<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Donor;
use App\Models\Project;
use App\Models\RoleVolunteer;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Volunteer;
use App\Models\SubProject;
use App\Models\Beneficiary;
use Illuminate\Http\Request;
use App\Models\SupportRequest;
use App\Models\SubProjectDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage; // ✅ تم إضافة هذا الاستيراد

class AdminAuthController extends Controller
{
    // تسجيل دخول الأدمن
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $admin = User::where('email', $request->email)
                     ->where('is_admin', true)
                     ->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['البريد الإلكتروني أو كلمة المرور غير صحيحة.'],
            ]);
        }

        $token = $admin->createToken('AdminAccessToken')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'token'   => $token,
            'user'    => [
                'id'       => $admin->id,
                'name'     => $admin->name,
                'email'    => $admin->email,
                'is_admin' => $admin->is_admin,
            ],
        ]);
    }

    // تسجيل الخروج
    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'المستخدم غير مصرح أو التوكن غير موجود'
            ], 401);
        }

        $user->tokens()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }

    // عرض قائمة المتبرعين (بدون تقسيم لغات)
    public function donors()
    {
        $this->authorizeAdmin();

        $donors = Donor::select('id', 'first_name', 'last_name', 'email', 'created_at')->get();

        return response()->json([
            'message' => 'قائمة المتبرعين',
            'donors' => $donors,
        ]);
    }

    // عرض قائمة المتطوعين (بدون تقسيم لغات)
    public function volunteers()
    {
        $this->authorizeAdmin();
    
        $volunteers = Volunteer::select(
            'id',
            'name',
            'email',
            'phone',
            'address_ar as address',
            'academic_certificate_ar as academic_certificate',
            'experiences_ar as experiences',
            'created_at'
        )->get();
    
        return response()->json([
            'message' => 'قائمة المتطوعين',
            'volunteers' => $volunteers,
        ]);
    }

    public function beneficiaries()
    {
        $this->authorizeAdmin();

        $beneficiaries = Beneficiary::select(
            'id',
            'first_name',
            'father_name',
            'mother_name',
            'address_ar as address',
            'phone',
            'email',
            'created_at'
        )->get();

        return response()->json([
            'message' => 'قائمة المستفيدين',
            'beneficiaries' => $beneficiaries,
        ]);
    }

    // عرض طلبات الدعم
    public function supportRequests(Request $request)
    {
        $this->authorizeAdmin();

        // استلام معرف المستفيد من الطلب (مثلاً عبر query param)
        $beneficiaryId = $request->query('beneficiary_id');

        $query = SupportRequest::with('beneficiary');

        if ($beneficiaryId) {
            $query->where('beneficiary_id', $beneficiaryId);
        }

        $requests = $query->get();

        return response()->json([
            'message' => 'قائمة طلبات الدعم',
            'requests' => $requests,
        ]);
    }

    // تأكيد طلب دعم معين
    public function confirmSupportRequest($id)
    {
        $this->authorizeAdmin();

        $request = SupportRequest::findOrFail($id);
        $request->status = 'confirmed';
        $request->save();

        return response()->json([
            'message' => 'تم تأكيد طلب الدعم',
            'request' => $request,
        ]);
    }

    // إلغاء تفعيل طلب الدعم (بدون حذف)
    public function deactivateSupportRequest($id)
    {
        $this->authorizeAdmin();

        $request = SupportRequest::findOrFail($id);
        $request->status = 'inactive'; // أو 'cancelled' حسب تعريفك
        $request->save();

        return response()->json([
            'message' => 'تم إلغاء تفعيل طلب الدعم',
            'request' => $request,
        ]);
    }

    // المشاريع - عرض
    public function projects()
    {
        $projects = Project::all()->map(function ($project) {
            return [
                'id' => $project->id,
                'name_en' => $project->name_en,
                'name_ar' => $project->name_ar,
                'image_url' => $project->image ? asset('storage/' . $project->image) : null, // ✅ تم التأكد من asset('storage/')
                'status' => $project->status,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
            ];
        });
    
        return response()->json([
            'projects' => $projects
        ]);
    }

    // إضافة مشروع
    public function addProject(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'name_ar' => 'nullable|string|max:255',
            'name_en'=>'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'status' => 'boolean',
        ]);
        $imagepath = null;
        if ($request->hasFile('image')) {
            // ✅ تم التأكد من استخدام 'public' كقرص تخزين
            $imagepath = $request->file('image')->store('projects', 'public'); 
        }

        $project = Project::create([
            'name_ar' => $request->name_ar,
            'name_en'=>$request->name_en,
            'image' => $imagepath,
            'status' => $request->status ?? true,
        ]);

        return response()->json([
            'message' => 'تم إضافة المشروع بنجاح',
            'project' => [ // ✅ تم تعديل الاستجابة لتشمل image_url
                'id' => $project->id,
                'name_ar' => $project->name_ar,
                'name_en' => $project->name_en,
                'image_url' => $project->image ? asset('storage/' . $project->image) : null,
                'status' => $project->status,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
            ],
        ]);
    }

    // تعديل مشروع
    public function updateProject(Request $request, $id)
{
    $this->authorizeAdmin();
    
    $project = Project::findOrFail($id);
    
    $validatedData = $request->validate([
        'name_ar' => 'nullable|string|max:255',
        'name_en' => 'nullable|string|max:255',
        'image' => 'nullable|image|max:2048',
        'status' => 'nullable|integer|in:0,1', // ✅ Corrected validation rule
    ]);
    
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('projects', 'public');
    } else {
        $imagePath = $project->image;
    }
    
    $project->update([
        'name_ar' => $validatedData['name_ar'] ?? $project->name_ar,
        'name_en' => $validatedData['name_en'] ?? $project->name_en,
        'image' => $imagePath,
        'status' => $validatedData['status'] ?? $project->status,
    ]);
    
    return response()->json([
        'message' => 'تم تعديل المشروع بنجاح',
        'project' => [
            'id' => $project->id,
            'name_ar' => $project->name_ar,
            'name_en' => $project->name_en,
            'image_url' => $project->image ? asset('storage/' . $project->image) : null,
            'status' => $project->status,
            'created_at' => $project->created_at,
            'updated_at' => $project->updated_at,
        ],
    ]);
}
    // إلغاء تفعيل / تفعيل مشروع (toggle status)
    public function toggleProjectStatus($id)
    {
        $this->authorizeAdmin();

        $project = Project::findOrFail($id);
        $project->status = !$project->status;
        $project->save();

        return response()->json([
            'message' => 'تم تعديل حالة تفعيل المشروع',
            'status' => $project->status,
        ]);
    }

    // المشاريع الفرعية - عرض
   
