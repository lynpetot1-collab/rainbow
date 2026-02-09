<?php
// index.php - HOME PAGE
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rainbow Hotel - Colorful Luxury Stays</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
        }
        
        .header {
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #ffeaa7, #ff9ff3);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .logo span {
            background: white;
            color: #ff6b6b;
            padding: 5px 10px;
            border-radius: 10px;
            margin-right: 10px;
        }
        
        .nav-buttons {
            display: flex;
            gap: 15px;
        }
        
        .nav-btn {
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-btn:hover {
            background: white;
            color: #ff6b6b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), 
                        url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1200');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 100px 20px;
            margin-bottom: 40px;
            border-radius: 0 0 30px 30px;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 30px;
            color: #fff;
        }
        
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .cta-btn {
            padding: 15px 35px;
            background: linear-gradient(90deg, #ff6b6b, #ff9ff3);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            box-shadow: 0 5px 15px rgba(255,107,107,0.4);
        }
        
        .cta-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 25px rgba(255,107,107,0.6);
        }
        
        .cta-btn.secondary {
            background: transparent;
            border: 2px solid white;
            color: white;
            box-shadow: none;
        }
        
        .cta-btn.secondary:hover {
            background: white;
            color: #ff6b6b;
            box-shadow: 0 10px 25px rgba(255,255,255,0.3);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 60px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: 0.4s;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .feature-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            background: linear-gradient(90deg, #4ecdc4, #45b7d1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .feature-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.4rem;
        }
        
        .feature-card p {
            color: #666;
            margin-bottom: 20px;
        }
        
        footer {
            background: linear-gradient(90deg, #2c3e50, #4a6491);
            color: white;
            padding: 40px 20px;
            margin-top: 60px;
            text-align: center;
        }
        
        footer a {
            color: #4ecdc4;
            text-decoration: none;
            margin: 0 15px;
            transition: 0.3s;
        }
        
        footer a:hover {
            color: #ff6b6b;
            text-decoration: underline;
        }
        
        #booking {
            background: white;
            padding: 40px;
            border-radius: 20px;
            margin: 40px auto;
            max-width: 800px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            transition: 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #4ecdc4;
            outline: none;
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.2);
        }
        
        .submit-btn {
            background: linear-gradient(90deg, #ff6b6b, #ff9ff3);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            display: block;
            margin: 0 auto;
            box-shadow: 0 5px 15px rgba(255,107,107,0.4);
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255,107,107,0.6);
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .cta-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            #booking {
                padding: 20px;
                margin: 20px;
            }
        }
        
        .welcome {
            text-align: center;
            padding: 20px;
            margin: 20px 0;
            background: linear-gradient(90deg, #ff9ff3, #feca57);
            border-radius: 15px;
            color: white;
            box-shadow: 0 5px 15px rgba(254, 202, 87, 0.4);
        }
    </style>
</head>
<body>
    <!-- Header with Navigation -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <span>🏨</span> Rainbow Hotel
                </a>
                <nav class="nav-buttons">
                    <a href="index.php" class="nav-btn">
                        🏠 Home
                    </a>
                    <a href="rooms.php" class="nav-btn">
                        🛏️ Rooms
                    </a>
                    <a href="verify.php" class="nav-btn">
                        🔍 Verify Booking
                    </a>
                    <a href="login.php" class="nav-btn">
                        🔐 Admin Login
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Welcome Message -->
    <div class="container">
        <div class="welcome">
            <h2>🌈 Experience Colorful Luxury!</h2>
            <p>Welcome to Rainbow Hotel - Where every stay is vibrant and memorable</p>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Discover Rainbow Luxury</h1>
            <p>Immerse yourself in a world of color and comfort at Rainbow Hotel. Experience vibrant accommodations, exceptional service, and unforgettable memories.</p>
            
            <div class="cta-buttons">
                <a href="#booking" class="cta-btn">
                    📅 Book Your Colorful Stay
                </a>
                <a href="verify.php" class="cta-btn secondary">
                    🔍 Check Your Booking
                </a>
                <a href="rooms.php" class="cta-btn secondary">
                    🛏️ View Our Rooms
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="feature-card">
            <div class="feature-icon">🎨</div>
            <h3>Vibrant Rooms</h3>
            <p>Stay in our color-themed rooms designed to uplift your mood and provide ultimate comfort.</p>
            <a href="rooms.php" class="nav-btn" style="margin-top: 15px; background: #ff6b6b; color: white;">Explore Rooms</a>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">⚡</div>
            <h3>Quick Verification</h3>
            <p>Instantly verify your booking status with our efficient online system.</p>
            <a href="verify.php" class="nav-btn" style="margin-top: 15px; background: #4ecdc4; color: white;">Verify Now</a>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">👑</div>
            <h3>Easy Management</h3>
            <p>Comprehensive admin dashboard for seamless hotel management.</p>
            <a href="login.php" class="nav-btn" style="margin-top: 15px; background: #45b7d1; color: white;">Admin Portal</a>
        </div>
    </section>

    <!-- Booking Form -->
    <section id="booking">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 30px; color: #2c3e50; font-size: 2rem;">Book Your Colorful Stay</h2>
            <form action="book.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" required placeholder="Enter your full name">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="Enter your email">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" required placeholder="Enter your phone number">
                    </div>
                    <div class="form-group">
                        <label>Number of Guests</label>
                        <select name="guests" required>
                            <option value="">Select guests</option>
                            <option value="1">1 Guest</option>
                            <option value="2">2 Guests</option>
                            <option value="3">3 Guests</option>
                            <option value="4">4 Guests</option>
                        </select>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" class="submit-btn">
                        ✨ Confirm Booking
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p style="font-size: 1.2rem; margin-bottom: 20px;">🌈 Rainbow Hotel - Color Your Stay</p>
            <div style="margin: 20px 0;">
                <a href="index.php">Home</a>
                <a href="rooms.php">Rooms</a>
                <a href="verify.php">Verify Booking</a>
                <a href="login.php">Admin Login</a>
            </div>
            <p>&copy; <?php echo date('Y'); ?> Rainbow Hotel. All rights reserved.</p>
            <p style="margin-top: 20px; font-size: 0.9rem; color: #ddd;">Experience the vibrant side of hospitality</p>
        </div>
    </footer>

    <script>
        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>