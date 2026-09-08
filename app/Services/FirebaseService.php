<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
    protected $projectId;
    protected $accessToken;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id');
        
        if (!$this->projectId) {
            Log::error('Firebase Project ID not configured');
            throw new \Exception('Firebase Project ID not configured');
        }
        
        Log::info('FirebaseService initialized with project ID: ' . $this->projectId);
    }

    /**
     * الحصول على Access Token من Google Service Account
     */
    private function getAccessToken()
    {
        try {
            $credentialsPath = config('services.firebase.credentials');
            
            if (!file_exists($credentialsPath)) {
                Log::error('Firebase credentials file not found: ' . $credentialsPath);
                throw new \Exception('Firebase credentials file not found');
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);
            
            if (!$credentials) {
                throw new \Exception('Invalid credentials file');
            }

            // إنشاء JWT token
            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT'
            ];

            $payload = [
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => time() + 3600,
                'iat' => time()
            ];

            $jwt = $this->createJWT($header, $payload, $credentials['private_key']);
            
            // الحصول على access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'];
            } else {
                Log::error('Failed to get access token: ' . $response->body());
                throw new \Exception('Failed to get access token');
            }
        } catch (\Exception $e) {
            Log::error('Error getting access token: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * إنشاء JWT token
     */
    private function createJWT($header, $payload, $privateKey)
    {
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $data = $headerEncoded . '.' . $payloadEncoded;
        $signature = '';
        
        openssl_sign($data, $signature, $privateKey, 'SHA256');
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $data . '.' . $signatureEncoded;
    }

    /**
     * Base64 URL encoding
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function sendNotification($deviceToken, $title, $body, $data = [])
    {
        if (!$deviceToken) {
            Log::error('Device token is required');
            return ['error' => 'Device token is required'];
        }

        Log::info('Sending FCM notification', [
            'device_token' => substr($deviceToken, 0, 20) . '...',
            'title' => $title,
            'body' => $body,
            'data' => $data
        ]);

        try {
            $accessToken = $this->getAccessToken();
            
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $message = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body
                    ],
                    'data' => $data,
                    'android' => [
                        'notification' => [
                            'channel_id' => 'default_channel',
                            'sound' => 'default',
                            'priority' => 'high'
                        ],
                        'priority' => 'high'
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1
                            ]
                        ]
                    ]
                ]
            ];

            Log::info('FCM v1 Request', [
                'url' => $url,
                'payload' => $message
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->post($url, $message);

            $responseData = $response->json();
            
            Log::info('FCM v1 Response', [
                'status_code' => $response->status(),
                'response' => $responseData
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $responseData['name'] ?? null,
                    'response' => $responseData
                ];
            } else {
                Log::error('FCM v1 Error', [
                    'status_code' => $response->status(),
                    'response' => $responseData
                ]);
                return [
                    'error' => 'HTTP Error: ' . $response->status(),
                    'details' => $responseData
                ];
            }
        } catch (\Exception $e) {
            Log::error('FCM v1 Exception: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function sendToMultipleDevices($deviceTokens, $title, $body, $data = [])
    {
        if (empty($deviceTokens)) {
            Log::error('Device tokens array is empty');
            return ['error' => 'Device tokens array is empty'];
        }

        Log::info('Sending FCM notification to multiple devices', [
            'tokens_count' => count($deviceTokens),
            'title' => $title,
            'body' => $body
        ]);

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($deviceTokens as $token) {
            $result = $this->sendNotification($token, $title, $body, $data);
            $results[] = [
                'token' => substr($token, 0, 20) . '...',
                'result' => $result
            ];

            if (isset($result['success'])) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        Log::info('FCM v1 Multiple Results', [
            'total' => count($deviceTokens),
            'success' => $successCount,
            'failure' => $failureCount,
            'results' => $results
        ]);

        return [
            'total' => count($deviceTokens),
            'success' => $successCount,
            'failure' => $failureCount,
            'results' => $results
        ];
    }

    /**
     * اختبار الاتصال بـ Firebase
     */
    public function testConnection()
    {
        try {
            $accessToken = $this->getAccessToken();
            
            return [
                'success' => true,
                'message' => 'Firebase connection successful',
                'project_id' => $this->projectId,
                'access_token' => substr($accessToken, 0, 20) . '...'
            ];
        } catch (\Exception $e) {
            Log::error('FCM v1 Connection Test Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * إرسال إشعار لموضوع (Topic)
     */
    public function sendToTopic($topic, $title, $body, $data = [])
    {
        try {
            $accessToken = $this->getAccessToken();
            
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $message = [
                'message' => [
                    'topic' => $topic,
                    'notification' => [
                        'title' => $title,
                        'body' => $body
                    ],
                    'data' => $data,
                    'android' => [
                        'notification' => [
                            'channel_id' => 'default_channel',
                            'sound' => 'default',
                            'priority' => 'high'
                        ]
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->post($url, $message);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('name'),
                    'topic' => $topic
                ];
            } else {
                return [
                    'error' => 'HTTP Error: ' . $response->status(),
                    'details' => $response->json()
                ];
            }
        } catch (\Exception $e) {
            Log::error('FCM v1 Topic Error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
}
