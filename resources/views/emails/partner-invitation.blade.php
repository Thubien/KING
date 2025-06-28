@component('mail::message')
# Partnership Invitation

Hello! You have been invited to become a partner in **{{ $storeName }}**.

## Partnership Details
- **Store:** {{ $storeName }}
- **Ownership Percentage:** {{ $ownershipPercentage }}%
- **Invited by:** {{ $companyOwner }}

We would like to invite you to join our business partnership. As a partner, you will have access to:

- Store performance analytics
- Your profit share calculations
- Partnership dashboard
- Financial reports for your stores

@component('mail::button', ['url' => $invitationUrl])
Accept Partnership Invitation
@endcomponent

**Important:** This invitation will expire in 7 days. Please accept it as soon as possible.

If you have any questions about this partnership, please contact {{ $companyOwner }}.

Thanks,<br>
{{ config('app.name') }} Team

---

*This invitation was sent to {{ $partnerEmail }}. If you didn't expect this invitation, you can safely ignore this email.*
@endcomponent