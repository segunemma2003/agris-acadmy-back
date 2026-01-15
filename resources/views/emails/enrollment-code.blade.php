<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Code</title>
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
        .code-box {
            background: white;
            border: 2px dashed #3E6866;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            border-radius: 5px;
        }
        .code {
            font-size: 24px;
            font-weight: bold;
            color: #3E6866;
            letter-spacing: 2px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #50C1AE;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
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
        <h1>🎓 Course Enrollment Code</h1>
    </div>
    <div class="content">
        <p>Hello,</p>
        <p>You have been issued an enrollment code for the following course:</p>

        <h2>{{ $course->title }}</h2>
        <p>{{ $course->short_description ?? 'Enroll now to start learning!' }}</p>

        <div class="code-box">
            <p style="margin: 0 0 10px 0;">Your Enrollment Code:</p>
            <div class="code">{{ $code }}</div>
        </div>

        @if($expiresAt)
        <p><strong>⏰ This code expires on:</strong> {{ $expiresAt->format('F d, Y \a\t h:i A') }}</p>
        @endif

        <p>Use this code to enroll in the course through our platform.</p>

        <p>If you have any questions, please don't hesitate to contact us.</p>

        <p>Best regards,<br>
        <strong>AgriSiti Academy Team</strong></p>
    </div>
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>



