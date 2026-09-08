<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FcmNotificationMessage extends Notification
{
    use Queueable;

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // القنوات اللي رح يستخدمها الإشعار
    public function via($notifiable)
    {
        return ['database'];
    }

    // البيانات اللي بتتحفظ في جدول notifications
    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->data['title'],
            'body'  => $this->data['body'],
            'status'=> $this->data['status'] ?? null,
            'extra' => $this->data['extra'] ?? null,
        ];
    }
}
