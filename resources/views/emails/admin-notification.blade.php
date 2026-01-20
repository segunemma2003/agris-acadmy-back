<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification from Agrisiti Academy</title>
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
        .message-box {
            background-color: #f8f9fa;
            border-left: 4px solid #50C1AE;
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
            <h1>📧 Notification from Agrisiti Academy</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $recipient->name }},</p>
            
            <div class="message-box">
                {!! nl2br(e($message)) !!}
            </div>
            
            @if($sender)
            <p><strong>From:</strong> {{ $sender->name }}</p>
            @endif
            
            <p>Best regards,<br><strong>The Agrisiti Academy Team</strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Agrisiti Academy. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

