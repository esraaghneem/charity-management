<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\SupportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use App\Notifications\FcmNotificationMessage;
use App\Notifications\SupportRequestSubmitted;

class BeneficiaryController extends Controller
{
    // تحديث الملف الشخصي
    public function updateProfile(Request $request)
    {
        $beneficiary = Auth::user();

        $validated = $request->validate([
            'fcm_token'   => 'nullable|string',
            'first_name'  => 'sometimes|string|max:255',
            'father_name' => 'sometimes|string|max:255',
            'mother_name' => 'sometimes|string|max:255',
            'address_en'  => 'sometimes|string|max:500',
            'address_ar'  => 'sometimes|string|max:500',
            'phone'       => 'sometimes|string|max:20',
            'email'       => 'sometimes|email|unique:beneficiaries,email,' . $beneficiary->id,
            'password'    => 'nullable|string|min:8|confirmed',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $beneficiary->update($validated);

        // حفظ FCM token
        if (isset($validated['fcm_token'])) {
            $beneficiary->fcm_token = $validated['fcm_token'];
            $beneficiary->save();
        }

        // إرسال إشعار على قاعدة البيانات و FCM
        if ($beneficiary->fcm_token) {
            $data = [
    'title' => 'تم تعديل ملفك الشخصي',
    'body' => 'تم تحديث بياناتك بنجاح',

    'extra' => [  'submitted_at'=> now()->toDateTimeString(),
     'status' => $beneficiary->status,
        ]
];

$beneficiary->notify(new FcmNotificationMessage($data));


            // إرسال FCM مباشرة
            $this->sendFCMNotificationV1(
                $beneficiary->fcm_token,
                $data['title'],
                $data['body']
            );
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'beneficiary' => $beneficiary,
        ], 200);
    }

    // إرسال FCM
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

    // تقديم طلب دعم
public function submitSupportRequest(Request $request)
{
    // 1️⃣ التحقق من البيانات
    $validated = $request->validate([
        'health_status_en'   => 'nullable|string|max:255',
        'health_status_ar'   => 'nullable|string|max:255',
        'monthly_income'     => 'nullable|numeric',
        'job_status_en'      => 'nullable|string|max:255',
        'job_status_ar'      => 'nullable|string|max:255',
        'is_single'          => 'required|boolean',
        'is_urgent'          => 'required|boolean',
        'support_type_en'    => 'nullable|string|max:255',
        'support_type_ar'    => 'nullable|string|max:255',
        'is_male'            => 'required|boolean',
        'has_family'         => 'required|boolean',
        'is_male_breadwinner_for_family'    => 'required|boolean',
        'is_female_breadwinner_for_family'  => 'required|boolean',
        'is_youth_without_family'           => 'required|boolean',
        'is_girl_without_family'            => 'required|boolean',
        'is_orphan'                         => 'required|boolean',
        'is_injured'                        => 'required|boolean',
        'is_disabled'                       => 'required|boolean',
        'total_number_of_children'          => 'nullable|integer',
        'number_of_disabled_children'       => 'nullable|integer',
        'needs_en'                          => 'nullable|string',
        'needs_ar'                          => 'nullable|string'
    ]);

    // 2️⃣ الحصول على المستخدم الحالي
    $beneficiary = Auth::user();

    // 3️⃣ إنشاء الطلب مع الحالة "pending"
    $supportRequest = SupportRequest::create(array_merge(
        ['beneficiary_id' => $beneficiary->id, 'status' => 'pending'],
        $validated
    ));

    // 4️⃣ إشعار المستفيد عند تقديم الطلب
    $data = [
        'title' => 'تم تقديم طلب دعم جديد',
        'body' => 'تم تقديم طلب دعم من قبل المستفيد: ' . $beneficiary->first_name,
        'extra' => [
            'support_request_id' => $supportRequest->id,
            'is_urgent' => $supportRequest->is_urgent,
            'status' => $supportRequest->status, // pending
            'submitted_at'=> now()->toDateTimeString(),
        ]
    ];

    $beneficiary->notify(new \App\Notifications\FcmNotificationMessage($data));

    // 5️⃣ إرسال FCM مباشرة إذا كان موجود
    if ($beneficiary->fcm_token) {
        $this->sendFCMNotificationV1(
            $beneficiary->fcm_token,
            $data['title'],
            $data['body']
        );
    }

    // 6️⃣ إعادة الاستجابة
    return response()->json([
        'message' => 'تم تقديم طلب الدعم بنجاح',
        'request' => $supportRequest,
    ], 201);
}


}
