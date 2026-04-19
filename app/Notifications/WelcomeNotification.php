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
            ->subject('Welcome to ' . config('app.name', 'Car Workshop') . '!')
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('Thank you for joining our car workshop.')
            ->line('We are ready to assist you with a wide range of services including regular maintenance, engine repair, vehicle inspection, and more.')
            ->line('Enjoy exclusive offers and priority booking as one of our valued members.')
            ->action('Book a Service Now', config('app.frontend_url', 'http://localhost:3000'))
            ->line('If you have any questions, our support team is always here to help.')
            ->line('Thank you for trusting us with your vehicle.');
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
