<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Module Added - Agrisiti Academy</title>
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
        .module-box {
            background-color: #f8f9fa;
            border-left: 4px solid #50C1AE;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .module-title {
            font-size: 22px;
            font-weight: bold;
            color: #3E6866;
            margin-bottom: 10px;
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
            <h1>📚 New Module Added!</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            
            <p>Great news! A new module has been added to <strong>{{ $course->title }}</strong> that you're enrolled in.</p>
            
            <div class="module-box">
                <div class="module-title">{{ $module->title }}</div>
                @if($module->description)
                <div style="color: #666; margin-top: 10px;">
                    {!! $module->description !!}
                </div>
                @endif
            </div>
            
            <p>You can now access this new module and continue your learning journey!</p>
            
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/courses/{{ $course->slug }}" class="button">View Course</a>
            </div>
            
            <p>Happy learning! 🌱</p>
            
            <p><strong>The Agrisiti Academy Team</strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Agrisiti Academy. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

