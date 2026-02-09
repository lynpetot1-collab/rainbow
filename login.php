<?php
require_once "config.php";

// If already logged in
if (isAdminLoggedIn()) {
    header("Location: admin.php");
    exit();
}

$error = "";
$login_attempts = 0;
$account_locked = false;
$lock_time = 0;

// Function to detect admin table
function detect_admin_source($conn) {
    $candidates = [
        ['admin', 'username', 'password_hash'],
        ['admins', 'username', 'password_hash'],
        ['users', 'username', 'password_hash'],
        ['admin', 'username', 'password'],
        ['admins', 'username', 'password'],
        ['users', 'username', 'password'],
    ];

    foreach ($candidates as $c) {
        [$t,$u,$p] = $c;
        $res = $conn->query("SHOW TABLES LIKE '".$conn->real_escape_string($t)."'");
        if ($res && $res->num_rows > 0) {
            $cols = [];
            $r2 = $conn->query("SHOW COLUMNS FROM `$t`");
            while ($r2 && ($row = $r2->fetch_assoc())) $cols[] = $row['Field'];
            if (in_array($u, $cols) && in_array($p, $cols)) return [$t,$u,$p];
        }
    }
    return [null,null,null];
}

[$TABLE,$USERCOL,$PASSCOL] = detect_admin_source($conn);
if ($TABLE === null) {
    $error = "Admin table not found. Please import the database tables first.";
}

// Initialize security tables if they don't exist
function initialize_security_tables($conn) {
    // Create login_attempts table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        username VARCHAR(100) NOT NULL,
        attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        successful BOOLEAN DEFAULT FALSE,
        user_agent TEXT,
        INDEX idx_ip (ip_address),
        INDEX idx_username (username),
        INDEX idx_time (attempt_time)
    )");
    
    // Create admin_security table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS admin_security (
        admin_id INT,
        username VARCHAR(100) NOT NULL,
        failed_attempts INT DEFAULT 0,
        last_attempt DATETIME,
        locked_until DATETIME,
        last_login DATETIME,
        last_login_ip VARCHAR(45),
        PRIMARY KEY (admin_id),
        INDEX idx_locked (locked_until)
    )");
}

// Track login attempts
function track_login_attempt($conn, $username, $ip, $success, $user_agent = '') {
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, successful, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $ip, $username, $success, $user_agent);
    $stmt->execute();
    
    if (!$success) {
        // Update failed attempts count
        $update_stmt = $conn->prepare("
            INSERT INTO admin_security (admin_id, username, failed_attempts, last_attempt) 
            SELECT id, ?, 1, NOW() FROM admin WHERE username = ?
            ON DUPLICATE KEY UPDATE 
            failed_attempts = failed_attempts + 1,
            last_attempt = NOW(),
            locked_until = CASE 
                WHEN failed_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                ELSE locked_until
            END
        ");
        $update_stmt->bind_param("ss", $username, $username);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Reset failed attempts on successful login
        $reset_stmt = $conn->prepare("
            UPDATE admin_security 
            SET failed_attempts = 0, 
                locked_until = NULL,
                last_login = NOW(),
                last_login_ip = ?
            WHERE username = ?
        ");
        $reset_stmt->bind_param("ss", $ip, $username);
        $reset_stmt->execute();
        $reset_stmt->close();
    }
    $stmt->close();
}

// Check if IP is rate limited
function is_ip_rate_limited($conn, $ip) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE ip_address = ? 
        AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        AND successful = 0
    ");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return ($row['attempts'] ?? 0) >= 10; // Limit to 10 failed attempts per 15 minutes
}

