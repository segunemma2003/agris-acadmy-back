<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Agrisiti Academy</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .welcome-message {
            font-size: 18px;
            color: #667eea;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #5568d3;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 12px;
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
            color: #667eea;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Welcome to Agrisiti Academy!</h1>
        </div>
        
        <div class="content">
            <p class="welcome-message">Hello {{ $user->name }},</p>
            
            <p>We're thrilled to have you join the Agrisiti Academy community! Your account has been successfully created and you're now ready to start your learning journey.</p>
            
            <div class="info-box">
                <strong>Your Account Details:</strong><br>
                <strong>Email:</strong> {{ $user->email }}<br>
                @if($user->phone)
                <strong>Phone:</strong> {{ $user->phone }}<br>
                @endif
                <strong>Account Status:</strong> Active ✅
            </div>
            
            <p><strong>What's Next?</strong></p>
            
            <div class="features">
                <ul>
                    <li>Browse our wide range of courses</li>
                    <li>Enroll in courses that interest you</li>
                    <li>Track your learning progress</li>
                    <li>Access course materials and resources</li>
                    <li>Connect with tutors and fellow students</li>
                    <li>Complete assignments and quizzes</li>
                </ul>
            </div>
            
            <p>If you have any questions or need assistance, our support team is here to help. Don't hesitate to reach out!</p>
            
            <p>We're excited to be part of your learning journey. Let's grow together! 🌱</p>
            
            <p>Best regards,<br>
            <strong>The Agrisiti Academy Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>&copy; {{ date('Y') }} Agrisiti Academy. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

