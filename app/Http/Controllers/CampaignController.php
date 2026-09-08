<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    // إضافة حملة جديدة
    public function storeCampaign(Request $request)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'location_en' => 'required|string|max:255',
            'location_ar' => 'required|string|max:255',
            'required_amount' => 'required|numeric|min:1',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('profile_pictures', 'public');
        }

        $campaign = Campaign::create([
            'name_en' => $validated['name_en'],
            'name_ar' => $validated['name_ar'],
            'image' => $imagePath,
            'location_en' => $validated['location_en'],
            'location_ar' => $validated['location_ar'],
            'required_amount' => $validated['required_amount'],
            'status' => 1, // 1 = نشطة
            'startDate' => $validated['startDate'],
            'endDate' => $validated['endDate'],
        ]);

        $locale = app()->getLocale();

        return response()->json([
            'message' => __('messages.campaign_added_successfully'),
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->{'name_' . $locale},
                'image' => $campaign->image ? asset('storage/' . $campaign->image) : null,
                'location' => $campaign->{'location_' . $locale},
                'required_amount' => $campaign->required_amount,
                'collected_amount' => $campaign->collected_amount,
                'remaining_amount' => $campaign->required_amount - $campaign->collected_amount,
                'status' => $campaign->status, // يرجع 1
                'startDate' => $campaign->startDate,
                'endDate' => $campaign->endDate,
            ]
        ], 201);
    }

    // عرض جميع الحملات النشطة
    public function index()
    {
        $locale = app()->getLocale();
        $campaigns = Campaign::where('status', 1) // فقط الحملات النشطة
            ->get()
            ->map(function ($campaign) use ($locale) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->{'name_' . $locale},
                    'image' => $campaign->image ? asset('storage/' . $campaign->image) : null,
                    'location' => $campaign->{'location_' . $locale},
                    'status' => $campaign->status, // رح يكون دائمًا 1
                ];
            });

        return response()->json(['campaigns' => $campaigns], 200);
    }

    // عرض تفاصيل حملة واحدة
    public function getCampaign($id)
    {
        $locale = app()->getLocale();

        $campaign = Campaign::where('id', $id)
            ->where('status', 1) // فقط النشطة
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found or inactive'
            ], 404);
        }

        $result = [
            'id' => $campaign->id,
            'name' => $campaign->{'name_' . $locale},
            'image' => $campaign->image ? asset('storage/' . $campaign->image) : null,
            'location' => $campaign->{'location_' . $locale},
            'required_amount' => $campaign->required_amount,
            'collected_amount' => $campaign->collected_amount,
            'remaining_amount' => $campaign->required_amount - $campaign->collected_amount,
            'status' => $campaign->status, // 1 فقط
            'startDate' => $campaign->startDate,
            'endDate' => $campaign->endDate,
        ];

        return response()->json(["camDetails" => $result], 200);
    }
}
