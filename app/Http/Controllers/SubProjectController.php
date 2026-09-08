<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\SubProject;
use App\Models\SubProjectDetail;

class SubProjectController extends Controller
{
    // إنشاء مشروع فرعي
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'location_en' => 'required|string|max:255',
            'location_ar' => 'required|string|max:255',
            'needs_en' => 'nullable|string|max:255',
            'needs_ar' => 'nullable|string|max:255',
            'required_amount' => 'required|numeric|min:1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'details' => 'nullable|array'
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('profile_pictures', 'public')
            : null;

        $subProject = SubProject::create([
            'project_id' => $validated['project_id'],
            'name_en' => $validated['name_en'],
            'name_ar' => $validated['name_ar'],
            'location_en' => $validated['location_en'],
            'location_ar' => $validated['location_ar'],
            'needs_en' => $validated['needs_en'],
            'needs_ar' => $validated['needs_ar'],
            'required_amount' => $validated['required_amount'],
            'collected_amount'=>0,
            'remaining_amount' => $validated['required_amount'], 

            'image' => $imagePath,
            'status'=>1
        ]);

        // حفظ التفاصيل مع دعم الترجمة (إذا موجودة)
        $formattedDetails = [];
        foreach ($validated['details'] ?? [] as $detail) {
            SubProjectDetail::create([
                'sub_project_id' => $subProject->id,
                'key_en' => $detail['key_en'],
                'key_ar' => $detail['key_ar'],
                'value_en' => $detail['value_en'],
                'value_ar' => $detail['value_ar'],
            ]);

            $formattedDetails[] = [
                'key' => app()->getLocale() === 'ar' ? $detail['key_ar'] : $detail['key_en'],
                'value' => app()->getLocale() === 'ar' ? $detail['value_ar'] : $detail['value_en'],
            ];
        }

        $locale = app()->getLocale();

        return response()->json([
            'message' => __('messages.sub_project_added_successfully'),
            'subProject' => [
                'id' => $subProject->id,
                'name' => $subProject->{'name_' . $locale},
                'image' => $subProject->image ? asset('storage/' . $subProject->image) : null,
                'location' => $subProject->{'location_' . $locale},
                'needs' => $subProject->{'needs_' . $locale},
                'required_amount' => $subProject->required_amount,
                'collected_amount'=>0,
                'remaining_amount' => $subProject->required_amount, // صححت هان
                'details' => $formattedDetails
            ]
        ], 201);
    }

    // جلب المشاريع الفرعية حسب المشروع الرئيسي
    public function getProjects($id)
    {
        $locale = app()->getLocale();

        $subProjects = SubProject::where('project_id', $id)->where('status',1)
            ->select("name_$locale as name", 'image', 'required_amount',"project_id","id")
            ->get();

        if ($subProjects->isEmpty()) {
            return response()->json([
                'message' => __('messages.no_sub_projects_found')
            ], 404);
        }

        // تنسيق رابط الصورة الكامل
        $subProjects->transform(function ($project) {
            $project->image = $project->image ? asset('storage/' . $project->image) : null;
            return $project;
        });

        return response()->json(['subProjects' => $subProjects], 200);
    }

    // جلب التفاصيل لمشروع فرعي معين
    public function showInfo($id, $project_id)
    {
        $locale = app()->getLocale();

        $subProject = SubProject::with('details')
            ->where('id', $id)
            ->where('project_id', $project_id)->where('status',1)
            ->firstOrFail();

        $formattedDetails = $subProject->details->map(function ($detail) use ($locale) {
            return [
                'key' => $detail->{'key_' . $locale},
                'value' => $detail->{'value_' . $locale},
            ];
        });

        return response()->json([
            'subProject' => [
                'id' => $subProject->id,
                'name' => $subProject->{'name_' . $locale},
                'image' => $subProject->image ? asset('storage/' . $subProject->image) : null,
                'location' => $subProject->{'location_' . $locale},
                'needs' => $subProject->{'needs_' . $locale},
                'required_amount' => $subProject->required_amount,
                'collected_amount' => $subProject->collected_amount,
                'remaining_amount' => $subProject->remaining_amount,
                'status'=>1,
                'details' => $formattedDetails,
            ]
        ], 200);

    }
    public function update(Request $request,$id)
{
    $validated = $request->validate([
        'name_en' => 'sometimes|required|string|max:255',
        'name_ar' => 'sometimes|required|string|max:255',
        'location_en' => 'sometimes|required|string|max:255',
        'location_ar' => 'sometimes|required|string|max:255',
        'needs_en' => 'nullable|sometimes|string|max:255',
        'needs_ar' => 'nullable|sometimes||string|max:255',
        'required_amount' => 'sometimes|required|numeric|min:1',
        'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'details' => 'nullable|array'
    ]);

    $subProject = SubProject::findOrFail($id);

    // تحديث الصورة إذا تم رفع صورة جديدة
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('profile_pictures', 'public');
        $subProject->image = $imagePath;
    }

    // تحديث باقي الحقول
    foreach ($validated as $key => $value) {
        if ($key!== 'details' && $key!== 'image') {
            $subProject->$key = $value;
        }
    }
    $subProject->save();

    // تحديث التفاصيل
    if (isset($validated['details'])) {
        // حذف التفاصيل القديمة
        $subProject->details()->delete();
        // إضافة التفاصيل الجديدة
        foreach ($validated['details'] as $detail) {
            SubProjectDetail::create([
                'sub_project_id' => $subProject->id,
                'key_en' => $detail['key_en'],
                'key_ar' => $detail['key_ar'],
                'value_en' => $detail['value_en'],
                'value_ar' => $detail['value_ar'],
            ]);
        }
    }

    $locale = app()->getLocale();
    $formattedDetails = $subProject->details->map(function ($detail) use ($locale) {
        return [
            'key' => $detail->{'key_'. $locale},
            'value' => $detail->{'value_'. $locale},
        ];
    });

    return response()->json([
        'message' => __('messages.sub_project_updated_successfully'),
        'subProject' => [
            'id' => $subProject->id,
            'name' => $subProject->{'name_'. $locale},
            'image' => $subProject->image? asset('storage/'. $subProject->image): null,
            'location' => $subProject->{'location_'. $locale},
            'needs' => $subProject->{'needs_'. $locale},
            'required_amount' => $subProject->required_amount,
            'collected_amount' => $subProject->collected_amount,
            'remaining_amount' => $subProject->remaining_amount,
            'details' => $formattedDetails,
        ]
    ]);
}









}
