<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class GeneralNotification extends Notification implements ShouldQueue {
    use Queueable;

    protected $message;
    protected $type;

    public function __construct($message, $type) {
        $this->message = $message;
        $this->type = $type;
    }

    public function via($notifiable) {
        return ['mail', 'broadcast', 'database'];
    }

    public function toMail($notifiable) {
        return (new MailMessage)
                    ->subject('إشعار جديد')
                    ->line($this->message);
    }

    public function toBroadcast($notifiable) {
        return new BroadcastMessage([
            'message' => $this->message,
            'type' => $this->type
        ]);
    }

   public function toDatabase($notifiable)
{
    return [
        'message' => $this->message,
        'type' => $this->type,
        'created_at' => now()->toDateTimeString(),
    ];
}

}
