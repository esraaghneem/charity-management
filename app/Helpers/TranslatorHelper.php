<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslatorHelper {
    public static function translate($text, $targetLang = 'en') {
        try {
            $apiKey = env('LIBRETRANSLATE_API_KEY'); // جلب مفتاح API من .env

            $response = Http::post('https://libretranslate.com/translate', [
                'q' => $text,
                'source' => 'auto',
                'target' => $targetLang,
                'format' => 'text',
                'api_key' => $apiKey
            ]);

            // التأكد من أن الطلب ناجح
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['translatedText']) && !empty($data['translatedText'])) {
                    return $data['translatedText']; // الترجمة ناجحة
                }
            }

            // تسجيل الخطأ إذا لم تكن الترجمة ناجحة
            Log::error('Translation API error: ' . $response->body());
            return $text; // إرجاع النص الأصلي عند الفشل

        } catch (\Exception $e) {
            Log::error('Translation Exception: ' . $e->getMessage());
            return $text; // تجنب تعطل التطبيق عند حدوث خطأ
        }
    }
}