// Check if account is locked
function is_account_locked($conn, $username) {
    $stmt = $conn->prepare("
        SELECT locked_until 
        FROM admin_security 
        WHERE username = ? 
        AND locked_until > NOW()
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $locked = $result->num_rows > 0;
    $stmt->close();
    
    return $locked;
}

// Get failed attempts count
function get_failed_attempts($conn, $username) {
    $stmt = $conn->prepare("
        SELECT failed_attempts 
        FROM admin_security 
        WHERE username = ?
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['failed_attempts'] ?? 0;
}

// Initialize security tables
initialize_security_tables($conn);

// Get client IP
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $TABLE !== null) {
    if (!csrf_validate($_POST['token'] ?? '')) {
        $error = "Invalid security token. Please refresh the page.";
        track_login_attempt($conn, '', $client_ip, 0, $user_agent);
    } else {
        $username = clean_input($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = "Please enter username and password.";
        } else {
            // Check IP rate limiting
            if (is_ip_rate_limited($conn, $client_ip)) {
                $error = "Too many failed attempts from your IP. Please try again in 15 minutes.";
                track_login_attempt($conn, $username, $client_ip, 0, $user_agent);
            }
            // Check if account is locked
            elseif (is_account_locked($conn, $username)) {
                $error = "Account temporarily locked due to too many failed attempts. Please try again later.";
                track_login_attempt($conn, $username, $client_ip, 0, $user_agent);
            }
            else {
                // Get current failed attempts
                $login_attempts = get_failed_attempts($conn, $username);
                
                // Add artificial delay for failed attempts (slows down brute force)
                if ($login_attempts > 0) {
                    usleep(min(($login_attempts * 500000), 2000000)); // Max 2 second delay
                }

                $sql = "SELECT * FROM `$TABLE` WHERE `$USERCOL` = ? LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($res && $res->num_rows === 1) {
                    $row = $res->fetch_assoc();
                    $dbPass = $row[$PASSCOL];
                    $admin_id = isset($row['id']) ? (int)$row['id'] : 0;

                    // Verify password (hashed or plain)
                    $ok = false;
                    if (is_string($dbPass) && strlen($dbPass) > 20 && strpos($dbPass, '$') !== false) {
                        $ok = password_verify($password, $dbPass);
                    } else {
                        $ok = hash_equals((string)$dbPass, (string)$password);
                    }

                    if ($ok) {
                        // Successful login
                        track_login_attempt($conn, $username, $client_ip, 1, $user_agent);
                        
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_username'] = $row[$USERCOL] ?? $username;
                        $_SESSION['admin_id'] = $admin_id;
                        $_SESSION['login_ip'] = $client_ip;
                        $_SESSION['login_time'] = time();
                        
                        // Set session timeout (1 hour)
                        $_SESSION['session_expire'] = time() + 3600;
                        
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        
                        header("Location: admin.php");
                        exit();
                    } else {
                        // Failed login
                        track_login_attempt($conn, $username, $client_ip, 0, $user_agent);
                        $login_attempts = get_failed_attempts($conn, $username);
                        
                        // Different error messages based on attempts
                        if ($login_attempts >= 3) {
                            $remaining = 5 - $login_attempts;
                            if ($remaining > 0) {
                                $error = "Invalid credentials. {$remaining} attempt(s) remaining before account lock.";
                            } else {
                                $error = "Account locked for 15 minutes due to too many failed attempts.";
                            }
                        } else {
                            $error = "Invalid username or password.";
                        }
                    }
                } else {
                    // User doesn't exist (but don't reveal that)
                    track_login_attempt($conn, $username, $client_ip, 0, $user_agent);
                    $login_attempts = get_failed_attempts($conn, $username);
                    
                    // Same delay for non-existent users to prevent user enumeration
                    if ($login_attempts > 0) {
                        usleep(min(($login_attempts * 500000), 2000000));
                    }
                    
                    $error = "Invalid username or password.";
                }
                $stmt->close();
            }
        }
    }
}

// Check if account is locked for display purposes
if (isset($username) && $TABLE !== null) {
    $account_locked = is_account_locked($conn, $username);
    if ($account_locked) {
        $error = "Account temporarily locked. Please try again in 15 minutes.";
    }
}

// Get login statistics for display
function get_login_stats($conn, $ip) {
    $stats = [
        'today_attempts' => 0,
        'ip_blocked' => false,
        'last_attempt' => null
    ];
    
    // Get today's attempts from this IP
    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt
        FROM login_attempts 
        WHERE ip_address = ? 
        AND DATE(attempt_time) = CURDATE()
        AND successful = 0
    ");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['today_attempts'] = $row['attempts'];
        $stats['last_attempt'] = $row['last_attempt'];
    }
    $stmt->close();
    
    // Check if IP is blocked
    $stats['ip_blocked'] = is_ip_rate_limited($conn, $ip);
    
    return $stats;
}

