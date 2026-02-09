  <?php
require_once "config.php";
requireAdmin();


// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get stats
$total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'] ?? 0;
$pending_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'")->fetch_assoc()['count'] ?? 0;
$confirmed_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'")->fetch_assoc()['count'] ?? 0;
$cancelled_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'cancelled'")->fetch_assoc()['count'] ?? 0;

// Get recent bookings
$recent_bookings = $conn->query("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 10");

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    $action = clean_input($_POST['action']);
    
    $allowed_statuses = ['confirmed', 'cancelled'];
    
    if (in_array($action, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
        $stmt->bind_param("si", $action, $booking_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Booking status updated successfully!";
        }
        $stmt->close();
        
        header("Location: admin.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rainbow Hotel</title>
    <style>
        /* (CSS styles from original admin.php - safe and clean) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        .header { background: linear-gradient(90deg, #2c3e50, #4a6491); color: white; padding: 1rem 0; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .header-content { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .user-info span { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; font-weight: 500; }
        .nav-buttons { display: flex; gap: 10px; }
        .nav-btn { padding: 10px 20px; background: rgba(255,255,255,0.2); color: white; text-decoration: none; border-radius: 20px; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .nav-btn:hover { background: white; color: #2c3e50; transform: translateY(-2px); }
        .dashboard { padding: 30px 0; }
        .dashboard-title { color: #2c3e50; margin-bottom: 30px; font-size: 2rem; display: flex; align-items: center; gap: 15px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; transition: 0.3s; border: 2px solid transparent; }
        .stat-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
        .stat-card.total { border-color: #4ecdc4; }
        .stat-card.pending { border-color: #ffd166; }
        .stat-card.confirmed { border-color: #06d6a0; }
        .stat-card.cancelled { border-color: #ef476f; }
        .stat-number { font-size: 3.5rem; font-weight: bold; margin-bottom: 15px; line-height: 1; }
        .stat-card.total .stat-number { color: #4ecdc4; }
        .stat-card.pending .stat-number { color: #ffd166; }
        .stat-card.confirmed .stat-number { color: #06d6a0; }
        .stat-card.cancelled .stat-number { color: #ef476f; }
        .stat-label { color: #666; font-size: 1.1rem; font-weight: 500; }
        .section-title { color: #2c3e50; margin: 40px 0 20px; font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .bookings-table-container { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 40px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        thead { background: linear-gradient(90deg, #2c3e50, #4a6491); }
        th { color: white; padding: 20px; text-align: left; font-weight: 600; font-size: 1.1rem; }
        tbody tr { border-bottom: 1px solid #eee; transition: 0.3s; }
        tbody tr:hover { background: #f8f9fa; }
        td { padding: 20px; color: #555; }
        .status { padding: 8px 20px; border-radius: 20px; font-size: 0.9rem; font-weight: bold; display: inline-block; }
        .status-pending { background: #fff3e0; color: #f57c00; }
        .status-confirmed { background: #e8f5e9; color: #2e7d32; }
        .status-cancelled { background: #ffebee; color: #c62828; }
        .action-buttons { display: flex; gap: 10px; }
        .action-btn { padding: 8px 20px; border: none; border-radius: 15px; cursor: pointer; font-size: 0.9rem; font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .btn-confirm { background: linear-gradient(90deg, #06d6a0, #06d6a0); color: white; }
        .btn-confirm:hover { background: linear-gradient(90deg, #05c590, #05c590); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(6, 214, 160, 0.4); }
        .btn-cancel { background: linear-gradient(90deg, #ef476f, #ef476f); color: white; }
        .btn-cancel:hover { background: linear-gradient(90deg, #e63e66, #e63e66); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(239, 71, 111, 0.4); }
        .empty-state { text-align: center; padding: 60px 20px; color: #666; }
        .empty-state-icon { font-size: 4rem; margin-bottom: 20px; opacity: 0.5; }
        .logout-btn { background: linear-gradient(90deg, #ef476f, #ff9a9e); }
        .notification { position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 15px; background: #06d6a0; color: white; font-weight: 500; box-shadow: 0 5px 15px rgba(0,0,0,0.2); display: none; z-index: 1000; }
        @media (max-width: 768px) {
            .header-content { flex-direction: column; gap: 15px; text-align: center; }
            .user-info { flex-direction: column; gap: 10px; }
            .stats { grid-template-columns: 1fr; }
            .bookings-table-container { margin: 0 -20px; border-radius: 0; }
        }
    </style>
</head>
<body>
    <!-- Notification -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div id="notification" class="notification" style="display: block;">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="admin.php" class="logo">
                    <span>🏨</span> Admin Dashboard
                </a>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <div class="nav-buttons">
                        <a href="index.php" class="nav-btn">🏠 Home</a>
                        <a href="logout.php" class="nav-btn logout-btn">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <div class="container">
        <div class="dashboard">
            <h1 class="dashboard-title">📊 Rainbow Hotel Management</h1>
            
            <!-- Stats Cards -->
            <div class="stats">
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $total_bookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-number"><?php echo $pending_bookings; ?></div>
                    <div class="stat-label">Pending Bookings</div>
                </div>
                <div class="stat-card confirmed">
                    <div class="stat-number"><?php echo $confirmed_bookings; ?></div>
                    <div class="stat-label">Confirmed Bookings</div>
                </div>
                <div class="stat-card cancelled">
                    <div class="stat-number"><?php echo $cancelled_bookings; ?></div>
                    <div class="stat-label">Cancelled Bookings</div>
                </div>
            </div>
            
            <!-- Recent Bookings -->
            <h2 class="section-title">📋 Recent Bookings <span style="font-size: 1rem; color: #666; margin-left: 10px;">(Last 10 bookings)</span></h2>
            
            <div class="bookings-table-container">
                <?php if ($recent_bookings->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Guest Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Guests</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($booking = $recent_bookings->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['reference']); ?></strong>
                                    <br>
                                    <small style="color: #888; font-size: 0.8rem;">
                                        <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['guest_email']); ?></td>
                                <td><?php echo htmlspecialchars($booking['guest_phone']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['checkin_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['checkout_date'])); ?></td>
                                <td><?php echo $booking['guests']; ?></td>
                                <td>
                                    <span class="status status-<?php echo $booking['booking_status']; ?>">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($booking['booking_status'] != 'confirmed'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="action" value="confirmed">
                                            <button type="submit" class="action-btn btn-confirm" onclick="return confirm('Confirm booking <?php echo $booking['reference']; ?>?')">✓ Confirm</button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['booking_status'] != 'cancelled'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="action" value="cancelled">
                                            <button type="submit" class="action-btn btn-cancel" onclick="return confirm('Cancel booking <?php echo $booking['reference']; ?>?')">✗ Cancel</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <h3>No Bookings Yet</h3>
                        <p>Bookings will appear here when customers make reservations.</p>
                        <a href="index.php" class="nav-btn" style="margin-top: 20px; display: inline-block;">🏠 Go to Homepage</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Links -->
            <h2 class="section-title">⚡ Quick Actions</h2>
            <div style="display: flex; gap: 15px; margin-bottom: 40px;">
                <a href="index.php" class="nav-btn" style="background: #4ecdc4;">🏠 View Website</a>
                <a href="verify.php" class="nav-btn" style="background: #45b7d1;">🔍 Verify Booking</a>
                <a href="rooms.php" class="nav-btn" style="background: #ff9ff3;">🛏️ Manage Rooms</a>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-hide notification
        const notification = document.getElementById('notification');
        if (notification) {
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
        
        // Form confirmation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                return confirm('Are you sure you want to perform this action?');
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>