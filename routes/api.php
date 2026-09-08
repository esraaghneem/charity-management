<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SubProjectController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\AuthController;
Route::post('/register', [AuthController::class, 'register']);


// مسارات المشاريع
Route::post('/projects', [ProjectController::class, 'store']); // إنشاء مشروع جديد
Route::get('/projects', [ProjectController::class, 'getProject']); // جلب جميع المشاريع
Route::post('/search',[ProjectController::class, 'searchProjectByName']);//البحث 


// مسارات المشاريع الفرعية
Route::post('/subprojects', [SubProjectController::class, 'store']); // إنشاء مشروع فرعي جديد
Route::get('/show/{id}/{projecr_id}', [SubProjectController::class, 'showInfo']);
Route::get('/getP/{id}', [SubProjectController::class, 'getProjects']);

//الحملات التبرعية
//الحملات التبرعية
Route::post('/campaign', [CampaignController::class, 'storeCampaign']);
Route::get('/getC', [CampaignController::class, 'index']);
Route::get('/getCam/{id}', [CampaignController::class,'getCampaign']);


//السلة

Route::post('/cart', [CartController::class, 'addToCart']);
Route::get('/cart/{cartId}', [CartController::class, 'show']);
Route::delete('/remove',[CartController::class,'removeFromCart']);
Route::post('/updat', [CartController::class, 'updateCart']);



Route::post('/register-donor', [AuthController::class, 'registerAsDonor']);
Route::post('/register-as-volunteer', [AuthController::class, 'registerAsVolunteer']);



Route::post('/register-as-beneficiary', [AuthController::class, 'registerAsBeneficiary']);

Route::middleware('auth:sanctum')->post('/support-request', [BeneficiaryController::class, 'submitSupportRequest']);
///Route::middleware('auth:sanctum')->put('/updatrequest', [BeneficiaryController::class, 'updateRequest'])ك
Route::middleware('auth:sanctum')->delete('/deletre/{id}',[BeneficiaryController::class,'deleteSupportRequest']);
Route::middleware('auth:sanctum')->put('/update-profile', [BeneficiaryController::class, 'updateProfile']);


Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);


// use App\Http\Controllers\DonorController;
// Route::middleware(['auth:sanctum'])->group(function () {
//     Route::put('/donor/profile', [DonorController::class, 'updateProfile']);
//     Route::delete('/donor/account', [DonorController::class, 'deleteAccount']);
// });

// use App\Http\Controllers\VolunteerController;

// Route::middleware(['auth:sanctum'])->group(function () {
//     Route::put('/volunteer/profile', [VolunteerController::class, 'updateProfile']);
//     Route::delete('/volunteer/account', [VolunteerController::class, 'deleteAccount']);
// });


/*
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/donations', [DonationController::class, 'store']);
});*/

//Route::get('/sub_projects/{project_id}', [SubProjectController::class, 'getByProject']);

//Route::get('/subprojects/autocomplete', [SubProjectController::class, 'autocomplete']);



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);




Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', function (Request $request) {
        return response()->json($request->user());
    });

   // Route::post('/donate', [DonationController::class, 'store']);
});
//حماية الراوتات باستخدام المصادقة بحيث لا يمكن الوصول اليه الا للمستخدمين المسجلين دخولهم




Route::post('/change-language', [AuthController::class, 'changeLanguage']);


use App\Http\Controllers\TranslationController;


Route::post('/translate', [TranslationController::class, 'translate']);



// Beneficiary Authenticated Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('beneficiaries')->group(function () {
       // Route::put('/update-profile', [BeneficiaryController::class, 'updateProfile']);
        Route::delete('delete-profile', [BeneficiaryController::class, 'deleteProfile']);
       // Route::post('/updatrequest', [BeneficiaryController::class, 'updatRequest']);
      //  Route::post('support-requests', [BeneficiaryController::class, 'submitSupportRequest']);
       // Route::get('support-requests', [BeneficiaryController::class, 'listSupportRequests']);
    });
});



// Route::middleware('auth:sanctum')->group(function () {
//     Route::put('/donors/{id}', [DonorController::class, 'updateProfile']);
//     Route::delete('/donors/{id}', [DonorController::class, 'deleteAccount']);
// });

// use App\Http\Controllers\NotificationController;
// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/notifications', [NotificationController::class, 'index']);
//     Route::get('/notifications/unread', [NotificationController::class, 'unread']);
//     Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
//     Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
//     Route::post('/notifications/clear', [NotificationController::class, 'clear']);
//     Route::delete('/notifications/{id}', [NotificationController::class, 'deleteById']);

