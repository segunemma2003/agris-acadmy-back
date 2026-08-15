<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($locale ?? 'en') === 'ha' ? 'Takardar Shaida A Shirye' : 'Certificate Ready' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f4f4f4;
        }
        .header {
            background: linear-gradient(135deg, #3E6866 0%, #50C1AE 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #ffffff;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .cert-box {
            background: #f8fafc;
            border: 2px solid #50C1AE;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .cert-box p { margin: 6px 0; }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #3E6866 0%, #50C1AE 100%);
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 8px 6px;
            text-align: center;
            font-weight: 600;
        }
        .button.secondary {
            background: #1B7F6E;
        }
        .path-cards {
            margin: 24px 0;
        }
        .path-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin: 12px 0;
            background: #f8fafc;
        }
        .path-card h3 {
            margin: 0 0 8px;
            color: #1B7F6E;
            font-size: 16px;
        }
        .path-card p {
            margin: 0 0 12px;
            font-size: 14px;
            color: #475569;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
        .note {
            font-size: 13px;
            color: #64748b;
        }
    </style>
</head>
<body>
@php $isHa = ($locale ?? 'en') === 'ha'; @endphp
<div class="header">
    <h1>{{ $isHa ? 'Takardar Shaida A Shirye' : 'Certificate Ready' }}</h1>
</div>
<div class="content">
    @if($isAdminCopy)
        <p>{{ $isHa ? 'An samar da takardar shaida yanzu:' : 'A certificate has just been generated:' }}</p>
    @else
        <p>{{ $isHa ? 'Sannu' : 'Hi' }} {{ $user->name }},</p>
        <p>
            @if($isHa)
                Taya murna! Ka kammala <strong>{{ $course->title }}</strong>. Takardar shaidarka an haɗa ta a wannan sakon (PDF), kuma za ka iya sauke ta a kowane lokaci.
            @else
                Congratulations! You have completed <strong>{{ $course->title }}</strong>. Your certificate is attached to this email as a PDF, and you can download it anytime from the platform.
            @endif
        </p>
    @endif

    <div class="cert-box">
        <p><strong>{{ $isHa ? 'Mahalarta' : 'Participant' }}:</strong> {{ $certificate->recipient_name }}</p>
        <p><strong>{{ $isHa ? 'Darasi' : 'Course' }}:</strong> {{ $course->title }}</p>
        <p><strong>{{ $isHa ? 'Lambar takarda' : 'Certificate No' }}:</strong> {{ $certificate->certificate_number }}</p>
        <p><strong>{{ $isHa ? 'An bayar' : 'Issued' }}:</strong> {{ $certificate->issued_date->format('jS F Y') }}</p>
    </div>

    <p style="text-align: center;">
        <a href="{{ $certificate->file_path }}" class="button">
            {{ $isHa ? 'Sauke Takardar Shaida' : 'Download Certificate' }}
        </a>
    </p>
    <p class="note" style="text-align:center;">
        {{ $isHa ? 'An haɗa kwafin PDF a wannan imel ɗin.' : 'A PDF copy is attached to this email.' }}
    </p>

    @if(!$isAdminCopy)
        <div class="path-cards">
            <h2 style="font-size:18px;margin:0 0 8px;">
                {{ $isHa ? 'Me Ya Biyo?' : "What's Next?" }}
            </h2>
            <p class="note">
                {{ $isHa
                    ? 'Ka zaɓi hanya don ci gaba da amfani da sabbin fasaharka:'
                    : 'Choose a pathway to put your new skills to work:' }}
            </p>

            <div class="path-card">
                <h3>{{ $isHa ? 'Hanya A — Agrisiti Finance' : 'Path A — Agrisiti Finance' }}</h3>
                <p>
                    {{ $isHa
                        ? 'Samu microcredit da kayan aikin kudi don gona ko kasuwancin noma.'
                        : 'Access microcredit and finance tools built for agripreneurs.' }}
                </p>
                <a href="{{ $pathAUrl }}" class="button secondary">
                    {{ $isHa ? 'Nemi Tallafi' : 'Apply for Funding' }}
                </a>
            </div>

            <div class="path-card">
                <h3>{{ $isHa ? 'Hanya B — Horon Aiki / Aiki' : 'Path B — Apprenticeship / Employment' }}</h3>
                <p>
                    {{ $isHa
                        ? 'Bincika damar horon aiki da wurin aiki daga kungiyoyi masu rijista.'
                        : 'Browse open apprenticeship and placement slots from registered organisations.' }}
                </p>
                <a href="{{ $pathBUrl }}" class="button secondary">
                    {{ $isHa ? 'Bincika horon aiki' : 'Browse apprenticeships' }}
                </a>
            </div>
        </div>

        <p>
            @if($isHa)
                Za ka iya samun takardun shaidarka a kowane lokaci a
                <a href="{{ $certificatesUrl }}">Takardun Shaida na</a>.
            @else
                You can also find your certificates anytime under
                <a href="{{ $certificatesUrl }}">My Certificates</a>.
            @endif
        </p>
        <p>
            {{ $isHa ? 'Sallama,' : 'Best regards,' }}<br>
            <strong>Agrisiti Academy</strong>
        </p>
    @endif
</div>
<div class="footer">
    <p>{{ $isHa ? 'Wannan imel ne na atomatik. Kar a amsa.' : 'This is an automated email. Please do not reply to this message.' }}</p>
</div>
</body>
</html>
