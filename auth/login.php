<?php
session_start();
require_once '../includes/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        if (!$stmt) {
            $error = "Database error.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $hashed_password);
                $stmt->fetch();

                if (password_verify($password, $hashed_password)) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;

                    $stmt->close();
                    $conn->close();

                    header("Location: ../dashboard.php");
                    exit;
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "User not found.";
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
    <title>Login</title>
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/register+login.css" />
</head>
<body>
    <div>
        <h1 id="header">Todo App</h1>
    </div>
    <div id="sides_cover">

        <!-- Left -->
        <div class="sides"></div>

        <!-- Middle -->
        <div id="form_background">
            <div>
                <div class="container">
                    <h2 style="color: white;">Login</h2>
                </div>
                <form method="post" action="">
                    <div class="container">
                        <input type="text" name="username" placeholder="Username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" />
                    </div>
                    <div class="container">
                        <input type="password" name="password" placeholder="Password" required />
                    </div>
                    <div class="container">
                        <button type="submit">Login</button>
                    </div>
                </form>
                <?php if ($error): ?>
                    <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <div class="container" style="text-align: center;">
                    <p style="text-align: center; color: white;">Don't have an account? <a href="register.php" style="color: blue;"><br>Sign up</a></p>
                </div>
            </div>
        </div>
        
        <!-- Right -->
        <div class="sides"></div>

    </div>
</body>
</html>
