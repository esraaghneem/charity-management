<?php

namespace App\Services;

use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class NotificationService
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * إرسال إشعار إلى جهاز واحد عبر التوكن
     */
    public function sendToDevice(string $deviceToken, string $title, string $body): void
    {
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification(Notification::create($title, $body))
            ->withData(['click_action' => 'FLUTTER_NOTIFICATION_CLICK'])
            ->withAndroidConfig([
                'priority' => 'high',
            ]);

        $this->messaging->send($message);
    }

    /**
     * إرسال إشعار إلى جميع الأجهزة المشتركين بموضوع معين
     */
    public function sendToTopic(string $topic, string $title, string $body): void
    {
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(Notification::create($title, $body))
            ->withData(['click_action' => 'FLUTTER_NOTIFICATION_CLICK'])
            ->withAndroidConfig([
                'priority' => 'high',
            ]);

        $this->messaging->send($message);
    }
}