// });






use App\Http\Controllers\AchievementController;

Route::get('/achievements', [AchievementController::class, 'index']);        // جلب كل الإنجازات
Route::get('/achievements/{id}', [AchievementController::class, 'show']);  // جلب تفاصيل إنجاز معين
Route::post('/achievements', [AchievementController::class, 'store']);     // إنشاء إنجاز جديد

Route::post('/register/user', [AuthController::class, 'register']);


use App\Http\Controllers\VolunteerController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::put('/volunteer/profile', [VolunteerController::class, 'updateProfile']);
    Route::delete('/volunteer/account', [VolunteerController::class, 'deleteAccount']);
Route::post('/reqes/{id}',[VolunteerController::class,'joinRole']);
});
use App\Http\Controllers\RoleController;

Route::prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'index']);        // عرض كل الرولات (الحملات التطوعية)
    Route::post('/', [RoleController::class, 'store']);       // إنشاء حملة تطوعية جديدة (رول)
    Route::get('/{id}', [RoleController::class, 'show']);     // عرض حملة تطوعية معينة
    Route::put('/{id}', [RoleController::class, 'update']);   // تحديث حملة تطوعية
    Route::delete('/{id}', [RoleController::class, 'destroy']); // حذف حملة تطوعية

});
// use App\Http\Controllers\NotificationController;
// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/notifications', [NotificationController::class, 'index']);
//     Route::get('/notifications/unread', [NotificationController::class, 'unread']);
//     Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
//     Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
//     Route::post('/notifications/clear', [NotificationController::class, 'clear']);
//     Route::delete('/notifications/{id}', [NotificationController::class, 'deleteById']);
// });
use App\Http\Controllers\NotificationController;
Route::middleware('auth:sanctum')->post('/update-fcm-token', [AuthController::class, 'updateFcmToken']);
Route::middleware('auth:sanctum')->group(function () {
    // إرسال إشعار لمستخدم محدد
   Route::post('/notify-user', [NotificationController::class, 'notifyUser']);

    // جلب كل الإشعارات للمستخدم الحالي
    Route::get('/notifications', [NotificationController::class, 'index']);

    // جلب الإشعارات الغير مقروءة فقط
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);

    // تعليم كل الإشعارات كمقروءة
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

    // حذف كل الإشعارات
    Route::delete('/notifications/clear', [NotificationController::class, 'clear']);

    // حذف إشعار معين بالـ ID
    Route::delete('/notifications/{id}', [NotificationController::class, 'deleteById']);
});
use App\Http\Controllers\DonorController;
Route::middleware(['auth:sanctum'])->group(function () {
    Route::put('/donor/profile', [DonorController::class, 'updateProfile']);
    Route::delete('/donor/account', [DonorController::class, 'deleteAccount']);
});

use App\Http\Controllers\WalletController;
Route ::middleware('auth:donor')->group(function(){
Route::post('/donate',[DonationController::class,'donate']);
Route::post('/wallet/recharge',[WalletController::class,'recharge']);
Route::get('/wallet/balance',[WalletController::class,'balance']);
Route::post('/dontCar/{id}', [DonationController::class, 'donateFromCart']);

});


