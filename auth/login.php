<?php
session_start();
require_once '../includes/db.php';

// Generate CSRF token if not exists - Zawsze na początku, niezależnie od metody
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$error = "";
$username = "";
$login_attempts = $_SESSION['login_attempts'] ?? 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Błąd CSRF - niedozwolone żądanie.");
    }
    // Rate limiting (max 5 attempts in 5 minutes)
    elseif ($login_attempts >= 5 && time() - ($_SESSION['last_attempt_time'] ?? 0) < 300) {
        $error = "Too many login attempts. Please try again later.";
    }
    // Validate inputs
    elseif (empty($username) || empty($password)) {
        $error = "All fields are required.";
        $_SESSION['login_attempts'] = ++$login_attempts;
        $_SESSION['last_attempt_time'] = time();
    } else {
        $stmt = $conn->prepare("SELECT id, password, is_locked FROM users WHERE username = ?");
        if (!$stmt) {
            $error = "Database error. Please try again later.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $hashed_password, $is_locked);
                $stmt->fetch();

                if ($is_locked) {
                    $error = "Account locked. Please contact support.";
                } elseif (password_verify($password, $hashed_password)) {
                    // Successful login
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['last_login'] = time();

                    // Regenerate session ID
                    session_regenerate_id(true);

                    $stmt->close();
                    $conn->close();

                    header("Location: ../dashboard.php");
                    exit;
                } else {
                    $error = "Incorrect username or password.";
                    $_SESSION['login_attempts'] = ++$login_attempts;
                    $_SESSION['last_attempt_time'] = time();

                    if ($login_attempts >= 5) {
                        $lock_stmt = $conn->prepare("UPDATE users SET is_locked = 1 WHERE username = ?");
                        $lock_stmt->bind_param("s", $username);
                        $lock_stmt->execute();
                        $lock_stmt->close();
                        $error = "Too many failed attempts. Account locked.";
                    }
                }
            } else {
                $error = "Incorrect username or password.";
                $_SESSION['login_attempts'] = ++$login_attempts;
                $_SESSION['last_attempt_time'] = time();
            }

            $stmt->close();
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | Todo App</title>
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/register+login.css" />
</head>
<body>
    <div>
        <h1 id="header"><a href="../index.php">Todo App</a></h1>
    </div>
    <div id="cover">
        <div id="form_background">
            <div>
                <div class="container">
                    <h2 style="color: #05558F;">Login</h2>
                </div>
                <form method="post" action="" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div class="container">
                        <input type="text" name="username" placeholder="Username" required 
                               value="<?php echo htmlspecialchars($username); ?>" 
                               maxlength="30" pattern="[a-zA-Z0-9_]{4,30}"
                               title="4-30 characters (letters, numbers, underscores)">
                    </div>
                    <div class="container">
                        <input type="password" name="password" id="password" placeholder="Password" required maxlength="64" minlength="12">
                        
                        <div class="show-password-container">
                            <input type="checkbox" id="showPassword">
                            <label for="showPassword">Show password</label>
                        </div>
                    </div>
                    <div class="container">
                        <button type="submit">Login</button>
                    </div>
                </form>
                
                <?php if ($error): ?>
                    <p style="color: red; text-align: center; margin: 10px 0;"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                
                <div class="container" style="text-align: center;">
                    <p style="color: white;">
                        Don't have an account? <br><a href="register.php" style="color:rgb(17, 0, 255);">Sign up</a>
                        <br>
                        <br>
                        <!-- <a href="forgot_password.php" style="color:rgb(0, 17, 255);">Forgot password?</a> -->
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show/hide password toggle
        document.getElementById('showPassword').addEventListener('change', function() {
            const passwordField = document.getElementById('password');
            passwordField.type = this.checked ? 'text' : 'password';
        });

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.querySelector('input[name="username"]').value;
            const password = document.getElementById('password').value;
            
            if (username.length < 4 || username.length > 30) {
                alert('Username must be between 4 and 30 characters');
                e.preventDefault();
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>