<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Donor;
use App\Models\Volunteer;
use App\Models\Beneficiary;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
public function registerAsDonor(Request $request)
{
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name'  => 'required|string|max:255',
        
        'email'      => 'required|email|unique:donors,email',
        'password'   => 'required|string|min:6|confirmed',
        'fcm_token'  => 'nullable|string',
    ]);

    // إنشاء المتبرع مع حفظ FCM token بطريقة موحدة
    $donor = Donor::create([
        ...$validated, // يشمل first_name, last_name, email, fcm_token
        'password' => Hash::make($validated['password']),
          'fcm_token' => $request->fcm_token ?? null,
    ]);

    $token = $donor->createToken('token')->plainTextToken;

    return response()->json([
        'message' => __('messages.registered_as_donor'),
        'donor' => [
            'id' => $donor->id,
            'first_name' => $donor->first_name ,
            'last_name' => $donor->last_name,
            'email' => $donor->email,
            'created_at' => $donor->created_at,
            'fcm_token' => $donor->fcm_token,
        ],
        'token' => $token,
    ], 201);
}


    public function registerAsVolunteer(Request $request)
    {
        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'phone'                    => 'required|string|max:20|unique:volunteers,phone',
            'email'                    => 'required|email|unique:volunteers,email',
            'password'                 => 'required|string|min:8|confirmed',
            'address_en'               => 'required|string|max:500',
            'address_ar'               => 'required|string|max:500',
            'academic_certificate_en' => 'nullable|string|max:255',
            'academic_certificate_ar' => 'nullable|string|max:255',
            'experiences_en'           => 'nullable|string',
            'experiences_ar'           => 'nullable|string',
              'fcm_token'  => 'nullable|string',
        ]);

        $volunteer = Volunteer::create([
            'name'                     => $validated['name'],
            'phone'                    => $validated['phone'],
            'email'                    => $validated['email'],
            'password'                 => Hash::make($validated['password']),
            'address_en'               => $validated['address_en'],
            'address_ar'               => $validated['address_ar'],
            'academic_certificate_en' => $validated['academic_certificate_en'] ?? null,
            'academic_certificate_ar' => $validated['academic_certificate_ar'] ?? null,
            'experiences_en'           => $validated['experiences_en'] ?? null,
            'experiences_ar'           => $validated['experiences_ar'] ?? null,
              'fcm_token' => $request->fcm_token ?? null,
        ]);
 $token = $volunteer->createToken('token')->plainTextToken;
        $locale = app()->getLocale();

        return response()->json([
            'message' => ('messages.registered_as_volunteer'),
            'volunteer' => [
                'id' => $volunteer->id,
                'name' => $volunteer->name,
                'email' => $volunteer->email,
                'phone' => $volunteer->phone,
                'address' => $volunteer->{'address_' . $locale},
                'academic_certificate' => $volunteer->{'academic_certificate_' . $locale},
                'experiences' => $volunteer->{'experiences_' . $locale},
                 'fcm_token' => $volunteer->fcm_token,
            ], 'token'=>$token
        ], 201);
    }
    