public function subProjects()
   {
       $this->authorizeAdmin();
   
       $subProjects = SubProject::select(
           'id',
           'project_id',
           'name_ar as name',
           'location_ar as location',
           'needs_ar as needs',
           'remaining_amount',
           'collected_amount',
           'required_amount',
           'image',
           'status',
           'created_at',
           'updated_at'
       )->get();
   
       return response()->json([
           'message' => 'قائمة المشاريع الفرعية',
           'subProjects' => $subProjects,
       ]);
   }
   public function getSubProjectsByProjectId($projectId)
{
    $this->authorizeAdmin();

    $subProjects = SubProject::select(
        'id',
        'project_id',
        'name_ar',
        'name_en',
        'location_ar',
        'location_en',
        'needs_ar',
        'needs_en',
        'remaining_amount',
        'collected_amount',
        'required_amount',
        'image',
        'status',
        'created_at',
        'updated_at'
    )
    ->where('project_id', $projectId)
    ->get();

    return response()->json([
        'message' => 'قائمة المشاريع الفرعية للمشروع رقم ' . $projectId,
        'subProjects' => $subProjects,
    ]);
}
   public function addSubProject(Request $request)
   {
       $this->authorizeAdmin();
   
       $request->validate([
           'project_id' => 'required|exists:projects,id',
           'name_ar' => 'nullable|string|max:255',
           'name_en' => 'nullable|string|max:255',
           'location_ar' => 'nullable|string|max:255',
           'location_en' => 'nullable|string|max:255',
           'needs_en' => 'nullable|string|max:255',
           'needs_ar' => 'nullable|string|max:255',
           'remaining_amount' => 'required|numeric',
           'required_amount' => 'required|numeric',
           'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
           'status' => 'boolean',
   
           // ✅ تحقق من مصفوفة التفاصيل
           'details' => 'nullable|array',
           'details.*.key_en' => 'nullable|string',
           'details.*.key_ar' => 'nullable|string',
           'details.*.value_en' => 'nullable|string',
           'details.*.value_ar' => 'nullable|string',
       ]);
   
       $imagepath = null;
       if ($request->hasFile('image')) {
           $imagepath = $request->file('image')->store('profile_pictures', 'public');
       }
   
       $subProject = SubProject::create([
           'project_id' => $request->project_id,
           'name_en' => $request->name_en,
           'name_ar' => $request->name_ar,
           'location_en' => $request->location_en,
           'location_ar' => $request->location_ar,
           'needs_en' => $request->needs_en,
           'needs_ar' => $request->needs_ar,
           'required_amount' => $request->required_amount,
           'remaining_amount' => $request->remaining_amount,
           'collected_amount' => $request->required_amount - $request->remaining_amount,
           'image' => $imagepath,
           'status' => $request->status ?? true
       ]);
   
       // ✅ معالجة تفاصيل المشروع الفرعي
       $formattedDetails = [];
       foreach ($request->input('details', []) as $detail) {
           $createdDetail = SubProjectDetail::create([
               'sub_project_id' => $subProject->id,
               'key_en' => $detail['key_en'] ?? null,
               'key_ar' => $detail['key_ar'] ?? null,
               'value_en' => $detail['value_en'] ?? null,
               'value_ar' => $detail['value_ar'] ?? null,
           ]);
   
           $formattedDetails[] = [
               'key_en' => $createdDetail->key_en,
               'key_ar' => $createdDetail->key_ar,
               'value_en' => $createdDetail->value_en,
               'value_ar' => $createdDetail->value_ar,
           ];
       }
   
       return response()->json([
           'message' => 'تم إضافة المشروع الفرعي بنجاح',
           'subProject' => [
               'id' => $subProject->id,
               'name_ar' => $subProject->name_ar,


'name_en' => $subProject->name_en,
               'image' => $subProject->image ? asset('storage/' . $subProject->image) : null,
               'location_ar' => $subProject->location_ar,
               'location_en' => $subProject->location_en,
               'needs_ar' => $subProject->needs_ar,
               'required_amount' => $subProject->required_amount,
               'collected_amount' => $subProject->collected_amount,
               'remaining_amount' => $subProject->remaining_amount,
               'details' => $formattedDetails,
            ]
        ]);
    }
    // تعديل مشروع فرعي
    public function updateSubProject(Request $request, $id)
    {
        $this->authorizeAdmin();
        $subProject = SubProject::findOrFail($id);
    
        $request->validate([
            'name_en' => 'nullable|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'location_en' => 'nullable|string|max:255',
            'location_ar' => 'nullable|string|max:255',
            'needs_en' => 'nullable|string|max:255',
            'needs_ar' => 'nullable|string|max:255',
            'required_amount' => 'nullable|numeric|min:0',
            'remaining_amount' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'nullable|boolean',
    
            // تفاصيل المشروع الفرعي
            'details' => 'nullable|array',
            'details.*.key_en' => 'nullable|string',
            'details.*.key_ar' => 'nullable|string',
            'details.*.value_en' => 'nullable|string',
            'details.*.value_ar' => 'nullable|string',
        ]);
    
        // حفظ الصورة الجديدة إن وُجدت
        $imagePath = $subProject->image;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('profile_pictures', 'public');
        }
    
        // تحديث المبالغ
        $required = $request->required_amount ?? $subProject->required_amount ?? 0;
        $remaining = $request->remaining_amount ?? $subProject->remaining_amount ?? 0;
        $collected = $required - $remaining;
    
        $subProject->update([
            'name_en' => $request->name_en ?? $subProject->name_en,
            'name_ar' => $request->name_ar ?? $subProject->name_ar,
            'location_en' => $request->location_en ?? $subProject->location_en,
            'location_ar' => $request->location_ar ?? $subProject->location_ar,
            'needs_en' => $request->needs_en ?? $subProject->needs_en,
            'needs_ar' => $request->needs_ar ?? $subProject->needs_ar,
            'required_amount' => $required,
            'remaining_amount' => $remaining,
            'collected_amount' => $collected,
            'status' => $request->status ?? $subProject->status,
            'image' => $imagePath,
        ]);
    
        // حذف التفاصيل القديمة وتخزين الجديدة
        if ($request->has('details')) {
            $subProject->details()->delete();
    
            foreach ($request->input('details', []) as $detail) {
                SubProjectDetail::create([
                    'sub_project_id' => $subProject->id,
                    'key_en' => $detail['key_en'] ?? null,
                    'key_ar' => $detail['key_ar'] ?? null,
                    'value_en' => $detail['value_en'] ?? null,
                    'value_ar' => $detail['value_ar'] ?? null,
                ]);
            }
        }
    
        // جمع تفاصيل العرض
        $formattedDetails = $subProject->details()->get()->map(function ($detail) {
            return [
                'key_en' => $detail->key_en,
                'key_ar' => $detail->key_ar,
                'value_en' => $detail->value_en,
                'value_ar' => $detail->value_ar,
            ];
        });
    
        return response()->json([
            'message' => 'تم تعديل المشروع الفرعي بنجاح',
            'subProject' => [
                'id' => $subProject->id,
                'name_en' => $subProject->name_en,
                'name_ar' => $subProject->name_ar,


'location_en' => $subProject->location_en,
                'location_ar' => $subProject->location_ar,
                'needs_en' => $subProject->needs_en,
                'needs_ar' => $subProject->needs_ar,
                'required_amount' => $subProject->required_amount,
                'remaining_amount' => $subProject->remaining_amount,
                'collected_amount' => $subProject->collected_amount,
                'status' => $subProject->status,
                'image_url' => $subProject->image ? asset('storage/' . $subProject->image) : null,
                'created_at' => $subProject->created_at,
                'updated_at' => $subProject->updated_at,
                'details' => $formattedDetails
            ]
        ]);
    }

    // إلغاء تفعيل / تفعيل مشروع فرعي
    public function toggleSubProjectStatus($id)
    {
        $this->authorizeAdmin();

        $subProject = SubProject::findOrFail($id);
        $subProject->status = !$subProject->status;
        $subProject->save();

        return response()->json([
            'message' => 'تم تعديل حالة تفعيل المشروع الفرعي',
            'status' => $subProject->status,
        ]);
    }

       public function campaigns()
    {
        $this->authorizeAdmin();
    
        $campaigns = Campaign::all()->map(function ($campaign) {
            $collected = $campaign->collected_amount ?? 0;
            $required = $campaign->required_amount ?? 0;
            $remaining = $required - $collected;
    
            return [
                'id' => $campaign->id,
                'name_en' => $campaign->name_en,
                'name_ar' => $campaign->name_ar,
                'description_en' => $campaign->description_en,
                'description_ar' => $campaign->description_ar,
                'location_en' => $campaign->location_en,
                'location_ar' => $campaign->location_ar,
                'required_amount' => $required,
                'collected_amount' => $collected,
                'remaining_amount' => $remaining,
                'start_date' => $campaign->startDate,
                'end_date' => $campaign->endDate,
                'image_url' => $campaign->image ? asset('storage/' . $campaign->image) : null, // ✅ تم التأكد من asset('storage/')
                'status' => $campaign->status,
                'created_at' => $campaign->created_at,
                'updated_at' => $campaign->updated_at,
            ];
        });
    
        return response()->json([
            'campaigns' => $campaigns
        ]);
    }

    // إضافة حملة دعم
    public function addCampaign(Request $request)
    {
        $this->authorizeAdmin();
    
        $request->validate([
            'name_ar' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'required_amount' => 'required|numeric|min:0',
            'remaining_amount' => 'required|numeric|min:0',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'location_ar' => 'nullable|string|max:255',
            'location_en' => 'nullable|string|max:255',
            'status' => 'boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'collected_amount' => 'nullable|numeric|min:0',
        ]);
    
        $imagePath = null;
        if ($request->hasFile('image')) {
            // ✅ تم التأكد من استخدام 'public' كقرص تخزين
            $imagePath = $request->file('image')->store('campaign_images', 'public');
        }
    
        $collected = $request->collected_amount ?? 0;
        $required = $request->required_amount ?? 0;
        $remaining = $required - $collected;
    
        $campaign = Campaign::create([
            'name_ar' => $request->name_ar,
            'name_en' => $request->name_en,
            'required_amount' => $required,
            'collected_amount' => $collected,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'location_ar' => $request->location_ar,
            'location_en' => $request->location_en,
            'status' => $request->status ?? true,
            'image' => $imagePath,
        ]);
    
        return response()->json([
            'message' => 'تم إضافة الحملة بنجاح',
            'campaign' => [
                'id' => $campaign->id,
                'name_en' => $campaign->name_en,
                'name_ar' => $campaign->name_ar,
                'location_en' => $campaign->location_en,
                'location_ar' => $campaign->location_ar,
                'required_amount' => $campaign->required_amount,
                'collected_amount' => $campaign->collected_amount,
                'remaining_amount' => $remaining,
                'start_date' => $campaign->startDate,
                'end_date' => $campaign->endDate,
                'image_url' => $campaign->image ? asset('storage/' . $campaign->image) : null, // ✅ تم التأكد من asset('storage/')
                'status' => $campaign->status,
                'created_at' => $campaign->created_at,
                'updated_at' => $campaign->updated_at,
            ],
        ]);
    }

    // تعديل حملة دعم
    public function updateCampaign(Request $request, $id)
    {
        $this->authorizeAdmin();
    
        $campaign = Campaign::findOrFail($id);
    
        $request->validate([
            'name_ar' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'required_amount' => 'nullable|numeric|min:0',
            'remaining_amount' => 'nullable|numeric|min:0',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'location_ar' => 'nullable|string|max:255',
            'location_en' => 'nullable|string|max:255',
            'status' => 'boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
    
        $required = $request->required_amount ?? $campaign->required_amount ?? 0;
        $remaining = $request->remaining_amount ?? $campaign->remaining_amount ?? 0;
        $collected = $required - $remaining;
    
        // حفظ الصورة أولاً، واستبدالها إذا أُرسلت
        $imagePath = $campaign->image;
        if ($request->hasFile('image')) {
            // ✅ تم التأكد من استخدام 'public' كقرص تخزين
            $imagePath = $request->file('image')->store('campaign_images', 'public');
        }
    
        $campaign->update([
            'name_ar' => $request->name_ar ?? $campaign->name_ar,
            'name_en' => $request->name_en ?? $campaign->name_en,
            'required_amount' => $required,
            'remaining_amount' => $remaining,
            'startDate' => $request->startDate ?? $campaign->startDate,
            'endDate' => $request->endDate ?? $campaign->endDate,
            'location_ar' => $request->location_ar ?? $campaign->location_ar,
            'location_en' => $request->location_en ?? $campaign->location_en,
            'status' => $request->status ?? $campaign->status,
            'image' => $imagePath,
        ]);
    
        return response()->json([
            'message' => 'تم تعديل الحملة بنجاح',
            'campaign' => [
                'id' => $campaign->id,
                'name_en' => $campaign->name_en,
                'name_ar' => $campaign->name_ar,
                'location_en' => $campaign->location_en,
                'location_ar' => $campaign->location_ar,
                'required_amount' => $campaign->required_amount,
                'remaining_amount' => $campaign->remaining_amount,
                'collected_amount' => $collected,
                'start_date' => $campaign->startDate,
                'end_date' => $campaign->endDate,
                'image_url' => $campaign->image ? asset('storage/' . $campaign->image) : null, // ✅ تم التأكد من asset('storage/')
                'status' => $campaign->status,
                'created_at' => $campaign->created_at,
                'updated_at' => $campaign->updated_at,
            ]
        ]);
    }

    // إلغاء تفعيل / تفعيل حملة دعم
    public function toggleCampaignStatus($id)
    {
        $this->authorizeAdmin();

        $campaign = Campaign::findOrFail($id);
        $campaign->status = !$campaign->status;
        $campaign->save();

        return response()->json([
            'message' => 'تم تعديل حالة تفعيل حملة الدعم',
            'status' => $campaign->status,
        ]);
    }

    // دالة مساعدة للتحقق من صلاحيات الأدمن
    private function authorizeAdmin()
    {
        $user = Auth::user();

        if (!$user || !$user->is_admin) {
            abort(403, 'غير مصرح لك بالدخول إلى هذه الصفحة.');
        }
    }

    public function generalStatistics()
    {
        $this->authorizeAdmin();

        $groupDonors = function ($donations) {
            return collect($donations)
            ->filter(fn($d) => $d->donor)
            ->groupBy(fn($d) => $d->donor->id)
            ->map(function ($group, $donorId) {
                $donor = $group->first()->donor;
                return [
                    'donor_id' => $donor->id,
                    'full_name' => "{$donor->first_name} {$donor->last_name}",
                    'total_donated' => $group->sum('amount'),
                ];
            })->values();
        };

        $completedProjects = Project::where('status', 1)
            ->with(['subProjects.donations.donor'])
            ->get()
            ->map(function ($project) use ($groupDonors) {
                $allDonations = $project->subProjects->flatMap(fn($sub) => $sub->donations);
                return [
                    'project_id' => $project->id,
                    'project_name' => $project->name_ar,
                    'donors' => $groupDonors($allDonations),
                ];
            });

        $completedSubProjects = SubProject::where('status', 1)
            ->with('donations.donor')
            ->get()
            ->map(function ($sub) use ($groupDonors) {
                return [
                    'sub_project_id' => $sub->id,
                    'sub_project_name' => $sub->name_ar,
                    'donors' => $groupDonors($sub->donations),
                ];
            });

        return response()->json([
            //'counts' => $this->getCounts(), // مُعلق حسب طلبك
            'completed_projects' => $completedProjects,
            'completed_sub_projects' => $completedSubProjects,
        ]);
    } 
    
public function store(Request $request)
    {

        $this->authorizeAdmin();
        $validated = $request->validate([
            'name_en' => 'nullable|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'start_end_time' => 'required|string|max:255',
            'start_end_date'=>'required|string|max:255',
            'description_en'=>'nullable|string|max:255',
            'description_ar'=>'nullable|string|max:255',
            'location_en' => 'nullable|string|max:255',
            'location_ar' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
         
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('profile_pictures', 'public');
        }

        $role = Role::create([
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
            'start_end_time' => $request->start_end_time,
           'start_end_date' => $request->start_end_date,
            'image' => $imagePath,
            'location_en' => $request->location_en,
            'location_ar' => $request->location_ar,
            'description_en' =>$request->description_en,
            'description_ar' => $request->description_ar,
            'status' => $request->status,
        ]);
        return response()->json([
            'message' => ('تم اضافة الحملة التطوعية'),
            'role' => [     'id' => $role->id,
            'name_ar' => $role->name_ar ?? null,
            'name_en' => $role->name_en ?? null,
            'start_end_time' => $role->start_end_time,
            'start_end_date'=>$role->start_end_date ?? null,
            'location_ar' => $role->location_ar ?? null,
            'location_en' => $role->location_en ?? null,
            'description_en'=>$role->description_en ?? null,
            'description_ar'=>$role->description_ar ?? null,
            'image' => $role->image ? asset('storage/' . $role->image) : null,
            'status' => $role->status ],
        ], 201);
    }
     
    public function getRoles(){
        $this->authorizeAdmin();
    $roles = Role::all()->map(function ($role)  {
        return [
            'id' => $role->id,
            'name_ar' => $role->name_ar,
            'name_en' => $role->name_en,
            'start_end_time' => $role->start_end_time,
            'start_end_date'=>$role->start_end_date,
            'image' => $role->image ? asset('storage/' . $role->image) : null,
            'location_ar' => $role->location_ar,
            'location_en' => $role->location_en,
            'description_en'=>$role->description_en,
            'description_ar'=>$role->description_ar,
          'status' => $role->status ,
     
        ];
    });

    return response()->json($roles, 200);
}public function update(Request $request, $id)
{
    $this->authorizeAdmin();
    $role = Role::findOrFail($id);
    
    $validated = $request->validate([
        'name_en' => 'sometimes|nullable|string|max:255',
        'name_ar' => 'sometimes|nullable|string|max:255',
        'start_end_time' => 'nullable|string|max:255',
        'start_end_date'=>'nullable|string|max:255',
        'location_en' => 'sometimes|nullable|string|max:255',
        'location_ar' => 'sometimes|nullable|string|max:255',
        'status' => 'sometimes|nullable|integer|in:0,1',
        'description_en'=>'nullable|string|max:255',
        'description_ar'=>'nullable|string|max:255', 
        'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    // ✅ هذا هو الجزء الذي يجب إضافته للتعامل مع الصورة
    if ($request->hasFile('image')) {
        // احذف الصورة القديمة من مجلد التخزين
        if ($role->image) {
            Storage::disk('public')->delete($role->image);
        }
        // احفظ الصورة الجديدة واحصل على مسارها
        $validated['image'] = $request->file('image')->store('profile_pictures', 'public');
    }

    $role->update($validated);

    return response()->json([
        'message' => 'تم التعديل',
        'role' => [
            'id' => $role->id,
            'name_ar' => $role->name_ar,
            'name_en' => $role->name_en,
            'start_end_time' => $role->start_end_time,
            'start_end_date' => $role->start_end_date,
            'image' => $role->image ? asset('storage/' . $role->image) : null,
            'location_ar' => $role->location_ar,
            'location_en' => $role->location_en,
            'description_en' => $role->description_en,
            'description_ar' => $role->description_ar,
            'status' => $role->status 
        ],
    ]);
}

public function toggleRoleStatus($id)
    {
        $this->authorizeAdmin();

        $role = Role::findOrFail($id);
        $role->status = !$role->status;
        $role->save();

        return response()->json([
            'message' => 'تم تعديل حالة تفعيل الحملة ',
            'status' => $role->status,
        ]);
    }
public function searchProjectByName(Request $request)
{
    $this->authorizeAdmin();
    $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $search = $request->name;

    $projects = Project::where(function ($q) use ($search) {
            $q->where('name_ar', 'like', "%$search%")
            ->orWhere('name_en', 'like', "%$search%");
        })
        ->get();

    if ($projects->isEmpty()) {
        return response()->json([
            'message' => ('project_not_found')
        ], 404);
    }

    $projects = $projects->map(function($project) use ($search) {
        return [
            'id' => $project->id,
            'name_ar' => $project->name_ar,
            'name_en' => $project->name_en,
            'image' => $project->image? asset('storage/'. $project->image): null,
            'status' => $project->status? 'مفعّلة': 'غير مفعّلة',
            'created_at' => $project->created_at,
            'updated_at' => $project->updated_at,
        ];
    });

    return response()->json([
        'projects' => $projects
    ], 200);
}
public function searcSubByName(Request $request)
{
    $this->authorizeAdmin();
    $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $search = $request->name;

    $subprojects =SubProject::
        where(function ($q) use ($search) {
            $q->where('name_ar', 'like', "%$search%")
              ->orWhere('name_en', 'like', "%$search%");
        })
        ->get();

    if ($subprojects ->isEmpty()) {
        return response()->json([
            'message' => ('project_not_found')
        ], 404);
    }

    $subprojects  = $subprojects->map(function($subprojects ) use ($search) {
        return [
           'id' =>  $subprojects->id,
               'name_ar' => $subprojects->name_ar,
               'name_en' => $subprojects->name_en,
               'image' => $subprojects->image ? asset('storage/' . $subprojects->image) : null,
               'location_ar' => $subprojects->location_ar,
               'location_en' =>  $subprojects->location_en,
               'needs_ar' => $subprojects->needs_ar,
               'required_amount' => $subprojects->required_amount,
               'collected_amount' => $subprojects->collected_amount,
               'remaining_amount' =>  $subprojects->remaining_amount,
               'details' =>  $subprojects->formattedDetails,
            'created_at' => $subprojects->created_at,
            'updated_at' => $subprojects->updated_at,
        ];
    });

    return response()->json([
        'projects' =>$subprojects
    ], 200);
}


public function searcCamByName(Request $request)
{
    $this->authorizeAdmin();
    $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $search = $request->name;

    $campaigns =Campaign::
        where(function ($q) use ($search) {
            $q->where('name_ar', 'like', "%$search%")
              ->orWhere('name_en', 'like', "%$search%");
        })
        ->get();

    if ( $campaigns ->isEmpty()) {
        return response()->json([
            'message' => ('campaign_not_found')
        ], 404);
    }

    $campaigns =  $campaigns ->map(function($campaigns) use ($search) {
        return [
            'id' => $campaigns->id,
            'name_en' => $campaigns->name_en,
            'name_ar' =>  $campaigns->name_ar,
            'location_en' => $campaigns->location_en,
            'location_ar' => $campaigns->location_ar,
            'required_amount' => $campaigns->required_amount,
            'remaining_amount' => $campaigns->remaining_amount,
            'collected_amount' => $campaigns->collected,
            'start_date' => $campaigns->startDate,
            'end_date' => $campaigns->endDate,
            'image_url' => $campaigns->image ? asset('storage/' .$campaigns->image) : null,
            'status' => $campaigns->status,
            'created_at' => $campaigns->created_at,
            'updated_at' => $campaigns->updated_at,
        ];
    });

return response()->json([
        'projects' =>$campaigns
    ], 200);
}
public function searcRoleByName(Request $request)
{
    $this->authorizeAdmin();
    $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $search = $request->name;

    $role =Role::where(function ($q) use ($search) {
            $q->where('name_ar', 'like', "%$search%")
            ->orWhere('name_en', 'like', "%$search%");
        })
        ->get();

    if ( $role ->isEmpty()) {
        return response()->json([
            'message' => ('campaign_not_found')
        ], 404);
    }

    $role =  $role ->map(function($role) use ($search) {
        return [
            'id' => $role->id,
            'name_ar' => $role->name_ar,
            'name_en' => $role->name_en,
            'start_end_time' => $role->start_end_time,
            'start_end_date'=>$role->start_end_date,
            'image' => $role->image ? asset('storage/' . $role->image) : null,
            'location_ar' => $role->location_ar,
            'location_en' => $role->location_en,
            'description_en'=>$role->description_en,
            'description_ar'=>$role->description_ar,
            'status' => $role->status 
        ];
    });

    return response()->json([
        'projects' =>$role
    ], 200);
}
    public function campaignsStatistics()
{
    $this->authorizeAdmin();

    // ✅ دالة مساعدة لتنظيم المتبرعين حسب مجموع التبرعات
    $groupDonors = function ($donations) {
        return collect($donations)
            ->filter(fn($d) => $d->donor) // تجاهل التبرعات بدون متبرع معروف
            ->groupBy(fn($d) => $d->donor->id)
            ->map(function ($group, $donorId) {
                $donor = $group->first()->donor;
                return [
                    'donor_id' => $donor->id,
                    'name' => "{$donor->first_name} {$donor->last_name}",
                    'total_donated' => $group->sum('amount'),
                ];
            })->values();
    };

    // ✅ الحملات الطارئة المكتملة فقط
    $completedCampaigns = Campaign::where('status', 1)
        ->with(['donations.donor'])
        ->get()
        ->map(function ($campaign) use ($groupDonors) {
            return [
                'id' => $campaign->id,
                'name' => $campaign->name_ar,
                'total_donated' => $campaign->donations->sum('amount'),
                'donors' => $groupDonors($campaign->donations),
            ];
        });

    // ✅ الحملات التطوعية المكتملة فقط
    $completedRoles = Role::where('status', 1)
        ->with('volunteers')
        ->get()
        ->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name_ar,
                'volunteers_count' => $role->volunteers->count(),
                'volunteers' => $role->volunteers->map(fn($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'email' => $v->email,
                ]),
            ];
        });

    // ✅ إخراج JSON النهائي
    return response()->json([
        'completed_campaigns' => $completedCampaigns,
        'completed_roles' => $completedRoles,
    ]);
}
// تابع لقبول طلب انضمام متطوع لحملة
   
    // تابع لرفض طلب انضمام متطوع لحملة
    


public function acceptVolunteerRequest($roleId, $volunteerId)
    {
        $this->authorizeAdmin();
      
        $role = Role::findOrFail($roleId);
        $volunteer = Volunteer::findOrFail($volunteerId);
   
        $pivotRow = $role->volunteers()->where('volunteer_id', $volunteer->id)->wherePivot('status', 'pending')->first();
    
        if (!$pivotRow) {
            return response()->json(['message' => ('لا يوجد طلب معلق')], 404);
        }
    
        $role->volunteers()->updateExistingPivot($volunteer->id, ['status' => 'accepted']);
    
        return response()->json(['message' => 'تم قبول الطلب']);
    }

    // تابع لرفض طلب انضمام متطوع لحملة
    public function rejectVolunteerRequest($roleId, $volunteerId)
    {    
        $this->authorizeAdmin();     
        $role = Role::findOrFail($roleId);
        $volunteer = Volunteer::findOrFail($volunteerId);
    
        $pivotRow = $role->volunteers()->where('volunteer_id', $volunteer->id)->wherePivot('status', 'pending')->first();
    
        if (!$pivotRow) {
            return response()->json(['message' => 'لا يوجد طلب معلق'], 404);
        }
    
        $role->volunteers()->updateExistingPivot($volunteer->id, ['status' => 'rejected']);
    
        return response()->json(['message' => 'تم رفض الطلب ']);
    }
   

public function getConfirmedSupportRequests()
{
    $this->authorizeAdmin();

    $requests = SupportRequest::where('status', 'confirmed')
        ->with('beneficiary')
        ->get();

    return response()->json([
        'message'  => 'الطلبات المؤكدة',
        'requests' => $requests,
    ], 200);
}

public function getRejectedSupportRequests()
{
    $this->authorizeAdmin();

    $requests = SupportRequest::where('status', 'rejected')
        ->with('beneficiary')
        ->get();

    return response()->json([
        'message'  => 'الطلبات المرفوضة',
        'requests' => $requests,
    ], 200);
}



public function rejectSupportRequest($id)
{
    $this->authorizeAdmin();

    $request = SupportRequest::with('beneficiary')->findOrFail($id);

    if ($request->status !== 'pending') {
        return response()->json([
            'message' => 'هذا الطلب لم يعد معلقاً',
        ], 400);
    }

    $request->status = 'rejected';
    $request->save();

    return response()->json([
        'message'     => 'تم رفض طلب الدعم',
        'request'     => $request,
        'beneficiary' => $request->beneficiary,
    ]);
}
public function getAcceptedBeneficiaries()
{
    $this->authorizeAdmin();

    $beneficiaries = SupportRequest::where('status', 'confirmed')
        ->with('beneficiary') // تحميل بيانات المستفيد
        ->get()
        ->pluck('beneficiary') // استخراج المستفيدين من الطلبات
        ->unique('id') // إزالة التكرار
        ->values(); // ترتيب النتائج

    return response()->json([
        'message' => 'كل معلومات المستفيدين المقبولين',
        'beneficiaries' => $beneficiaries,
    ], 200);
}
public function getPendingRequests()
{
$this->authorizeAdmin();

$requests = SupportRequest::where('status', 'pending')
->with('beneficiary')
->get();

return response()->json([
'message' => 'الطلبات المعلقة',
'requests' => $requests,
],200);
}

public function showAllRolesWithAcceptedVolunteers()
{
    $this->authorizeAdmin();
    $locale = app()->getLocale();

    $roles = Role::with(['volunteers' => function($query) {
        $query->wherePivot('status', 'accepted');
    }])->get();

    $result = $roles->map(function($role) use ($locale) {
        // جمع أسماء المتطوعين
        $volunteerNames = $role->volunteers->map(function($volunteer) {
            return [
                'name' => $volunteer->name,
                'email' => $volunteer->email,
                'phone' => $volunteer->phone,
               'address_en'=> $volunteer->address_en,
               'address_ar'=> $volunteer->address_ar,
               'academic_certificate_en'=> $volunteer->academic_certificate_en,
               'academic_certificate_ar'=> $volunteer->academic_certificate_ar,
               'experiences_en'=> $volunteer->experiences_en,  
               'experiences_ar'=> $volunteer->experiences_ar,  
            ];
        });

        return [
            'role_name' => $role->{'name_' . $locale},
            'volunteers' => $volunteerNames,
        ];
    });
    return response()->json($result);
}
// دالة عامة لجلب الحملات الطارئة المكتملة والمتبرعين
public function completedEmergencyCampaigns()
{
    $this->authorizeAdmin();

    // دالة مساعدة لجمع المتبرعين حسب مجموع التبرعات
    $groupDonors = function ($donations) {
        return collect($donations)
            ->filter(fn($d) => $d->donor) // تجاهل التبرعات بدون متبرع معروف
            ->groupBy(fn($d) => $d->donor->id)
            ->map(function ($group, $donorId) {
                $donor = $group->first()->donor;
                return [
                    'donor_id' => $donor->id,
                    'full_name' => "{$donor->first_name} {$donor->last_name}",
                    'total_donated' => $group->sum('amount'),
                ];
            })->values();
    };

    // ✅ الحملات الطارئة المكتملة فقط
    $completedCampaigns = Campaign::where('status', 1)
        ->with(['donations.donor'])
        ->get()
        ->map(function ($campaign) use ($groupDonors) {
            return [
                'id' => $campaign->id,
                'name' => $campaign->name_ar,
                'total_donated' => $campaign->donations->sum('amount'),
                'donors' => $groupDonors($campaign->donations),
            ];
        });

    // إخراج JSON النهائي
    return response()->json([
        'completed_campaigns' => $completedCampaigns,
    ]);
}
// دالة عامة لجلب الحملات التطوعية المكتملة والمتطوعين
public function completedVolunteerRoles()
{
    $this->authorizeAdmin();

// ✅ الحملات التطوعية المكتملة فقط
    $completedRoles = Role::where('status', 1)
        ->with('volunteers')
        ->get()
        ->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name_ar,
                'volunteers_count' => $role->volunteers->count(),
                'volunteers' => $role->volunteers->map(fn($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'email' => $v->email,
                ]),
            ];
        });

    // إخراج JSON النهائي
    return response()->json([
        'completed_roles' => $completedRoles,
    ]);
}
// دالة عامة لجلب المشاريع المكتملة والمتبرعين
public function completedProjects()
{
    $this->authorizeAdmin();

    // دالة مساعدة لجمع المتبرعين حسب التبرعات
    $groupDonors = function ($donations) {
        return collect($donations)
            ->filter(fn($d) => $d->donor)
            ->groupBy(fn($d) => $d->donor->id)
            ->map(function ($group, $donorId) {
                $donor = $group->first()->donor;
                return [
                    'donor_id' => $donor->id,
                    'full_name' => "{$donor->first_name} {$donor->last_name}",
                    'total_donated' => $group->sum('amount'),
                ];
            })->values();
    };

    // ✅ المشاريع المكتملة
    $completedProjects = Project::where('status', 1)
        ->with(['subProjects.donations.donor'])
        ->get()
        ->map(function ($project) use ($groupDonors) {
            $allDonations = $project->subProjects->flatMap(fn($sub) => $sub->donations);
            return [
                'project_id' => $project->id,
                'project_name' => $project->name_ar,
                'donors' => $groupDonors($allDonations),
            ];
        });

    // إخراج JSON النهائي
    return response()->json([
        'completed_projects' => $completedProjects,
    ]);
}
// دالة عامة لجلب المشاريع الفرعية المكتملة والمتبرعين
public function completedSubProjects()
{
    $this->authorizeAdmin();

    // دالة مساعدة لجمع المتبرعين حسب التبرعات
    $groupDonors = function ($donations) {
        return collect($donations)
            ->filter(fn($d) => $d->donor)
            ->groupBy(fn($d) => $d->donor->id)
            ->map(function ($group, $donorId) {
                $donor = $group->first()->donor;
                return [
                    'donor_id' => $donor->id,
                    'full_name' => "{$donor->first_name} {$donor->last_name}",
                    'total_donated' => $group->sum('amount'),
                ];
            })->values();
    };

    // ✅ المشاريع الفرعية المكتملة
    $completedSubProjects = SubProject::where('status', 1)
        ->with('donations.donor')
        ->get()
        ->map(function ($sub) use ($groupDonors) {
            return [
                'sub_project_id' => $sub->id,
                'sub_project_name' => $sub->name_ar,
                'donors' => $groupDonors($sub->donations),
            ];
        });

    // إخراج JSON النهائي
    return response()->json([
        'completed_sub_projects' => $completedSubProjects,
    ]);
}
public function searchDonorByName(Request $request)
{
    $this->authorizeAdmin();
    $request->validate([
        'name' => 'required|string|max:255', ]);

    $search = $request->name;

    $donor= Donor::where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%$search%");
        })->get();
    if ( $donor->isEmpty()) {
        return response()->json([
            'message' => ('Donor_not_found')
        ], 404);
    }
    $donor =  $donor->map(function( $donor) use ($search) {
        return [
            'id' =>  $donor->id,
            'first_name' =>  $donor->first_name,
            'last_name' =>  $donor->last_name,
            'email' => $donor->email,
             ];});

    return response()->json([
        ' donor' =>  $donor
    ], 200);
}