$login_stats = get_login_stats($conn, $client_ip);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Rainbow Hotel | Admin Login</title>

  <style>
    :root{
      --bg1:#ff7aa2;
      --bg2:#58d6ff;
      --bg3:#ffe17a;
      --bg4:#c7ff8a;
      --bg5:#ff9cf0;
      --card: rgba(255,255,255,.14);
      --stroke: rgba(255,255,255,.28);
      --shadow: 0 18px 45px rgba(0,0,0,.20);
      --text:#0c1220;
      --muted: rgba(12,18,32,.7);
      --white: rgba(255,255,255,.92);
      --error-bg: rgba(255, 231, 231, .92);
      --error-text: #7a0b0b;
      --warning-bg: rgba(255, 248, 225, .92);
      --warning-text: #8a6d00;
      --success-bg: rgba(231, 255, 236, .92);
      --success-text: #0b7a1e;
    }

    *{box-sizing:border-box}
    body{
      margin:0;
      font-family: Arial, sans-serif;
      color: var(--text);
      min-height:100vh;
      background:
        radial-gradient(1200px 500px at 10% 10%, rgba(255,255,255,.55), transparent 55%),
        radial-gradient(900px 500px at 90% 20%, rgba(255,255,255,.45), transparent 55%),
        linear-gradient(90deg, var(--bg1), var(--bg2), var(--bg3), var(--bg4), var(--bg5));
    }

    /* Top nav */
    .topbar{
      width:100%;
      padding:18px 22px;
    }
    .nav{
      max-width:1100px;
      margin:0 auto;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:14px;
      padding:12px 16px;
      border-radius:18px;
      background: rgba(255,255,255,.18);
      border:1px solid var(--stroke);
      backdrop-filter: blur(12px);
      box-shadow: 0 10px 30px rgba(0,0,0,.10);
    }
    .brand{
      display:flex;
      align-items:center;
      gap:12px;
      font-weight:800;
      color: var(--white);
      letter-spacing:.2px;
      text-shadow:0 2px 8px rgba(0,0,0,.15);
    }
    .logo{
      width:44px;height:44px;
      display:grid;place-items:center;
      border-radius:12px;
      background: rgba(255,255,255,.22);
      border:1px solid var(--stroke);
      box-shadow: 0 10px 25px rgba(0,0,0,.10);
      font-size:22px;
    }
    .brand span{font-size:28px}
    .menu{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      justify-content:flex-end;
    }
    .pill{
      text-decoration:none;
      color: rgba(255,255,255,.92);
      padding:10px 14px;
      border-radius:999px;
      background: rgba(255,255,255,.16);
      border:1px solid rgba(255,255,255,.22);
      backdrop-filter: blur(10px);
      display:flex;
      gap:8px;
      align-items:center;
      transition: transform .08s ease, background .2s ease;
      font-weight:700;
      font-size:14px;
    }
    .pill:hover{transform: translateY(-1px); background: rgba(255,255,255,.22);}
    .pill.active{
      background: rgba(255,255,255,.32);
      border-color: rgba(255,255,255,.35);
      color: #0c1220;
    }

    /* Page layout */
    .wrap{
      max-width:1100px;
      margin:0 auto;
      padding:18px 22px 40px;
      display:grid;
      grid-template-columns: 1.15fr .85fr;
      gap:22px;
      align-items:start;
    }

    .hero{
      border-radius:22px;
      padding:26px;
      background: rgba(255,255,255,.16);
      border: 1px solid var(--stroke);
      backdrop-filter: blur(12px);
      box-shadow: var(--shadow);
      min-height: 420px;
      position:relative;
      overflow:hidden;
    }
    .hero:before{
      content:"";
      position:absolute; inset:-60px;
      background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.45), transparent 55%),
                  radial-gradient(circle at 70% 20%, rgba(255,255,255,.35), transparent 55%);
      filter: blur(8px);
      opacity:.8;
    }
    .hero > *{position:relative}
    .tag{
      display:inline-flex;
      gap:10px;
      align-items:center;
      padding:10px 14px;
      border-radius:999px;
      background: rgba(255,255,255,.26);
      border:1px solid rgba(255,255,255,.30);
      color: rgba(255,255,255,.95);
      font-weight:800;
      width:max-content;
    }
    .hero h1{
      margin:18px 0 8px;
      font-size:54px;
      line-height:1.02;
      color: rgba(255,255,255,.95);
      text-shadow: 0 8px 35px rgba(0,0,0,.25);
    }
    .hero p{
      margin:0;
      max-width: 560px;
      color: rgba(255,255,255,.92);
      font-size:18px;
      line-height:1.6;
      text-shadow: 0 8px 25px rgba(0,0,0,.18);
    }

    /* Login card */
    .card{
      border-radius:22px;
      padding:22px;
      background: rgba(255,255,255,.20);
      border: 1px solid rgba(255,255,255,.30);
      backdrop-filter: blur(12px);
      box-shadow: var(--shadow);
    }
    .card h2{
      margin:0 0 6px;
      color: rgba(255,255,255,.95);
      text-shadow: 0 8px 25px rgba(0,0,0,.18);
    }
    .card .sub{
      margin:0 0 16px;
      color: rgba(255,255,255,.85);
      font-weight:600;
    }
    label{
      display:block;
      font-weight:800;
      color: rgba(255,255,255,.92);
      margin:10px 0 6px;
    }
    input{
      width:100%;
      padding:12px 12px;
      border-radius:14px;
      border:1px solid rgba(255,255,255,.28);
      background: rgba(255,255,255,.18);
      color: rgba(255,255,255,.95);
      outline:none;
      font-size:15px;
    }
    input::placeholder{color: rgba(255,255,255,.70)}
    input:focus{border-color: rgba(255,255,255,.45); background: rgba(255,255,255,.22)}
    .btn{
      width:100%;
      margin-top:14px;
      padding:12px 14px;
      border:0;
      border-radius:16px;
      font-weight:900;
      cursor:pointer;
      color:#0c1220;
      background: linear-gradient(90deg, rgba(255,255,255,.9), rgba(255,255,255,.75));
      box-shadow: 0 12px 25px rgba(0,0,0,.18);
      transition: transform .08s ease;
    }
    .btn:hover{transform: translateY(-1px)}
    .btn:disabled{
      opacity:0.6;
      cursor:not-allowed;
    }
    
    /* Alert boxes */
    .alert{
      padding:12px 12px;
      border-radius:16px;
      font-weight:800;
      margin-bottom:10px;
      display:flex;
      align-items:center;
      gap:10px;
      border:1px solid rgba(255,255,255,.65);
    }
    .error{
      background: var(--error-bg);
      color: var(--error-text);
    }
    .warning{
      background: var(--warning-bg);
      color: var(--warning-text);
    }
    .info{
      background: rgba(231, 243, 255, .92);
      color: #0b4c7a;
    }
    
    /* Security info */
    .security-info {
      background: rgba(255,255,255,.12);
      border-radius: 12px;
      padding: 12px;
      margin-top: 15px;
      border: 1px solid rgba(255,255,255,.15);
    }
    .security-info h4 {
      margin: 0 0 8px 0;
      color: rgba(255,255,255,.95);
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .security-info p {
      margin: 0;
      color: rgba(255,255,255,.85);
      font-size: 12px;
      line-height: 1.4;
    }
    
    /* Attempts indicator */
    .attempts-indicator {
      display: flex;
      gap: 4px;
      margin: 10px 0;
      align-items: center;
    }
    .attempt-dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: rgba(255,255,255,.2);
      border: 1px solid rgba(255,255,255,.3);
    }
    .attempt-dot.failed {
      background: #ff6b6b;
      animation: pulse 0.5s ease;
    }
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.2); }
      100% { transform: scale(1); }
    }
    
    .foot{
      margin-top:12px;
      display:flex;
      justify-content:space-between;
      gap:10px;
      flex-wrap:wrap;
      font-weight:800;
    }
    .foot a{
      color: rgba(255,255,255,.92);
      text-decoration:none;
      padding:8px 10px;
      border-radius:999px;
      border:1px solid rgba(255,255,255,.22);
      background: rgba(255,255,255,.12);
    }
    .foot a:hover{background: rgba(255,255,255,.18)}

    @media (max-width: 980px){
      .wrap{grid-template-columns:1fr}
      .hero{min-height:auto}
      .hero h1{font-size:44px}
    }
    
    /* Lockout warning */
    .lockout-warning {
      background: linear-gradient(90deg, rgba(255, 100, 100, 0.2), rgba(255, 150, 150, 0.1));
      border: 1px solid rgba(255, 100, 100, 0.3);
      border-radius: 12px;
      padding: 12px;
      margin-bottom: 15px;
      text-align: center;
    }
  </style>
