<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    // إضافة مشروع جديد
    public function store(Request $request)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
             'status' => 'nullable|in:0,1'
            
        ]);

        $imagepath = null;
        if ($request->hasFile('image')) {
            $imagepath = $request->file('image')->store('profile_pictures', 'public');
        }

        $project = Project::create([
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
            'image' => $imagepath,
            'status' => $request->status ?? 1, // إذا لم يُرسل، نعطيها 1
        ]);

        if ($project->image) {
            $project->image = asset('storage/' . $project->image);
        }

        $locale = app()->getLocale();
        $name = $locale === 'ar' ? $project->name_ar : $project->name_en;

        $responseProject = [
            'id' => $project->id,
            'name' => $name,
            'image' => $project->image,
               'status' => $project->status, // إضافة الحالة في الرد
            'created_at' => $project->created_at,
            'updated_at' => $project->updated_at,
        ];

        return response()->json([
            'message' => __('messages.project_added_successfully'),
            'project' => $responseProject
        ], 201);
    }

    // جلب جميع المشاريع
 public function getProject()
{
    $locale = app()->getLocale();
    $column = $locale === 'ar' ? 'name_ar' : 'name_en';

    $projects = Project::where('status', true)->get()->map(function($project) use ($column) {
        return [
            'id' => $project->id,
            'name' => $project->$column,
            'image' => $project->image ? asset('storage/' . $project->image) : null,
            'created_at' => $project->created_at,
            'updated_at' => $project->updated_at,
        ];
    });

    return response()->json([
        'projects' => $projects
    ], 200);
}

    // البحث عن مشروع بالاسم حسب اللغة
    public function searchProjectByName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $locale = app()->getLocale();
        $column = $locale === 'ar' ? 'name_ar' : 'name_en';

        $projects = Project::where($column, 'like', '%' . $request->name . '%')->get();

        if ($projects->isEmpty()) {
            return response()->json([
                'message' => __('messages.project_not_found')
            ], 404);
        }

        $projects = $projects->map(function($project) use ($column) {
            return [
                'id' => $project->id,
                'name' => $project->$column,
                'image' => $project->image ? asset('storage/' . $project->image) : null,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
            ];
        });

        return response()->json([
            'projects' => $projects
        ], 200);
    }


// تحديث مشروع موجود
public function update(Request $request, $id)
{
    $request->validate([
        'name_en' => 'nullable|string|max:255',
        'name_ar' => 'nullable|string|max:255',
        'image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048'
    ]);

    $project = Project::find($id);

    if (!$project) {
        return response()->json([
            'message' => 'messages.project_not_found'
        ], 404);
    }

    if ($request->hasFile('image')) {
        // احذف الصورة القديمة إذا كانت موجودة
        if ($project->image) {
            Storage::disk('public')->delete($project->image);
        }
        $project->image = $request->file('image')->store('profile_pictures', 'public');
    }

    if ($request->name_en) {
        $project->name_en = $request->name_en;
    }

    if ($request->name_ar) {
        $project->name_ar = $request->name_ar;
    }

    $project->save();

    $locale = app()->getLocale();
    $name = $locale === 'ar' ? $project->name_ar : $project->name_en;

    return response()->json([
        'message' => __('messages.project_updated_successfully'),
        'project' => [
            'id' => $project->id,
            'name' => $name,
            'image' => $project->image ? asset('storage/' . $project->image) : null,
            'created_at' => $project->created_at,
            'updated_at' => $project->updated_at,
        ]
    ], 200);
}


}
