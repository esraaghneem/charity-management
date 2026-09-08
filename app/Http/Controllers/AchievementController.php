<?php


namespace App\Http\Controllers;

use App\Models\Achievement;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    // ✅ جلب الإنجازات من الخارج (عنوان + صورة فقط)
    public function index()
    {
        $locale = app()->getLocale();

        $achievements = Achievement::select('id', "title_$locale as title", 'image')->get();

        // تعديل رابط الصورة
        $achievements->transform(function ($item) {
            $item->image = $item->image ? asset('storage/' . $item->image) : null;
            return $item;
        });

        return response()->json([
            'achievements' => $achievements,
        ]);
    }

    // ✅ عند النقر على إنجاز: يعرض صورة + اسم + وصف + موقع
    public function show($id)
    {
        $locale = app()->getLocale();

        $achievement = Achievement::select(
                'id',
                "title_$locale as title",
                "description_$locale as description",
                "location_$locale as location",
                'image'
            )->findOrFail($id);

        $achievement->image = $achievement->image ? asset('storage/' . $achievement->image) : null;

       return response()->json(["AchievDetails"=>$achievement], 200);
    }

    // ✅ إضافة إنجاز جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title_en' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'location_en' => 'required|string|max:255',
            'location_ar' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'description_en' => 'required|string',
            'description_ar' => 'required|string',
        ]);

        $imagePath = $request->file('image')->store('achievements', 'public');

        $achievement = Achievement::create([
            'title_en' => $validated['title_en'],
            'title_ar' => $validated['title_ar'],
            'location_en' => $validated['location_en'],
            'location_ar' => $validated['location_ar'],
            'image' => $imagePath,
            'description_en' => $validated['description_en'],
            'description_ar' => $validated['description_ar'],
        ]);

        return response()->json([
            'message' => __('messages.achievement_added_successfully'),
            'achievement' => [
                'id' => $achievement->id,
                'title' => $achievement->{'title_' . app()->getLocale()},
                'location' => $achievement->{'location_' . app()->getLocale()},
                'image' => asset('storage/' . $achievement->image),
                'description' => $achievement->{'description_' . app()->getLocale()},
            ]
        ], 201);
    }
}