</head>

<body>
  <div class="topbar">
    <div class="nav">
      <div class="brand">
        <div class="logo">🏨</div>
        <span>Rainbow Hotel</span>
      </div>
      <div class="menu">
        <a class="pill" href="index.php">🏠 Home</a>
        <a class="pill" href="rooms.php">🛏️ Rooms</a>
        <a class="pill" href="verify.php">🔎 Verify Booking</a>
        <a class="pill active" href="login.php">🔐 Admin Login</a>
      </div>
    </div>
  </div>

  <div class="wrap">
    <div class="hero">
      <div class="tag">🔒 Secure Admin Portal</div>
      <h1>Admin Login</h1>
      <p>
        Welcome to Rainbow Hotel management. Please sign in to access bookings, rooms, and admin tools.
        <br><br>
        <small style="opacity:0.9; font-size:14px;">
          ⚠️ For security: 5 failed attempts will lock your account for 15 minutes.
        </small>
      </p>
    </div>

    <div class="card">
      <h2>Sign in</h2>
      <p class="sub">Enter your admin credentials</p>

      <?php if ($login_stats['ip_blocked']): ?>
        <div class="alert warning">
          ⚠️ Too many failed attempts from your IP. Please try again in 15 minutes.
        </div>
      <?php endif; ?>
      
      <?php if ($account_locked): ?>
        <div class="lockout-warning">
          <strong style="color: rgba(255,255,255,.95);">🔒 ACCOUNT LOCKED</strong><br>
          <span style="color: rgba(255,255,255,.85); font-size: 14px;">
            Too many failed attempts. Please try again in 15 minutes.
          </span>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert <?php echo $account_locked ? 'warning' : 'error'; ?>">
          <?php echo $account_locked ? '🔒' : '⚠️'; ?>
          <?php echo e($error); ?>
        </div>
      <?php endif; ?>

      <!-- Failed attempts indicator -->
      <?php if ($login_attempts > 0 && !$account_locked): ?>
        <div class="attempts-indicator">
          <span style="color: rgba(255,255,255,.85); font-size: 13px; margin-right: 8px;">
            Failed attempts: <?php echo $login_attempts; ?>/5
          </span>
          <?php for($i = 1; $i <= 5; $i++): ?>
            <div class="attempt-dot <?php echo $i <= $login_attempts ? 'failed' : ''; ?>"></div>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off" id="loginForm">
        <input type="hidden" name="token" value="<?php echo e(csrf_token()); ?>">

        <label>Username</label>
        <input type="text" name="username" 
               placeholder="Enter username" 
               value="<?php echo e($_POST['username'] ?? ''); ?>"
               required
               <?php echo $account_locked || $login_stats['ip_blocked'] ? 'disabled' : ''; ?>>

        <label>Password</label>
        <input type="password" name="password" 
               placeholder="Enter password" 
               required
               <?php echo $account_locked || $login_stats['ip_blocked'] ? 'disabled' : ''; ?>>

        <button class="btn" type="submit" 
                <?php echo $account_locked || $login_stats['ip_blocked'] ? 'disabled' : ''; ?>>
          <?php echo $account_locked ? '🔒 Account Locked' : 'Login'; ?>
        </button>
      </form>
      
      <!-- Security information -->
      <div class="security-info">
        <h4>🛡️ Security Information</h4>
        <p>
          • <?php echo $login_stats['today_attempts']; ?> failed attempts from your IP today<br>
          • Account locks after 5 failed attempts<br>
          • Session expires after 1 hour of inactivity<br>
          • All login attempts are logged
        </p>
      </div>

      <div class="foot">
        <a href="index.php">← Back to Home</a>
        <a href="verify.php">Check Booking</a>
      </div>
    </div>
  </div>

  <script>
    // Add form submission protection
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const btn = this.querySelector('button[type="submit"]');
      const originalText = btn.innerHTML;
      
      // Disable button and show loading
      btn.disabled = true;
      btn.innerHTML = '⏳ Verifying...';
      btn.style.opacity = '0.7';
      
      // Re-enable after 3 seconds if something goes wrong
      setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        btn.style.opacity = '1';
      }, 3000);
    });
    
    // Auto-focus username field if not locked
    <?php if (!$account_locked && !$login_stats['ip_blocked']): ?>
    document.querySelector('input[name="username"]').focus();
    <?php endif; ?>
    
    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
      window.history.replaceState(null, null, window.location.href);
    }
  </script>
</body>
</html>
<?php $conn->close(); ?>