<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Graded</title>
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
        .grade-box {
            background: white;
            border: 2px solid #50C1AE;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .grade-box p {
            margin: 6px 0;
        }
        .feedback-box {
            background: #f8f9fa;
            border-left: 4px solid #50C1AE;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
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
        <h1>Assignment Graded</h1>
    </div>
    <div class="content">
        <p>Hi {{ $user->name }},</p>
        <p>Your assignment has been graded by your tutor.</p>

        <div class="grade-box">
            <p><strong>Assignment:</strong> {{ $assignment->title }}</p>
            <p><strong>Course:</strong> {{ $course->title }}</p>
            <p><strong>Status:</strong> {{ ucfirst($submission->status) }}</p>
            @if($submission->score !== null)
                <p><strong>Score:</strong> {{ $submission->score }} / {{ $assignment->max_score }}</p>
            @endif
        </div>

        @if($submission->feedback)
            <div class="feedback-box">
                <strong>Tutor Feedback:</strong>
                <div>{!! $submission->feedback !!}</div>
            </div>
        @endif

        <p>Log in to Agrisiti Academy to view the full feedback and, if allowed, resubmit your work.</p>

        <p>Best regards,<br><strong>Agrisiti Academy Team</strong></p>
    </div>
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>
