<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Confirmation - Agrisiti Academy</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            background: linear-gradient(135deg, #3E6866 0%, #50C1AE 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            margin: -30px -30px 30px -30px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 20px 0;
        }
        .greeting {
            font-size: 18px;
            color: #3E6866;
            margin-bottom: 20px;
        }
        .course-box {
            background-color: #f8f9fa;
            border-left: 4px solid #50C1AE;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .course-title {
            font-size: 22px;
            font-weight: bold;
            color: #3E6866;
            margin-bottom: 10px;
        }
        .course-description {
            color: #666;
            margin-top: 10px;
        }
        .info-box {
            background-color: #e8f5e9;
            border: 1px solid #50C1AE;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box strong {
            color: #3E6866;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #3E6866 0%, #50C1AE 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            opacity: 0.9;
        }
        .features {
            margin: 20px 0;
        }
        .features ul {
            list-style: none;
            padding: 0;
        }
        .features li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }
        .features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #50C1AE;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Enrollment Confirmed!</h1>
        </div>
        
        <div class="content">
            <p class="greeting">Hello {{ $user->name }},</p>
            
            <p>Congratulations! You have successfully enrolled in <strong>{{ $course->title }}</strong>. We're excited to have you join this learning journey!</p>
            
            <div class="course-box">
                <div class="course-title">{{ $course->title }}</div>
                @if($course->short_description)
                <div class="course-description">{{ $course->short_description }}</div>
                @endif
            </div>
            
            <div class="info-box">
                <strong>Your Enrollment Details:</strong><br>
                <strong>Course:</strong> {{ $course->title }}<br>
                <strong>Enrollment Date:</strong> {{ $enrollment->enrolled_at->format('F d, Y') }}<br>
                <strong>Status:</strong> Active ✅<br>
                @if($course->duration_minutes)
                <strong>Estimated Duration:</strong> {{ $course->duration_minutes }} minutes<br>
                @endif
                @if($course->certificate_included)
                <strong>Certificate:</strong> Included 🎓<br>
                @endif
            </div>
            
            <p><strong>What's Next?</strong></p>
            
            <div class="features">
                <ul>
                    <li>Access all course materials and resources</li>
                    <li>Watch video lessons at your own pace</li>
                    <li>Complete assignments and quizzes</li>
                    <li>Track your learning progress</li>
                    <li>Interact with tutors and fellow students</li>
                    @if($course->certificate_included)
                    <li>Earn your certificate upon completion</li>
                    @endif
                </ul>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/courses/{{ $course->slug }}" class="button">Start Learning Now</a>
            </div>
            
            <p>If you have any questions or need assistance, our support team is here to help. Don't hesitate to reach out!</p>
            
            <p>Happy learning! 🌱</p>
            
            <p><strong>The Agrisiti Academy Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply directly to this message.</p>
            <p>&copy; {{ date('Y') }} Agrisiti Academy. All rights reserved.</p>
        </div>
    </div>
</body>
</html>



