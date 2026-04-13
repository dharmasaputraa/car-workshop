<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->onQueue('emails');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to the ' . config('app.name', 'Laravel') . "!")
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('We are so excited to have you join our community.')
            ->line('Get ready to discover the best toys, exclusive discounts, and early access to our new arrivals.')
            ->action('Start Shopping', url('/'))
            ->line('If you have any questions, our support team is always here to help.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Welcome! Your account has been successfully created.',
            'user_id' => $notifiable->id
        ];
    }
}
