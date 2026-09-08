<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    // إضافة حملة تطوعية جديدة
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'start_end_time' => 'required|string|max:255',
            'start_end_date' => 'required|string|max:255',
            'description_en' => 'required|string|max:255',
            'description_ar' => 'required|string|max:255',
            'location_en' => 'required|string|max:255',
            'location_ar' => 'required|string|max:255',
            'status' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('profile_pictures', 'public');
        }

        $role = Role::create([
            'name_en' => $validated['name_en'],
            'name_ar' => $validated['name_ar'],
            'start_end_time' => $validated['start_end_time'],
            'start_end_date' => $validated['start_end_date'],
            'image' => $imagePath,
            'location_en' => $validated['location_en'],
            'location_ar' => $validated['location_ar'],
            'description_en' => $validated['description_en'],
            'description_ar' => $validated['description_ar'],
            'status' => $validated['status'],
        ]);

        $locale = app()->getLocale();

        return response()->json([
            'message' => __('messages.role_added_successfully'),
            'role' => [
                'id' => $role->id,
                'name' => $role->{'name_' . $locale},
                'start_end_time' => $role->start_end_time,
                'start_end_date' => $role->start_end_date,
                'location' => $role->{'location_' . $locale},
                'description' => $role->{'description_' . $locale},
                'image' => $role->image ? asset('storage/' . $role->image) : null,
                'status' => $role->status
            ],
        ], 201);
    }

    // عرض جميع الحملات التطوعية
    public function index()
    {
        $locale = app()->getLocale();
        $roles = Role::with('volunteers')->where('status', 1)->get()->map(function ($role) use ($locale) {
            return [
                'id' => $role->id,
                'name' => $role->{'name_' . $locale},
                'image' => $role->image ? asset('storage/' . $role->image) : null,
                'location' => $role->{'location_' . $locale},
                 'status' => $role->status,
            ];
        });

        return response()->json($roles, 200);
    }

    // عرض حملة تطوعية واحدة
    public function show($id)
    {
        $role = Role::with('volunteers')->findOrFail($id);
        $locale = app()->getLocale();
   if ($role->status == 0) {
        return response()->json([
            'message' => __('messages.role_inactive')
        ], 404);
    }
        return response()->json([
            'role' => [
                'id' => $role->id,
                'name' => $role->{'name_' . $locale},
                'start_end_time' => $role->start_end_time,
                'start_end_date' => $role->start_end_date,
                'image' => $role->image ? asset('storage/' . $role->image) : null,
                'location' => $role->{'location_' . $locale},
                'description' => $role->{'description_' . $locale},
             'status' => $role->status,
            ],
        ], 200);
    }

    // تحديث حملة تطوعية
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name_en' => 'sometimes|required|string|max:255',
            'name_ar' => 'sometimes|required|string|max:255',
            'start_end_time' => 'nullable|string|max:255',
            'start_end_date' => 'nullable|string|max:255',
            'location_en' => 'sometimes|required|string|max:255',
            'location_ar' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|boolean',
            'description_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $role = Role::findOrFail($id);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('profile_pictures', 'public');
        }

        $role->update($validated);

        $locale = app()->getLocale();

        return response()->json([
            'message' => __('messages.role_updated_successfully'),
            'role' => [
                'id' => $role->id,
                'name' => $role->{'name_' . $locale},
                'start_end_time' => $role->start_end_time,
                'start_end_date' => $role->start_end_date,
                'image' => $role->image ? asset('storage/' . $role->image) : null,
                'location' => $role->{'location_' . $locale},
                'description' => $role->{'description_' . $locale},
                'status' => $role->status ? __('messages.active') : __('messages.inactive'),
            ],
        ]);
    }

    // حذف حملة تطوعية
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->volunteers()->detach();
        $role->delete();

        return response()->json([
            'message' => __('messages.role_deleted_successfully')
        ]);
    }
}