public function registerAsBeneficiary(Request $request)
{
    $validated = $request->validate([
        'first_name'  => 'required|string|max:255',
        'father_name' => 'required|string|max:255',
        'mother_name' => 'required|string|max:255',
        'address_en'  => 'nullable|string|max:500',
        'address_ar'  => 'nullable|string|max:500',
        'phone'       => 'required|string|max:20|unique:beneficiaries,phone',
        'email'       => 'required|email|unique:beneficiaries,email',
        'password'    => 'required|string|min:8|confirmed',
        'fcm_token'   => 'nullable|string',
    ]);

    // إنشاء المستفيد مع حفظ FCM token
    $beneficiary = Beneficiary::create([
        ...$validated,
        'password' => Hash::make($validated['password']),
        'fcm_token' => $request->fcm_token ?? null,
    ]);

    // إنشاء Token لتسجيل الدخول
    $token = $beneficiary->createToken('token')->plainTextToken;

    $locale = app()->getLocale();

    return response()->json([
        'message' => __('messages.registered_as_beneficiary'),
        'beneficiary' => [
            'id'          => $beneficiary->id,
            'first_name'  => $beneficiary->first_name,
            'father_name' => $beneficiary->father_name,
            'mother_name' => $beneficiary->mother_name,
            'email'       => $beneficiary->email,
            'phone'       => $beneficiary->phone,
            'address'     => $beneficiary->{'address_' . $locale},
            'fcm_token' => $beneficiary->fcm_token,
        ],
     
        'token'     => $token,
    ], 201);
}

   public function login(Request $request)
{
    $locale = app()->getLocale();
    $fields = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
        'fcm_token' => 'nullable|string',
    ]);

    $userTypes = [
        ['model' => User::class, 'type' => 'user'],
        ['model' => Donor::class, 'type' => 'donor'],
        ['model' => Volunteer::class, 'type' => 'volunteer'],
        ['model' => Beneficiary::class, 'type' => 'beneficiary'],
    ];

    foreach ($userTypes as $userType) {
        $user = $userType['model']::where('email', $fields['email'])->first();

        if ($user && Hash::check($fields['password'], $user->password)) {
            $token = $user->createToken($userType['type'].'Token')->plainTextToken;

            // حفظ fcm_token إذا أرسله التطبيق
            if (!empty($fields['fcm_token'])) {
                $user->fcm_token = $fields['fcm_token'];
                $user->save();
            }

            $name = $user->name ?? ($user->first_name.' '.($user->last_name ?? ''));

            if($userType['type'] === 'beneficiary') {
    $responseUser = [
        'id' => $user->id,
        'first_name' => $user->first_name,
        'father_name' => $user->father_name,
        'mother_name' => $user->mother_name,
        'email' => $user->email,
        'phone' => $user->phone,
        'address'=> $user->{'address_' . $locale},
        'user_type' => $userType['type'],
        'fcm_token' => $user->fcm_token,
    ];
     return response()->json([
        'message' => __('messages.login_success'),
        'user' => $responseUser,
        'token' => $token,
    ]);
}

  if($userType['type'] === 'donor') {
    $responseUser = [
        'id' => $user->id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
         'user_type' => $userType['type'],
        'email' => $user->email,
        'fcm_token' => $user->fcm_token,
    ];
     return response()->json([
        'message' => __('messages.login_success'),
        'user' => $responseUser,
        'token' => $token,
    ]);
}

if($userType['type'] === 'volunteer') {
    $responseUser = [
        'id' => $user->id,
        'name' => $user->name,
        'phone' => $user->phone,
        'email' => $user->email,
        'address' => $user->{'address_' . $locale},
        'academic_certificate' => $user->{'academic_certificate_' . $locale},
        'experiences' => $user->{'experiences_' . $locale},
        'fcm_token' => $user->fcm_token,
            'user_type' => $userType['type'],
    ];

    return response()->json([
        'message' => __('messages.login_success'),
        'user' => $responseUser,
        'token' => $token,
    ]);
}


            return response()->json([
                'message' => __('messages.login_success'),
                'user' => [
                    'id'        => $user->id,
                    'name'      => $name,
                    'email'     => $user->email,
                    'user_type' => $userType['type'],
                    'fcm_token' => $user->fcm_token, // 🔹 هنا يظهر FCM token
                ],
                'token' => $token,
            ]);
        }
    }

    return response()->json(['message' => __('messages.login_failed')], 401);
}





            public function logout(Request $request)
{
    $user = $request->user();

    if ($user) {
        $user->tokens()->delete();

        // ✅ مسح fcm_token
        $user->fcm_token = null;
        $user->save();

        return response()->json(['message' => ('messages.logout_success')]);
    }

    return response()->json(['message' => ('messages.user_not_found')], 400);
}

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6|confirmed',
            'is_admin'  => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        return response()->json([
            'message' => ('messages.user_registered_success'),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
            ],
        ], 201);
    }
public function updateFcmToken(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string',
    ]);

    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }

       $user->fcm_token = $request->fcm_token; 
    $user->save();

    return response()->json(['message' => 'FCM token saved successfully']);
}

}