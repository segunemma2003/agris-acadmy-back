<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Ready</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #3E6866 0%, #50C1AE 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .cert-box {
            background: white;
            border: 2px solid #50C1AE;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .cert-box p {
            margin: 6px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #3E6866 0%, #50C1AE 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎓 Certificate Ready</h1>
    </div>
    <div class="content">
        @if($isAdminCopy)
            <p>A certificate has just been generated:</p>
        @else
            <p>Hi {{ $user->name }},</p>
            <p>Congratulations! Your certificate is ready to download.</p>
        @endif

        <div class="cert-box">
            <p><strong>Participant:</strong> {{ $certificate->recipient_name }}</p>
            <p><strong>Course:</strong> {{ $course->title }}</p>
            <p><strong>Certificate No:</strong> {{ $certificate->certificate_number }}</p>
            <p><strong>Issued:</strong> {{ $certificate->issued_date->format('jS F Y') }}</p>
        </div>

        <p style="text-align: center;">
            <a href="{{ $certificate->file_path }}" class="button">Download Certificate</a>
        </p>

        @if(!$isAdminCopy)
            <p>You can also find it anytime under your certificates on Agrisiti Academy.</p>
            <p>Best regards,<br><strong>Agrisiti Academy Team</strong></p>
        @endif
    </div>
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>