public function searchVolunteerByName(Request $request)
{
    $this->authorizeAdmin();
    $request->validate([
        'name' => 'required|string|max:255', ]);

    $search = $request->name;

    $voulenteer=Volunteer::where(function ($q) use ($search) {
            $q->where('name', 'like', "%$search%");
        })->get();
    if ( $voulenteer->isEmpty()) {
        return response()->json([
            'message' => ('Volunteer_not_found')
        ], 404);
    }
    $voulenteer = $voulenteer->map(function( $voulenteer) use ($search) {
        return [
            'id' =>$voulenteer->id,
            'name' =>$voulenteer->name,
            'email' =>$voulenteer->email,
            'phone'=>$voulenteer->phone,
            'experiences_ar'=>$voulenteer->experiences_ar,
            'experiences_en'=>$voulenteer->experiences_en,
            'academic_certificate_ar'=>$voulenteer->academic_certificate_ar,
            'academic_certificate_en'=>$voulenteer->academic_certificate_en,
            'address_ar'=>$voulenteer->address_ar,
            'address_en'=>$voulenteer->address_en,
             ];});

    return response()->json([
        'voulenteer' => $voulenteer
    ], 200);
}

public function searchBeneficiaryByName(Request $request)
{
    $this->authorizeAdmin();
    $request->validate([
        'name' => 'required|string|max:255', ]);

    $search = $request->name;

    $beneficiary=Beneficiary::where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%$search%");
        })->get();
    if ( $beneficiary->isEmpty()) {
        return response()->json([
            'message' => ('beneficiary_not_found')
        ], 404);
    }
    $beneficiary =$beneficiary->map(function( $beneficiary) use ($search) {
        return [
            'id' =>$beneficiary->id,
            'name' =>$beneficiary->first_name,
             'father_name'=>$beneficiary->father_name,
             'mother_name'=>$beneficiary->mother_name,
            'email' =>$beneficiary->email,
            'phone'=>$beneficiary->phone,
            'address_ar'=>$beneficiary->address_ar,
            'address_en'=>$beneficiary->address_en,
             ];});

    return response()->json([
        'beneficiary' => $beneficiary
    ], 200);
}

 public function getAdmain()
    {
        $this->authorizeAdmin();
        $Admains = User::where('is_admin', 1)->get()->map(function($Admain)  {
            return [
                'id' =>$Admain->id,
                'name' => $Admain->name,
                'email' => $Admain->email
            ];
        });
       return response()->json([
            'Admains' =>  $Admains
        ], 200);
    }

    public function getPendingVolunteerRequests() {
    // استرجاع الطلبات المعلقة (الحالة pending) مع روابطها للمتطوعين والحملات
    $requests = RoleVolunteer::where('status', 'pending')
        ->with(['volunteers', 'roles'])
        ->get();
    
    $result = $requests->map(function($request) {
        return [
            // ✅ تم إضافة حقول المعرفات
            'role_id' => $request->role_id,
            'volunteer_id' => $request->volunteer_id,
            
            'name'=>$request->volunteers ? $request->volunteers->name : null,
            'volunteer_email' => $request->volunteers ? $request->volunteers->email : null,
            'volunteer_phone' => $request->volunteers ? $request->volunteers->phone : null,
            'address_en' => $request->volunteers ? $request->volunteers->address_en : null,
            'address_ar' => $request->volunteers ? $request->volunteers->address_ar : null,
            'academic_certificate_en'=> $request->volunteers ? $request->volunteers->academic_certificate_en: null,
            'academic_certificate_ar'=> $request->volunteers ? $request->volunteers->academic_certificate_ar: null,
            'experiences_en'=> $request->volunteers ? $request->volunteers->experiences_en: null,  
            'experiences_ar'=> $request->volunteers ? $request->volunteers->experiences_ar: null,
            'role_name' => $request->roles ? $request->roles->name_ar : null,
            'request_status' => $request->status,
        ];
    });

    return response()->json($result, 200);
}
public function getCounts()
{
 return [
 'donors' => Donor::count(),
 'volunteers' => Volunteer::count(),
 'beneficiaries' => Beneficiary::count(),
 'donations_count' => Donation::count(),
 'donations_total' => Donation::sum('amount'),
 ];
}

public function getTopDonors()
{
 $donors = Donation::with('donor')
 ->get()
 ->groupBy(fn($donation) => $donation->donor->id)
 ->map(function ($group) {
 $donor = $group->first()->donor;
 return [
 'donor_id' => $donor->id,
 'full_name' => "{$donor->first_name} {$donor->last_name}",
 'total_donated' => $group->sum('amount'),
 ];
 })
 ->sortByDesc('total_donated')
 ->values()
 ->take(10);

 return $donors;
}

public function getPendingRequestsCount()
{
 return Request::where('status', 'pending')->count();
}
}

