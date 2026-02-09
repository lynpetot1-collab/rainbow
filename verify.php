<?php
// verify.php - WORKING VERIFICATION PAGE
require_once 'config.php';

$message = '';
$reference = '';
$bookingDetails = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference = strtoupper(clean_input($_POST['reference'] ?? ''));
    
    if (empty($reference)) {
        $message = '<div class="alert alert-error">⚠️ Please enter a booking reference number.</div>';
    } else {
        // Check if reference matches format
        if (preg_match('/^RNBW\d{5}$/', $reference)) {
            // Query database
            $stmt = $conn->prepare("SELECT * FROM bookings WHERE reference = ? LIMIT 1");
            $stmt->bind_param("s", $reference);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $bookingDetails = $result->fetch_assoc();
                $statusClass = '';
                $statusIcon = '';
                
                switch($bookingDetails['booking_status']) {
                    case 'confirmed':
                        $statusClass = 'status-confirmed';
                        $statusIcon = '✅';
                        break;
                    case 'pending':
                        $statusClass = 'status-pending';
                        $statusIcon = '⏳';
                        break;
                    case 'cancelled':
                        $statusClass = 'status-cancelled';
                        $statusIcon = '❌';
                        break;
                    default:
                        $statusClass = 'status-pending';
                        $statusIcon = '⏳';
                }
                
                $message = '<div class="alert alert-success">
                            <div class="result-header">
                                <h3>'.$statusIcon.' Booking Found!</h3>
                                <span class="status-badge '.$statusClass.'">'.ucfirst($bookingDetails['booking_status']).'</span>
                            </div>
                            <div class="result-details">
                                <div class="detail-row">
                                    <span class="detail-label">Reference:</span>
                                    <span class="detail-value">'.$bookingDetails['reference'].'</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Guest Name:</span>
                                    <span class="detail-value">'.htmlspecialchars($bookingDetails['guest_name']).'</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value">'.htmlspecialchars($bookingDetails['guest_email']).'</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value">'.htmlspecialchars($bookingDetails['guest_phone']).'</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Check-in Date:</span>
                                    <span class="detail-value">'.date('F d, Y', strtotime($bookingDetails['checkin_date'])).'</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Check-out Date:</span>
                                    <span class="detail-value">'.date('F d, Y', strtotime($bookingDetails['checkout_date'])).'</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Number of Guests:</span>
                                    <span class="detail-value">'.$bookingDetails['guests'].'</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Booking Date:</span>
                                    <span class="detail-value">'.date('F d, Y h:i A', strtotime($bookingDetails['created_at'])).'</span>
                                </div>
                            </div>
                            </div>';
            } else {
                $message = '<div class="alert alert-warning">
                            🔍 No booking found with reference: <strong>'.htmlspecialchars($reference).'</strong>
                            </div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-warning">
                        📝 Please use the correct format: RNBW followed by 5 numbers (e.g., RNBW00001)
                        </div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Booking - Rainbow Hotel</title>
    <style>
        /* (CSS styles from original verify.php - safe and clean) */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); margin: 0; min-height: 100vh; }
        .header { background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1); color: white; padding: 1rem 0; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .container { max-width: 800px; margin: 0 auto; padding: 0 20px; }
        .header-content { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; color: white; text-decoration: none; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); }
        .nav-buttons { display: flex; gap: 10px; }
        .nav-btn { padding: 8px 15px; background: rgba(255,255,255,0.2); color: white; text-decoration: none; border-radius: 20px; transition: 0.3s; }
        .nav-btn:hover { background: white; color: #ff6b6b; transform: translateY(-2px); }
        .verify-container { background: white; padding: 40px; border-radius: 20px; margin: 40px auto; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.2); }
        h1 { text-align: center; margin-bottom: 30px; color: #2c3e50; font-size: 2.2rem; background: linear-gradient(90deg, #ff6b6b, #4ecdc4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .form-group { margin-bottom: 25px; }
        label { display: block; margin-bottom: 10px; font-weight: bold; color: #2c3e50; font-size: 1.1rem; }
        input[type="text"] { width: 100%; padding: 15px 20px; border: 2px solid #ddd; border-radius: 15px; font-size: 16px; transition: 0.3s; font-family: monospace; letter-spacing: 1px; }
        input[type="text"]:focus { border-color: #4ecdc4; outline: none; box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.2); }
        .verify-btn { width: 100%; padding: 16px; background: linear-gradient(90deg, #ff6b6b, #ff9ff3); color: white; border: none; border-radius: 15px; font-size: 18px; font-weight: bold; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 5px 15px rgba(255,107,107,0.4); }
        .verify-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(255,107,107,0.6); }
        .alert { padding: 25px; border-radius: 15px; margin: 25px 0; border-left: 5px solid; }
        .alert-error { background: #ffebee; color: #c62828; border-color: #c62828; }
        .alert-warning { background: #fff3e0; color: #f57c00; border-color: #f57c00; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border-color: #2e7d32; }
        .result-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid rgba(46, 125, 50, 0.2); }
        .result-header h3 { margin: 0; font-size: 1.5rem; }
        .status-badge { padding: 8px 20px; border-radius: 20px; font-weight: bold; font-size: 0.9rem; }
        .status-confirmed { background: #c8e6c9; color: #2e7d32; }
        .status-pending { background: #fff3e0; color: #f57c00; }
        .status-cancelled { background: #ffcdd2; color: #c62828; }
        .result-details { display: grid; gap: 12px; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(0,0,0,0.1); }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: bold; color: #555; }
        .detail-value { color: #2c3e50; font-weight: 500; }
        .demo-info { background: linear-gradient(90deg, #e3f2fd, #f3e5f5); padding: 20px; border-radius: 15px; margin-top: 30px; border: 2px dashed #45b7d1; }
        .demo-info h4 { color: #1976d2; margin-top: 0; display: flex; align-items: center; gap: 10px; }
        .back-link { text-align: center; margin-top: 30px; }
        .back-link a { color: #4ecdc4; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; }
        .back-link a:hover { color: #ff6b6b; text-decoration: underline; }
        @media (max-width: 600px) {
            .header-content { flex-direction: column; gap: 15px; text-align: center; }
            .verify-container { padding: 20px; margin: 20px; }
            .result-header { flex-direction: column; gap: 10px; text-align: center; }
            .detail-row { flex-direction: column; gap: 5px; }
            .detail-label, .detail-value { width: 100%; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">🏨 Rainbow Hotel</a>
                <nav class="nav-buttons">
                    <a href="index.php" class="nav-btn">Home</a>
                    <a href="rooms.php" class="nav-btn">Rooms</a>
                    <a href="login.php" class="nav-btn">Admin</a>
                </nav>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="verify-container">
            <h1>🔍 Verify Your Booking</h1>
            <p style="text-align: center; color: #666; margin-bottom: 30px; font-size: 1.1rem;">
                Enter your booking reference to check status and details instantly
            </p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="reference">Booking Reference Number</label>
                    <input type="text" 
                           id="reference" 
                           name="reference" 
                           value="<?php echo htmlspecialchars($reference); ?>"
                           placeholder="Enter reference (e.g., RNBW00001)"
                           required
                           pattern="RNBW\d{5}"
                           title="Format: RNBW followed by 5 digits">
                    <p style="color: #666; font-size: 0.9rem; margin-top: 8px;">
                        Format: <strong>RNBW</strong> followed by 5 numbers (e.g., RNBW00001)
                    </p>
                </div>
                
                <button type="submit" class="verify-btn">🔍 Verify Booking Status</button>
            </form>
            
            <?php echo $message; ?>
            
            <div class="demo-info">
                <h4>💡 Sample References for Testing:</h4>
                <p>Try these sample booking references:</p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li><strong>RNBW00001</strong> - Confirmed booking</li>
                    <li><strong>RNBW00002</strong> - Pending booking</li>
                    <li><strong>RNBW00003</strong> - Confirmed booking</li>
                </ul>
                <p style="margin: 10px 0 0 0; font-size: 0.9rem; color: #666;">
                    You can also create new bookings from the homepage to generate your own reference.
                </p>
            </div>
            
            <div class="back-link">
                <a href="index.php">← Back to Homepage</a>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-format reference input
        document.getElementById('reference').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase();
            value = value.replace(/[^A-Z0-9]/g, '');
            e.target.value = value;
        });
        
        // Focus on input field
        document.getElementById('reference').focus();
    </script>
</body>
</html>
<?php $conn->close(); ?>