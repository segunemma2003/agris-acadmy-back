<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agrisiti Academy - Welcome</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #3E6866 0%, #50C1AE 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .logo-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .logo-img {
            width: 120px;
            height: 120px;
            object-fit: contain;
        }
        
        .subtitle {
            font-size: 24px;
            color: #666;
            margin-bottom: 40px;
        }
        
        .description {
            font-size: 16px;
            color: #888;
            margin-bottom: 50px;
            line-height: 1.6;
        }
        
        .buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .btn {
            display: inline-block;
            padding: 18px 40px;
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3E6866 0%, #50C1AE 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(62, 104, 102, 0.4);
        }
        
        .btn-secondary {
            background: #f7f7f7;
            color: #3E6866;
            border: 2px solid #3E6866;
        }
        
        .btn-secondary:hover {
            background: #3E6866;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(62, 104, 102, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #3E6866 0%, #50C1AE 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(80, 193, 174, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #50C1AE 0%, #3E6866 100%);
            color: white;
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(62, 104, 102, 0.4);
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 40px 20px;
            }
            
            .logo-img {
                width: 80px;
                height: 80px;
            }
            
            .subtitle {
                font-size: 20px;
            }
            
            .btn {
                padding: 15px 30px;
                font-size: 16px;
            }
        }
            </style>
    </head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="https://agrisiti.com/logo-without.svg" alt="Agrisiti Logo" class="logo-img">
        </div>
        <div class="subtitle">Academy Portal</div>
        <div class="description">
            Welcome to Agrisiti Academy. Choose your panel to get started.
        </div>
        
        <div class="buttons">
            <a href="/tutor" class="btn btn-primary">
                👨‍🏫 Tutor Panel
            </a>
            
            <a href="/tagdev" class="btn btn-success">
                💻 TagDev Panel
            </a>
            
            <a href="/supervisor" class="btn btn-warning">
                👔 Supervisor Panel
            </a>
        </div>
    </div>
    </body>
</html>
