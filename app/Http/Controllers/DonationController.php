<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\SubProject;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Notifications\FcmNotificationMessage;

class DonationController extends Controller
{
  public function donate(Request $request)
{
    $validator = Validator::make($request->all(), [
        'amount' => 'required|numeric|min:1',
        'currency' => 'nullable|string|in:usd,eur,gbp',
        'sub_project_id' => 'nullable|exists:sub_projects,id',
        'campaign_id' => 'nullable|exists:campaigns,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 400);
    }

    if ($request->sub_project_id && $request->campaign_id) {
        return response()->json([
            'success' => false,
            'message' => 'Choose either project or campaign'
        ], 400);
    }

    if (!$request->sub_project_id && !$request->campaign_id) {
        return response()->json([
            'success' => false,
            'message' => 'Project or campaign required'
        ], 400);
    }

    $donor = $request->user();
    $amount = $request->amount;

    if ($donor->wallet_balance < $amount) {
        return response()->json([
            'success' => false,
            'message' => 'Insufficient balance'
        ], 400);
    }

    $locale = app()->getLocale(); // 'ar' أو 'en'
    $targetName = 'Unknown';
    $targetExtraName = 'Unknown';

    // المشروع الفرعي
    if ($request->sub_project_id) {
        $subProject = SubProject::findOrFail($request->sub_project_id);

        if ($subProject->remaining_amount == 0) {
            return response()->json(['message' => 'Project already fully funded'], 201);
        }

        if ($amount > $subProject->remaining_amount) {
            return response()->json(['message' => 'Amount exceeds remaining'], 400);
        }

        $subProject->collected_amount += $amount;
        $subProject->remaining_amount -= $amount;
        $subProject->save();

        $targetExtraName = $subProject->{'name_' . $locale} ?: 'مشروع غير معروف';
        $targetName = "المشروع: " . $targetExtraName;

    } elseif ($request->campaign_id) {
        $campaign = Campaign::findOrFail($request->campaign_id);

        if ($campaign->remaining_amount == 0) {
            return response()->json(['message' => 'Campaign already fully funded'], 201);
        }

        if ($amount > $campaign->remaining_amount) {
            return response()->json(['message' => 'Amount exceeds remaining'], 400);
        }

        $campaign->collected_amount += $amount;
        $campaign->remaining_amount -= $amount;
        $campaign->save();

        $targetExtraName = $campaign->{'name_' . $locale} ?: 'حملة غير معروفة';
        $targetName = "الحملة: " . $targetExtraName;
    }

    // إنشاء التبرع
    $donation = Donation::create([
        'donor_id' => $donor->id,
        'amount' => $amount,
        'currency' => $request->currency ?? 'USD',
        'sub_project_id' => $request->sub_project_id,
        'campaign_id' => $request->campaign_id,
        'status' => 'success',
    ]);

    // خصم الرصيد
    $donor->wallet_balance -= $amount;
    $donor->save();

    // إشعار
    $data = [
        'title' => ' شكراً على تبرعك❤️' ,
        'body'  => "  تم التبرع بمبلغ \${$amount} إلى {$targetName}",
        'extra' => [
            'target_name' => $targetExtraName,
            'donation_id' => $donation->id,
            'amount' => $amount,
            'target' => $targetName,
            'submitted_at' => now()->toDateTimeString(),
            
        ]
    ];

    $donor->notify(new FcmNotificationMessage($data));

    if ($donor->fcm_token) {
        $this->sendFCMNotificationV1(
            $donor->fcm_token,
            $data['title'],
            $data['body']
        );
    }

    return response()->json([
        'success' => true,
        'message' => 'Donation successful',
        'donation' => $donation,
        'new_balance' => $donor->wallet_balance
    ], 201);
}

public function donateFromCart(Request $request, $cartId)
{
    $donor = $request->user();
    $cart = Cart::with(['subProjects', 'campaigns'])->findOrFail($cartId);

    if (!$cart->donor_id) {
        $cart->donor_id = $donor->id;
        $cart->save();
    }

    $totalAmount = 0;
    $locale = app()->getLocale(); // 'ar' أو 'en'

    // تبرعات المشاريع الفرعية
    foreach ($cart->subProjects as $subProject) {
        $amount = $subProject->pivot->amount ?? 0;
        if ($amount <= 0) continue;

        $donation = Donation::create([
            'donor_id' => $donor->id,
            'sub_project_id' => $subProject->id,
            'campaign_id' => null,
            'amount' => $amount,
            'status' => 'success',
        ]);

        $subProject->remaining_amount -= $amount;
        $subProject->collected_amount += $amount;
        $subProject->save();

        $totalAmount += $amount;

        $subProjectName = $subProject->{'name_' . $locale}; // الاسم حسب اللغة
        $targetName = "المشروع: {$subProjectName}";

        // إشعار
        $data = [
            'title' => ' شكراً على تبرعك', 
            'body'  => "تم التبرع بمبلغ \${$amount} إلى {$targetName}", 
            'extra' => [
                'donation_id' => $donation->id,
                'target_name' => $subProjectName,
                'amount' => $amount,
                'target' => $targetName,
                'donated_at' => now()->toDateTimeString(),
            ]
        ];

        $donor->notify(new FcmNotificationMessage($data));
        if (!empty($donor->fcm_token)) {
            $this->sendFCMNotificationV1(
                $donor->fcm_token,
                $data['title'],
                $data['body']
            );
        }
    }

    // تبرعات الحملات
    foreach ($cart->campaigns as $campaign) {
        $amount = $campaign->pivot->amount ?? 0;
        if ($amount <= 0) continue;

        $donation = Donation::create([
            'donor_id' => $donor->id,
            'sub_project_id' => null,
            'campaign_id' => $campaign->id,
            'amount' => $amount,
            'status' => 'success',
        ]);

        $campaign->remaining_amount -= $amount;
        $campaign->collected_amount += $amount;
        $campaign->save();

        $totalAmount += $amount;

        $campaignName = $campaign->{'name_' . $locale}; // الاسم حسب اللغة
        $targetName = "الحملة: {$campaignName}";

        $data = [
            'title' => ' شكراً على تبرعك❤️', 
            'body'  => "تم التبرع بمبلغ \${$amount} إلى {$targetName}", 
            'extra' => [
                'donation_id' => $donation->id,
                'target_name' => $campaignName,
                'amount' => $amount,
                'target' => $targetName,
                'donated_at' => now()->toDateTimeString(),
            ]
        ];

        $donor->notify(new FcmNotificationMessage($data));
        if (!empty($donor->fcm_token)) {
            $this->sendFCMNotificationV1(
                $donor->fcm_token,
                $data['title'],
                $data['body']
            );
        }
    }

    // خصم الرصيد
    $donor->wallet_balance -= $totalAmount;
    $donor->save();

    // تفريغ السلة
    $cart->subProjects()->detach();
    $cart->campaigns()->detach();

    return response()->json([
        'message' => 'Donation from cart successful',
        'total_donated' => $totalAmount,
        'new_balance' => $donor->wallet_balance
    ]);
}



    private function sendFCMNotificationV1($deviceToken, $title, $body)
    {
        $serviceAccountPath = storage_path('app/firebase/firebase_credentials.json');
        $projectId = json_decode(file_get_contents($serviceAccountPath), true)['project_id'];

        $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $serviceAccountPath
        );

        $accessToken = $credentials->fetchAuthToken()['access_token'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        try {
            $response = Http::withToken($accessToken)
                ->post($url, [
                    'message' => [
                        'token' => $deviceToken,
                        'notification' => [
                            'title' => $title,
                            'body'  => $body,
                        ],
                        'data' => [
                            'status' => 'profile_updated',
                        ]
                    ]
                ]);

            return $response->body();
        } catch (\Exception $e) {
            \Log::error("FCM Error: " . $e->getMessage());
            return $e->getMessage();
        }
    }
}
