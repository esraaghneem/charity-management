<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Campaign;
use App\Models\SubProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $request->validate([
            'cart_id' => 'nullable|exists:carts,id',
            'donor_id' => 'nullable|exists:donors,id',
            'sub_project_id' => 'nullable|exists:sub_projects,id',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($request->filled('cart_id')) {
            $cart = Cart::findOrFail($request->cart_id);
        } else {
            $cart = Cart::create([
                'donor_id' => Auth::check() ? Auth::id() : null,
            ]);
        }

        if (Auth::check() && !$cart->donor_id) {
            $cart->donor_id = Auth::id();
            $cart->save();
        }

       if (!$request->sub_project_id && !$request->campaign_id) {
        return response()->json([
            'success' => false,
            'message' => ('messages.project_or_campaign_required')
        ], 400);
         }

        if ($request->filled('sub_project_id')) {
            $subProject = SubProject::findOrFail($request->sub_project_id);
            $remainingAmount = $subProject->remaining_amount;

            if ($remainingAmount == 0) {
                 return response()->json([
                    'message' => ('messages.donation_completed'),
                    'cart_id' => $cart->id
                ], 400);
            } elseif ($request->amount > $remainingAmount) {
                return response()->json([
                    'message' => ('messages.amount_exceeds_remaining'),
                    'cart_id' => $cart->id
                ], 400);
            }

        /*    $subProject->remaining_amount -= $request->amount;
            $subProject->collected_amount += $request->amount;
            $subProject->save();
*/
            $cart->subProjects()->syncWithoutDetaching([
                $request->sub_project_id => ['amount' => $request->amount]
            ]);

            return response()->json([
                'message' => ('messages.added_to_cart_sub_project'),
                'cart_id' => $cart->id
            ]);

        } elseif ($request->filled('campaign_id')) {
            $campaign = Campaign::findOrFail($request->campaign_id);
            $remainingAmount = $campaign->remaining_amount;

            if ($remainingAmount == 0) {
                 return response()->json([
                    'message' => ('messages.donation_completed'),
                    'cart_id' => $cart->id
                ], 400);
            } elseif ($request->amount > $remainingAmount) {
                return response()->json([
                    'message' => ('messages.amount_exceeds_remaining'),
                    'cart_id' => $cart->id
                ], 400);
            }

            $cart->campaigns()->syncWithoutDetaching([
                $request->campaign_id => ['amount' => $request->amount]
            ]);

            return response()->json([
                'message' => ('messages.added_to_cart_campaign'),
                'cart_id' => $cart->id
            ]);
        } else {
            return response()->json(['message' => ('messages.no_target_selected'),
                'cart_id' => $cart->id
            ], 400);
        }
    }

    public function show($cartId)
    {
        $cart = Cart::with([
            'subProjects:id,name_en,name_ar,image',
            'campaigns:id,name_en,name_ar,image'
        ])->findOrFail($cartId);

        $locale = app()->getLocale();

        $totalSubProjectsAmount = $cart->subProjects->reduce(function ($carry, $sp) {
            return $carry + ($sp->pivot->amount ?? 0);
        }, 0);

        $totalCampaignsAmount = $cart->campaigns->reduce(function ($carry, $c) {
            return $carry + ($c->pivot->amount ?? 0);
        }, 0);

        $totalAmount = $totalSubProjectsAmount + $totalCampaignsAmount;

        return response()->json(['cart'=>[
            'cart_id'=>$cartId,
            'sub_projects' => $cart->subProjects->map(fn($sp) => [
                'id' => $sp->id,
                'name' => $sp->{'name_' . $locale},
                'image' => asset('storage/' . $sp->image),
                'amount' => $sp->pivot->amount,
            ]),
            'campaigns' => $cart->campaigns->map(fn($c) => [
                   'cart_id'=>$cartId,
                'id' => $c->id,
                'name' => $c->{'name_' .$locale},
                'image' =>  asset('storage/' . $c->image),
                'amount' => $c->pivot->amount,
            ]),
            'total_amount' => $totalAmount,]
        ]);
    }

    public function removeFromCart(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'sub_project_id' => 'nullable|exists:sub_projects,id',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ]);

        $cart = Cart::findOrFail($request->cart_id);

        if ($request->filled('sub_project_id')) {
            $subProject = SubProject::findOrFail($request->sub_project_id);
            $donationAmount = $cart->subProjects()->where('sub_project_id', $subProject->id)->first()->pivot->amount;
         $cart->subProjects()->detach($subProject->id);

            return response()->json([
                'message' => ('messages.removed_sub_project'),
                'cart_id' => $cart->id
            ]);
        } elseif ($request->filled('campaign_id')) {
            $campaign = Campaign::findOrFail($request->campaign_id);
            $donationAmount = $cart->campaigns()->where('campaign_id', $campaign->id)->first()->pivot->amount;

  
            $cart->campaigns()->detach($campaign->id);

            return response()->json([
                'message' => ('messages.removed_campaign'),
                'cart_id' => $cart->id
            ]);
        } else {
            return response()->json([
                'message' => ('messages.no_target_selected'),
                'cart_id' => $cart->id
            ], 400);
        }
    }

    public function updateCart(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'sub_project_id' => 'nullable|exists:sub_projects,id',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $cart = Cart::findOrFail($request->cart_id);

        if ($request->filled('sub_project_id')) {
            $subProject = SubProject::findOrFail($request->sub_project_id);
            $currentDonation = $cart->subProjects()->where('sub_project_id', $subProject->id)->first()->pivot->amount;

          
            $newDonation = $request->amount;if ( $newDonation >  $subProject->remaining_amount) {
                return response()->json([
                    'message' => ('messages.amount_exceeds_remaining'),
                    'cart_id' => $cart->id
                ], 400);
            }

            $cart->subProjects()->updateExistingPivot($subProject->id, ['amount' => $newDonation]);

            return response()->json([
                'message' => ('messages.updated_sub_project'),
                'cart_id' => $cart->id,
                'new_amount' => $newDonation
            ]);
        } elseif ($request->filled('campaign_id')) {
            $campaign = Campaign::findOrFail($request->campaign_id);
            $currentDonation = $cart->campaigns()->where('campaign_id', $campaign->id)->first()->pivot->amount;

            $newDonation = $request->amount;

            if ( $newDonation > $campaign->remaining_amount) {
                return response()->json([
                    'message' => ('messages.amount_exceeds_remaining'),
                    'cart_id' => $cart->id
                ], 400);
            }
            $cart->campaigns()->updateExistingPivot($campaign->id, ['amount' => $newDonation]);

            return response()->json([
                'message' => ('messages.updated_campaign'),
                'cart_id' => $cart->id,
                'new_amount' => $newDonation
            ]);
        } else {
            return response()->json([
                'message' => __('messages.no_target_selected'),
                'cart_id' => $cart->id
            ], 400);
        }
    }
}