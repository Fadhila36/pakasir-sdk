<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\Notifications;

use Fadhila36\Pakasir\DataObjects\TransactionCreateResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected TransactionCreateResponse $transaction
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $formattedAmount = number_format($this->transaction->totalPayment, 0, ',', '.');

        return (new MailMessage)
            ->subject("Pembayaran Transaksi {$this->transaction->orderId}")
            ->greeting('Halo!')
            ->line("Tagihan untuk transaksi **{$this->transaction->orderId}** telah diterbitkan.")
            ->line("Total nominal yang harus dibayar adalah **Rp {$formattedAmount}**.")
            ->action('Bayar Sekarang', $this->transaction->paymentUrl)
            ->line("Batas waktu pembayaran adalah s/d {$this->transaction->expiredAt}.")
            ->line('Terima kasih telah bertransaksi dengan kami!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->transaction->orderId,
            'amount' => $this->transaction->amount,
            'fee' => $this->transaction->fee,
            'total_payment' => $this->transaction->totalPayment,
            'payment_url' => $this->transaction->paymentUrl,
            'expired_at' => $this->transaction->expiredAt,
            'payment_method' => $this->transaction->paymentMethod->value,
        ];
    }
}
