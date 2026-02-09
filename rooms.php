<?php
// rooms.php - ROOMS PAGE
require_once 'config.php';

// Get rooms from database
$rooms_result = $conn->query("SELECT * FROM rooms WHERE status = 'available' ORDER BY price_per_night");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Rooms - Rainbow Hotel</title>
    <style>
        /* (CSS styles from original rooms.php - safe and clean) */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); margin: 0; color: #333; }
        .header { background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1); color: white; padding: 1rem 0; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .header-content { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; color: white; text-decoration: none; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); }
        .nav-buttons { display: flex; gap: 10px; }
        .nav-btn { padding: 8px 15px; background: rgba(255,255,255,0.2); color: white; text-decoration: none; border-radius: 20px; transition: 0.3s; }
        .nav-btn:hover { background: white; color: #ff6b6b; transform: translateY(-2px); }
        .page-header { text-align: center; padding: 60px 20px; }
        .page-header h1 { font-size: 3rem; margin-bottom: 20px; background: linear-gradient(90deg, #ff6b6b, #4ecdc4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-shadow: 2px 2px 4px rgba(0,0,0,0.1); }
        .page-header p { font-size: 1.2rem; color: #666; max-width: 700px; margin: 0 auto; }
        .rooms-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; padding: 20px 0 60px; }
        .room-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); transition: 0.4s; border: 2px solid transparent; position: relative; }
        .room-card:hover { transform: translateY(-15px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); border-color: #4ecdc4; }
        .room-image { height: 250px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); position: relative; overflow: hidden; }
        .room-type { position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.9); padding: 5px 15px; border-radius: 15px; font-weight: bold; color: #ff6b6b; text-transform: uppercase; font-size: 0.9rem; }
        .room-content { padding: 30px; }
        .room-title { font-size: 1.5rem; margin-bottom: 15px; color: #2c3e50; display: flex; justify-content: space-between; align-items: center; }
        .room-price { color: #ff6b6b; font-size: 1.8rem; font-weight: bold; }
        .room-price span { font-size: 1rem; color: #666; font-weight: normal; }
        .room-description { color: #666; line-height: 1.6; margin-bottom: 20px; }
        .room-features { display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0; }
        .feature-tag { background: #f0f7ff; padding: 5px 12px; border-radius: 15px; font-size: 0.9rem; color: #45b7d1; }
        .book-btn { display: block; width: 100%; padding: 15px; background: linear-gradient(90deg, #ff6b6b, #ff9ff3); color: white; text-decoration: none; border-radius: 15px; font-weight: bold; text-align: center; transition: 0.3s; border: none; cursor: pointer; font-size: 1.1rem; margin-top: 20px; box-shadow: 0 5px 15px rgba(255,107,107,0.4); }
        .book-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(255,107,107,0.6); }
        .room-details { display: flex; justify-content: space-between; margin-top: 15px; color: #666; font-size: 0.9rem; }
        .detail-item { display: flex; align-items: center; gap: 5px; }
        footer { background: linear-gradient(90deg, #2c3e50, #4a6491); color: white; padding: 40px 20px; text-align: center; margin-top: 60px; }
        footer a { color: #4ecdc4; text-decoration: none; margin: 0 10px; }
        @media (max-width: 768px) {
            .header-content { flex-direction: column; gap: 15px; text-align: center; }
            .page-header h1 { font-size: 2.2rem; }
            .rooms-grid { grid-template-columns: 1fr; }
            .room-card { max-width: 500px; margin: 0 auto; }
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
                    <a href="verify.php" class="nav-btn">Verify Booking</a>
                    <a href="login.php" class="nav-btn">Admin Login</a>
                </nav>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="page-header">
            <h1>🌈 Our Colorful Rooms</h1>
            <p>Experience comfort in our uniquely themed rooms. Each room is designed to provide a vibrant and memorable stay.</p>
        </div>
        
        <div class="rooms-grid">
            <?php if ($rooms_result->num_rows > 0): ?>
                <?php while($room = $rooms_result->fetch_assoc()): 
                    $amenities = json_decode($room['amenities'] ?? '[]', true);
                ?>
                <div class="room-card">
                    <div class="room-image">
                        <div class="room-type"><?php echo htmlspecialchars($room['room_type']); ?></div>
                    </div>
                    <div class="room-content">
                        <div class="room-title">
                            <h3><?php echo htmlspecialchars($room['room_name']); ?></h3>
                            <div class="room-price">
                                $<?php echo $room['price_per_night']; ?>
                                <span>/ night</span>
                            </div>
                        </div>
                        
                        <p class="room-description">
                            <?php echo htmlspecialchars($room['description']); ?>
                        </p>
                        
                        <div class="room-details">
                            <div class="detail-item">
                                <span>👤</span>
                                <span>Max <?php echo $room['max_guests']; ?> guests</span>
                            </div>
                            <div class="detail-item">
                                <span>🏷️</span>
                                <span>Room <?php echo $room['room_number']; ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($amenities)): ?>
                        <div class="room-features">
                            <?php foreach($amenities as $amenity): ?>
                                <span class="feature-tag"><?php echo htmlspecialchars($amenity); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <a href="index.php#booking" class="book-btn">🛏️ Book This Room</a>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
                    <h3>No rooms available at the moment</h3>
                    <p>Please check back later or contact us for more information.</p>
                    <a href="index.php" class="nav-btn" style="margin-top: 20px; display: inline-block;">← Back to Homepage</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p style="font-size: 1.2rem; margin-bottom: 20px;">Experience the Rainbow Difference</p>
            <div style="margin: 20px 0;">
                <a href="index.php">Home</a>
                <a href="rooms.php">Rooms</a>
                <a href="verify.php">Verify Booking</a>
                <a href="login.php">Admin</a>
            </div>
            <p>&copy; <?php echo date('Y'); ?> Rainbow Hotel. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?>