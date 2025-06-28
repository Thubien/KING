<?php

namespace App\Mail;

use App\Models\Partnership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartnerInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Partnership $partnership;
    public string $invitationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Partnership $partnership)
    {
        $this->partnership = $partnership;
        $this->invitationUrl = route('partnership.accept', ['token' => $partnership->invitation_token]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Partnership Invitation for {$this->partnership->store->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.partner-invitation',
            with: [
                'partnership' => $this->partnership,
                'storeName' => $this->partnership->store->name,
                'ownershipPercentage' => $this->partnership->ownership_percentage,
                'companyOwner' => $this->partnership->store->company->owner->name ?? 'Company Owner',
                'invitationUrl' => $this->invitationUrl,
                'partnerEmail' => $this->partnership->partner_email,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}