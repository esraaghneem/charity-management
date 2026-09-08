<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Volunteer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Notifications\FcmNotificationMessage;

class VolunteerController extends Controller
{
    // ✅ تحديث الملف الشخصي
    public function updateProfile(Request $request)
    {
        $volunteer = Auth::user();

        $validated = $request->validate([
            'fcm_token'               => 'nullable|string',
            'name'                    => 'sometimes|required|string|max:255',
            'phone'                   => 'sometimes|required|string|max:20|unique:volunteers,phone,' . $volunteer->id,
            'address_en'              => 'sometimes|required|string|max:500',
            'address_ar'              => 'sometimes|required|string|max:500',
            'academic_certificate_en' => 'nullable|string|max:255',
            'academic_certificate_ar' => 'nullable|string|max:255',
            'experiences_en'          => 'nullable|string|max:1000',
            'experiences_ar'          => 'nullable|string|max:1000',
            'email'                   => 'sometimes|required|email|unique:volunteers,email,' . $volunteer->id,
            'password'                => 'nullable|string|min:8|confirmed',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $volunteer->update($validated);

        // حفظ FCM token
        if (isset($validated['fcm_token'])) {
            $volunteer->fcm_token = $validated['fcm_token'];
            $volunteer->save();
        }

        // إشعار عند تحديث الملف
        if ($volunteer->fcm_token) {
            $data = [
                'title' => 'تم تعديل ملفك الشخصي',
                'body'  => 'تم تحديث بياناتك بنجاح',
                'extra' => [
                    'submitted_at' => now()->toDateTimeString(),
                ]
            ];

            $volunteer->notify(new FcmNotificationMessage($data));

            $this->sendFCMNotificationV1(
                $volunteer->fcm_token,
                $data['title'],
                $data['body']
            );
        }

        return response()->json([
            'message' => __('messages.profile_updated_successfully'),
            'volunteer' => $volunteer,
        ]);
    }

    // ✅ طلب الانضمام لدور معين (مثل طلب الدعم للمستفيد)
public function joinRole(Request $request, $roleId)
{
    $volunteer = Auth::user();
    $role = Role::findOrFail($roleId);

    // تحقق إذا المتطوع مسجّل مسبقًا
    if ($role->volunteers()->where('volunteer_id', $volunteer->id)->exists()) {
        return response()->json([
            'message' => __('messages.You have already submitted a request for this campaign'),
        ], 400);
    }

    // إضافة الطلب بحالة pending
    $role->volunteers()->attach($volunteer->id, ['status' => 'pending']);

    // اسم الرول حسب اللغة الحالية
    $locale = app()->getLocale();
    $roleName = $role->{'name_'.$locale};

    // إعداد البيانات للإشعار
    $data = [
        'title' => 'تم تقديم طلب انضمام جديد',
        'body'  => 'تم تقديم طلب تطوع للحملة: ' . $roleName .  'من قبل المتطوع:' .$volunteer->name,
        'extra' => [
            'role_id' => $roleId,
            'status' => 'pending',
            'submitted_at' => now()->toDateTimeString(),
        ]
    ];

    // إرسال إشعار إذا لديه fcm_token
    if ($volunteer->fcm_token) {
        $volunteer->notify(new FcmNotificationMessage($data));
        $this->sendFCMNotificationV1($volunteer->fcm_token, $data['title'], $data['body']);
    }

    return response()->json([
        'message' => __('messages.The request has been sent, awaiting approval')
    ]);
}



    // ✅ حذف الحساب
    public function deleteAccount()
    {
        $volunteer = Auth::user();
        $volunteer->tokens()->delete();
        $volunteer->delete();

        return response()->json([
            'message' => __('messages.account_deleted_successfully')
        ]);
    }

    // ✅ إرسال FCM
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
                            'status' => 'volunteer_join_role',
                        ]
                    ]
                ]);

            \Log::info("FCM response (volunteer): " . $response->body());

            return $response->body();
        } catch (\Exception $e) {
            \Log::error("FCM Error (volunteer): " . $e->getMessage());
            return $e->getMessage();
        }
    }
}
