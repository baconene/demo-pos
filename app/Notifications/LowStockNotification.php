<?php

namespace App\Notifications;

use App\Models\Ingredient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ingredient $ingredient) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Low Stock Alert: {$this->ingredient->name}")
            ->line("The ingredient '{$this->ingredient->name}' is running low on stock.")
            ->line("Current quantity: {$this->ingredient->current_quantity} {$this->ingredient->unit}")
            ->line("Minimum quantity: {$this->ingredient->min_quantity} {$this->ingredient->unit}")
            ->action('Check Inventory', config('app.url') . '/inventory');
    }
}
