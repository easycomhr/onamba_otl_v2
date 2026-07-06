<?php

namespace App\Mail;

use App\Models\OtRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtResultMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public OtRequest $otRequest
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->otRequest->status === OtRequest::STATUS_APPROVED 
            ? 'Đơn tăng ca của bạn đã được duyệt' 
            : 'Đơn tăng ca của bạn đã bị từ chối';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ot.result',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
