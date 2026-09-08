<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function recharge(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);
        $user = $request->user(); // نفترض إنه المتبرع

        $user->wallet_balance += $request->amount;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تمت تعبئة المحفظة بنجاح!',
            'new_balance' => $user->wallet_balance
        ]);
    }

    public function balance(Request $request)
    {
        return response()->json([
            'wallet_balance' => $request->user()->wallet_balance
        ]);
    }
}