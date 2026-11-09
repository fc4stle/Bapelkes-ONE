<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetKataSandi extends Notification
{
    /**
     * The password reset token.
     */
    public function __construct(
        public string $token,
    ) {}

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
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expireMinutes = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject('Atur Ulang Kata Sandi - Portal Pelatihan Terpadu')
            ->greeting("Halo {$notifiable->name},")
            ->line('Anda menerima email ini karena kami menerima permintaan pengaturan ulang kata sandi untuk akun Anda.')
            ->action('Atur Ulang Kata Sandi', $resetUrl)
            ->line("Tautan pengaturan ulang ini akan berlaku selama {$expireMinutes} menit.")
            ->line('Abaikan email ini jika Anda tidak meminta pengaturan ulang.')
            ->line('Jika Anda mengalami kesulitan mengklik tombol "Atur Ulang Kata Sandi", salin dan tempel URL berikut ke peramban Anda:')
            ->line($resetUrl);
    }
}
