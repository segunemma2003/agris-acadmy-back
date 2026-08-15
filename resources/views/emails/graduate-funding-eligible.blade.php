<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($locale ?? 'en') === 'ha' ? 'Cancantar Rancen Digiri' : 'Graduate Loan Eligibility' }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background: #f4f4f4; }
        .header { background: linear-gradient(135deg, #3E6866 0%, #50C1AE 100%); color: white; padding: 28px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #ffffff; padding: 28px; border-radius: 0 0 10px 10px; }
        .amount { font-size: 22px; font-weight: 700; color: #1B7F6E; margin: 12px 0; }
        .button { display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #3E6866 0%, #50C1AE 100%); color: white !important; text-decoration: none; border-radius: 5px; font-weight: 600; margin-top: 12px; }
        .footer { text-align: center; margin-top: 16px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
@php $isHa = ($locale ?? 'en') === 'ha'; @endphp
<div class="header">
    <h1>{{ $isHa ? 'Ka Cancanci Rancen Digiri' : 'You Qualify for a Graduate Loan' }}</h1>
</div>
<div class="content">
    <p>{{ $isHa ? 'Sannu' : 'Hi' }} {{ $user->name }},</p>

    @if($isHa)
        <p>
            Taya murna! Ka kammala <strong>{{ $course->title }}</strong>.
            Domin wannan nasara, ka cancanci <strong>rancen digiri</strong> ta Agrisiti Finance.
        </p>
        <p class="amount">Har ₦{{ $eligibleLoanAmountFormatted }}</p>
        <p>Yi rijista (ko ci gaba da aikace-aikace idan kana da asusu) don amfani da wannan dama:</p>
    @else
        <p>
            Congratulations! You completed <strong>{{ $course->title }}</strong>.
            That achievement unlocks a dedicated <strong>graduate loan</strong> on Agrisiti Finance.
        </p>
        <p class="amount">Up to ₦{{ $eligibleLoanAmountFormatted }}</p>
        <p>Register (or continue to apply if you already have an account) to take up this opportunity:</p>
    @endif

    <p style="text-align:center;">
        <a href="{{ $registerUrl }}" class="button">
            {{ $isHa ? 'Nemi Tallafi' : 'Apply for Funding' }}
        </a>
    </p>

    <p style="font-size:13px;color:#64748b;margin-top:20px;">
        {{ $isHa
            ? 'Lambar takardar shaida: '.$certificate->certificate_number
            : 'Certificate number: '.$certificate->certificate_number }}
    </p>

    <p>
        {{ $isHa ? 'Sallama,' : 'Best regards,' }}<br>
        <strong>Agrisiti Academy &amp; Agrisiti Finance</strong>
    </p>
</div>
<div class="footer">
    <p>{{ $isHa ? 'Wannan imel ne na atomatik. Kar a amsa.' : 'This is an automated email. Please do not reply.' }}</p>
</div>
</body>
</html>