use App\Http\Controllers\AdminAuthController;
Route::post('/admin/login', [AdminAuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
    Route::get('/admin/donors', [AdminAuthController::class, 'donors']);
    Route::get('/admin/volunteers', [AdminAuthController::class, 'volunteers']);
    Route::get('/admin/beneficiaries', [AdminAuthController::class, 'beneficiaries']);
// طلبات الدعم
Route::post('roles/{id}/{roleid}', [AdminAuthController::class, 'acceptVolunteerRequest']);
Route::post('role/{id}/{roleid}', [AdminAuthController::class, 'rejectVolunteerRequest']);
Route::get('/support-requests', [AdminAuthController::class, 'supportRequests']);
Route::patch('/support-requests/{id}/confirm', [AdminAuthController::class, 'confirmSupportRequest']);
Route::patch('/support-requests/{id}/deactivate', [AdminAuthController::class, 'deactivateSupportRequest']);
Route::get('/getvolunteerRole', [AdminAuthController::class, 'getPendingVolunteerRequests']);
//Route::post('/reqest/{id}',[VolunteerController::class,'joinRole']);
Route::get('/counts', [AdminAuthController::class, 'getCounts']);
 Route::get('/topDonors', [AdminAuthController::class, 'getTopDonors']);
 Route::get('/pendingReqCount', [AdminAuthController::class, 'getPendingRequestsCount']);
// المشاريع
Route::get('/projectsA', [AdminAuthController::class, 'projects']);
Route::post('/projectsA', [AdminAuthController::class, 'addProject']);
Route::post('/projectsA/{id}', [AdminAuthController::class, 'updateProject']);
Route::patch('/projects/{id}/toggle-status', [AdminAuthController::class, 'toggleProjectStatus']);

Route::get('/sub-projectsA', [AdminAuthController::class, 'subProjects']);
Route::post('/sub-projectsA', [AdminAuthController::class, 'addSubProject']);
Route::post('/sub-projectsA/{id}', [AdminAuthController::class, 'updateSubProject']);
Route::patch('/sub-projects/{id}/toggle-status', [AdminAuthController::class, 'toggleSubProjectStatus']);
Route::get('/subProjects/{projectId}', [AdminAuthController::class, 'getSubProjectsByProjectId']);// الحملات
Route::get('/campaigns', [AdminAuthController::class, 'campaigns']);
Route::post('/campaigns', [AdminAuthController::class, 'addCampaign']);
Route::post('/campaigns/{id}', [AdminAuthController::class, 'updateCampaign']);
Route::patch('/campaigns/{id}/toggle-status', [AdminAuthController::class, 'toggleCampaignStatus']);
Route::get('/statistics/general', [AdminAuthController::class, 'generalStatistics']);
Route::get('/statistics/campaigns', [AdminAuthController::class, 'campaignsStatistics']);
//::get('/admin/statistics', [AdminAuthController::class, 'statistics']);
Route::post('/searchrolesA', [AdminAuthController::class, 'searchProjectByName']);
Route::post('/searchS', [AdminAuthController::class, 'searcSubByName']);
Route::post('/searchC', [AdminAuthController::class, 'searcCamByName']);
Route::post('/searchR', [AdminAuthController::class, 'searcRoleByName']);Route::post('/rolesA', [AdminAuthController::class, 'store']);
Route::get('/rolesA', [AdminAuthController::class, 'getRoles']);
Route::post('/updateRoles/{id}', [AdminAuthController::class, 'update']);
Route::patch('/roles/{id}/toggle-status', [AdminAuthController::class, 'toggleRoleStatus']);
    Route::get('/admin/dashboard', [AdminAuthController::class, 'dashboard']);
    Route::get('/admin/settings', [AdminAuthController::class, 'settings']);





    Route::post('/searchrolesA', [AdminAuthController::class, 'searchProjectByName']);
Route::post('/searchS', [AdminAuthController::class, 'searcSubByName']);
Route::post('/searchC', [AdminAuthController::class, 'searcCamByName']);
Route::post('/searchR', [AdminAuthController::class, 'searcRoleByName']);

Route::post('roles/{id}/{roleid}', [AdminAuthController::class, 'acceptVolunteerRequest']);
Route::post('role/{id}/{roleid}', [AdminAuthController::class, 'rejectVolunteerRequest']);
Route::get('/getvolunteerRole', action: [AdminAuthController::class, 'getPendingVolunteerRequests']);
Route::get('/support-requests/confirmed', [AdminAuthController::class, 'getConfirmedSupportRequests']);
    Route::get('/support-requests/rejected', [AdminAuthController::class, 'getRejectedSupportRequests']);
    Route::post('/support-requests/{id}/confirm', [AdminAuthController::class, 'confirmSupportRequest']);
    Route::post('/support-requests/{id}/reject', [AdminAuthController::class, 'rejectSupportRequest']);
    Route::get('/accepted-beneficiaries', [AdminAuthController::class, 'getAcceptedBeneficiaries']);
    Route::get('/support-requests/pending', [AdminAuthController::class, 'getPendingRequests']);

    Route::get('/rolesAccept', action: [AdminAuthController::class, 'showAllRolesWithAcceptedVolunteers']);

    Route::get('/statistics/completed-projects', [AdminAuthController::class, 'completedProjects']);
    Route::get('/statistics/completed-sub-projects', [AdminAuthController::class, 'completedSubProjects']);
    Route::get('/statistics/completed-campaigns', [AdminAuthController::class, 'completedEmergencyCampaigns']);
Route::get('/statistics/completed-roles', [AdminAuthController::class, 'completedRoles']);

Route::post('/searchD', [AdminAuthController::class, 'searchDonorByName']);
Route::post('/searchV', [AdminAuthController::class, 'searchVolunteerByName']);
Route::post('/searchB', [AdminAuthController::class, 'searchBeneficiaryByName']);
Route::get('/admain', action: [AdminAuthController::class, 'getAdmain']);
});