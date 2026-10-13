<x-mail::message>
# Welcome to {{ config('app.name') }}

Hi **{{ $user->name }}**,

You have been invited to join **{{ $user->company->name }}** on our B2B platform.

To get started and access your dashboard, you need to set up a secure password for your account.

<x-mail::button :url="$resetUrl" color="primary">
Set up your account
</x-mail::button>

<x-mail::panel>
**For security reasons:** This invitation link will expire automatically in 60 minutes.
</x-mail::panel>

Best regards,<br>
The **{{ config('app.name') }}** team
</x-mail::message>
