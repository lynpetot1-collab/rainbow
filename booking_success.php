<?php
// booking_success.php
require_once 'config.php';

$reference = clean_input($_GET['ref'] ?? '');

if (empty($reference)) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - Rainbow Hotel</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        
        .success-card {
            background: white;
            padding: 50px 40px;
            border-radius: 25px;
            text-align: center;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            animation: float 3s ease-in-out infinite;
            border: 3px solid transparent;
            background-clip: padding-box;
            position: relative;
            overflow: hidden;
        }
        
        .success-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #ffeaa7, #ff9ff3);
            z-index: -1;
            border-radius: 27px;
            animation: rotate 4s linear infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .success-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: bounce 1s ease infinite alternate;
        }
        
        @keyframes bounce {
            from { transform: scale(1); }
            to { transform: scale(1.1); }
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 2.5rem;
        }
        
        .reference {
            background: linear-gradient(90deg, #f8f9fa, #e9ecef);
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
            font-size: 2rem;
            font-weight: bold;
            color: #ff6b6b;
            border: 3px dashed #4ecdc4;
            letter-spacing: 2px;
        }
        
        .instructions {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin: 25px 0;
            text-align: left;
            border-left: 5px solid #2196f3;
        }
        
        .instructions h3 {
            color: #1976d2;
            margin-top: 0;
        }
        
        .instructions ul {
            padding-left: 20px;
            margin: 10px 0;
        }
        
        .instructions li {
            margin-bottom: 8px;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            background: linear-gradient(90deg, #ff6b6b, #ff9ff3);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            transition: 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(255,107,107,0.4);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255,107,107,0.6);
        }
        
        .btn-secondary {
            background: linear-gradient(90deg, #4ecdc4, #45b7d1);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.4);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 10px 25px rgba(78, 205, 196, 0.6);
        }
        
        p {
            color: #666;
            line-height: 1.6;
            margin: 10px 0;
        }
        
        @media (max-width: 600px) {
            .success-card {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .reference {
                font-size: 1.5rem;
                padding: 20px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">🎉</div>
        <h1>Booking Confirmed!</h1>
        <p>Thank you for choosing Rainbow Hotel for your colorful stay.</p>
        <p>Your booking has been received and is being processed.</p>
        
        <div class="reference"><?php echo htmlspecialchars($reference); ?></div>
        
        <div class="instructions">
            <h3>📋 Important Information:</h3>
            <ul>
                <li>Save your reference number for future inquiries</li>
                <li>Check your email for booking confirmation</li>
                <li>Check-in time: 2:00 PM</li>
                <li>Check-out time: 11:00 AM</li>
                <li>Contact us if you need to make changes</li>
            </ul>
        </div>
        
        <p><strong>Status:</strong> ⏳ Pending Confirmation</p>
        <p>We will contact you within 24 hours to confirm your booking.</p>
        
        <div class="actions">
            <a href="verify.php" class="btn">
                🔍 Verify Booking Status
            </a>
            <a href="index.php" class="btn btn-secondary">
                🏠 Back to Homepage
            </a>
        </div>
        
        <p style="margin-top: 30px; font-size: 0.9rem; color: #888;">
            Need help? Contact us at: support@rainbowhotel.com
        </p>
    </div>
</body>
</html>