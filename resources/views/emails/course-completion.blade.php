<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Completion</title>
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
        .congratulations {
            text-align: center;
            font-size: 32px;
            margin: 20px 0;
        }
        .course-box {
            background: white;
            border: 2px solid #50C1AE;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .course-title {
            font-size: 24px;
            font-weight: bold;
            color: #3E6866;
            margin-bottom: 10px;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .stat-item {
            text-align: center;
            padding: 10px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #3E6866;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
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
        <h1>🎉 Course Completed! 🎉</h1>
    </div>
    <div class="content">
        <div class="congratulations">
            Congratulations, {{ $user->name }}!
        </div>

        <p>We're thrilled to inform you that you have successfully completed the course:</p>

        <div class="course-box">
            <div class="course-title">{{ $course->title }}</div>
            <p>{{ $course->short_description ?? 'You\'ve completed this amazing course!' }}</p>
        </div>

        <div class="stats">
            <div class="stat-item">
                <div class="stat-value">100%</div>
                <div class="stat-label">Completion</div>
            </div>
            @if($course->certificate_included)
            <div class="stat-item">
                <div class="stat-value">✓</div>
                <div class="stat-label">Certificate</div>
            </div>
            @endif
            <div class="stat-item">
                <div class="stat-value">{{ $course->lessons_count ?? 0 }}</div>
                <div class="stat-label">Lessons</div>
            </div>
        </div>

        <p><strong>What's Next?</strong></p>
        <ul>
            <li>Continue learning with our other courses</li>
            @if($course->certificate_included)
            <li>Download your certificate from your profile</li>
            @endif
            <li>Share your achievement on social media</li>
            <li>Leave a review to help other students</li>
        </ul>

        <p>Thank you for being part of Agrisiti Academy. We're proud of your achievement!</p>

        <p>Best regards,<br>
        <strong>Agrisiti Academy Team</strong></p>
    </div>
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>






