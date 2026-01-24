<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message from Your Tutor - Agrisiti Academy</title>
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
        .content {
            padding: 20px 0;
        }
        .course-box {
            background-color: #f8f9fa;
            border-left: 4px solid #50C1AE;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .message-box {
            background-color: #e8f5e9;
            border: 1px solid #50C1AE;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
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
            <h1>📚 Message from Your Tutor</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $recipient->name }},</p>
            
            <div class="course-box">
                <strong>Course:</strong> {{ $course->title }}
            </div>
            
            <div class="message-box">
                {!! nl2br(e($message)) !!}
            </div>
            
            <p><strong>From:</strong> {{ $tutor->name }}<br>
            <strong>Course:</strong> {{ $course->title }}</p>
            
            <p>Best regards,<br><strong>{{ $tutor->name }}</strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Agrisiti Academy. All rights reserved.</p>
        </div>
    </div>
</body>
</html>



