<?php
session_start([
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'
]);

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

require_once '../includes/db.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate limiting
$ip = $_SERVER['REMOTE_ADDR'];
$cacheDir = '../cache/';

// Ensure cache directory exists and is writable
if (!file_exists($cacheDir)) {
    if (!mkdir($cacheDir, 0755, true)) {
        error_log("Failed to create cache directory");
        // Continue without rate limiting rather than failing registration
    }
}

if (file_exists($cacheDir) && is_writable($cacheDir)) {
    $cacheFile = $cacheDir . "register_attempts_" . preg_replace('/[^a-zA-Z0-9\.]/', '_', $ip) . ".txt";
    
    if (file_exists($cacheFile)) {
        $attempts = (int)file_get_contents($cacheFile);
        $lastAttempt = filemtime($cacheFile);
        
        if ($attempts >= 5 && time() - $lastAttempt < 300) {
            $_SESSION['rate_limit_info'] = [
                'last_attempt' => $lastAttempt,
                'attempts' => $attempts
            ];
            header("Location: too_many_attempts.php");
            exit();
        }
    }
} else {
    error_log("Cache directory not writable: " . $cacheDir);
    // Continue without rate limiting
}

$error = "";
$success = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Błąd CSRF - niedozwolone żądanie.");
    }

    // Update rate limiting - tylko jeśli katalog jest dostępny do zapisu
    if (file_exists($cacheDir) && is_writable($cacheDir)) {
        $written = file_put_contents($cacheFile, ($attempts ?? 0) + 1);
        if ($written === false) {
            error_log("Failed to write to rate limit file: " . $cacheFile);
        }
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($username) || empty($password) || empty($password_confirm)) {
        $error = "All fields are required.";
    } elseif (strlen($username) < 4) {
        $error = "Username must be at least 4 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username can only contain letters, numbers and underscores.";
    } elseif (preg_match('/[\x00-\x1F\x7F<>]/', $username)) {
        $error = "Username contains invalid characters.";
    } elseif (strlen($password) < 12) {
        $error = "Password must be at least 12 characters.";
    } elseif (strlen($password) > 64) {
        $error = "Password cannot be longer than 64 characters.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number.";
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $error = "Password must contain at least one special character.";
    } elseif (preg_match('/^(password|123456|qwerty)/i', $password)) {
        $error = "Password is too common. Choose a stronger one.";
    } elseif ($password !== $password_confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if user exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        if ($check === false) {
            error_log("Database error: " . $conn->error);
            $error = "Database error. Please try again later.";
        } else {
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "Username is already taken.";
            } else {
                // Insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                
                if ($stmt === false) {
                    error_log("Database error: " . $conn->error);
                    $error = "Database error. Please try again later.";
                } else {
                    $stmt->bind_param("ss", $username, $hashed_password);
                    
                    if ($stmt->execute()) {
                        // Clear both session and file rate limits
                        if (isset($_SESSION['rate_limit_info'])) {
                            unset($_SESSION['rate_limit_info']);
                        }
                        if (file_exists($cacheFile)) {
                            unlink($cacheFile);
                        }
                        
                        $success = "Registration successful! Redirecting to login...";
                        $username = "";
                    } else {
                        error_log("Database error: " . $stmt->error);
                        $error = "Registration failed. Please try again.";
                    }
                    $stmt->close();
                }
            }
            $check->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';">
    <title>Registration</title>
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/register+login.css" />
</head>
<body>
    <div>
        <h1 id="header"><a href="../index.php">Todo App</a></h1>
    </div>
    <div id="cover">
        <div id="form_background">
            <div id="form_container">
                <div class="container">
                    <h2 style="color: #05558F;">Registration</h2>
                </div>
                <form method="post" action="" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="container">
                        <input type="text" name="username" placeholder="Username" required 
                            value="<?php echo htmlspecialchars($username); ?>"
                            maxlength="30" pattern="[a-zA-Z0-9_]{4,30}" 
                            title="4-30 characters (letters, numbers, underscores)">
                    </div>
                    <div class="container password-container">
                        <input type="password" name="password" id="password" placeholder="Password" required
                            maxlength="64" minlength="12">
                        
                        <div class="password-feedback">
                            <div id="password_strength">
                                <div id="password_strength_bar"></div>
                            </div>
                            <div class="password-hints">
                                <span id="length_hint" class="invalid">✓ 12-64 characters</span>
                                <span id="uppercase_hint" class="invalid">✓ 1 uppercase letter</span>
                                <span id="number_hint" class="invalid">✓ 1 number</span>
                                <span id="special_char_hint" class="invalid">✓ 1 special character</span>
                            </div>
                        </div>
                    </div>
                    <div class="container">
                        <input type="password" name="password_confirm" id="password_confirm" placeholder="Confirm Password" required />
                        <span id="password_match" style="display:none; color:red;">Passwords do not match</span>
                    </div>
                    <div class="container">
                        <button type="submit" id="submitBtn">Register</button>
                    </div>
                </form>
                <?php if ($error): ?>
                    <p style="color:red; text-align:center;"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <?php if ($success): ?>
                    <p style="color:green; text-align:center;"><?php echo $success; ?></p>
                    <script>
                        setTimeout(function() {
                            window.location.href = "login.php";
                        }, 2000);
                    </script>
                <?php endif; ?>
                <div class="container" style="text-align: center;">
                    <p>Already have an account? <a href="login.php" style="color: blue;"><br>Log in</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Username restrictions
        document.querySelector('input[name="username"]').addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');
            if (this.value.length > 30) {
                this.value = this.value.substring(0, 30);
            }
        });

        // Password restrictions and validation
        document.getElementById('password').addEventListener('input', function() {
            if (this.value.length > 64) {
                this.value = this.value.substring(0, 64);
            }
            
            // Update validation
            const password = this.value;
            const hasLength = password.length >= 12;
            const hasUpper = /[A-Z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            const isCommon = /^(password|123456|qwerty)/i.test(password);
            
            document.getElementById('length_hint').className = hasLength ? 'valid' : 'invalid';
            document.getElementById('uppercase_hint').className = hasUpper ? 'valid' : 'invalid';
            document.getElementById('number_hint').className = hasNumber ? 'valid' : 'invalid';
            document.getElementById('special_char_hint').className = hasSpecialChar ? 'valid' : 'invalid';
            
            // Update password strength
            const strength = (hasLength ? 1 : 0) + (hasUpper ? 1 : 0) + 
                            (hasNumber ? 1 : 0) + (hasSpecialChar ? 1 : 0);
            const strengthBar = document.getElementById('password_strength_bar');
            strengthBar.style.width = (strength * 25) + '%';
            
            // Update strength color
            if (strength < 2 || isCommon) {
                strengthBar.style.background = '#dc3545'; // Red
            } else if (strength === 2) {
                strengthBar.style.background = '#ffc107'; // Yellow
            } else if (strength === 3) {
                strengthBar.style.background = '#fd7e14'; // Orange
            } else {
                strengthBar.style.background = '#28a745'; // Green
            }

            // Disable submit if common password
            document.getElementById('submitBtn').disabled = isCommon;
        });

        // Password match check
        document.getElementById('password_confirm').addEventListener('input', function() {
            const match = document.getElementById('password').value === this.value;
            document.getElementById('password_match').style.display = match ? 'none' : 'block';
            document.getElementById('submitBtn').disabled = !match || 
                document.getElementById('password_match').style.display === 'block';
        });

        // Initial state
        document.getElementById('submitBtn').disabled = true;
    </script>
</body>
</html>