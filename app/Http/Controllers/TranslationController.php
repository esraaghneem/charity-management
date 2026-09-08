<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\TranslatorHelper;

class TranslationController extends Controller {
    public function translate(Request $request) {
        $request->validate([
            'text' => 'required|string',
            'lang' => 'required|string' // تحديد لغة الترجمة
        ]);

        $translatedText = TranslatorHelper::translate($request->input('text'), $request->input('lang'));

        return response()->json([
            'original_text' => $request->input('text'),
            'translated_text' => $translatedText
        ]);
    }
}
