<?php
require_once '../includes/db.php';

$error = "";
$success = "";

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'empty':
            $error = "All fields are required.";
            break;
        case 'db':
            $error = "Database error. Please try again later.";
            break;
        case 'username_taken':
            $error = "Username is already taken.";
            break;
        case 'register_failed':
            $error = "Registration failed. Please try again.";
            break;
        default:
            $error = "Unknown error.";
    }
}

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = "Registration successful!";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
    header("Location: register.php?error=empty");
    exit;
    }

    // Check if username exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if (!$check) {
        header("Location: register.php?error=db");
        exit;
    }
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        $conn->close();
        header("Location: register.php?error=username_taken");
        exit;
    }
    $check->close();

    // Insert user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    if (!$stmt) {
        header("Location: register.php?error=db");
        exit;
    }
    $stmt->bind_param("ss", $username, $hashed_password);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: register.php?success=1");
        exit;
    } else {
        $stmt->close();
        $conn->close();
        header("Location: register.php?error=register_failed");
        exit;
    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Registration</title>
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/register.css" />
</head>
<body>
    <h1 id="header">Todo App</h1>
    <div id="sides_cover">
        <div class="sides"></div>
        <div id="form_background">
            <div>
                <div class="container">
                    <h2>Registration</h2>
                </div>
                <form method="post" action="">
                    <div class="container">
                        <input type="text" name="username" placeholder="Username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" />
                    </div>
                    <div class="container">
                        <input type="password" name="password" placeholder="Password" required />
                    </div>
                    <div class="container">
                        <button type="submit">Register</button>
                    </div>
                </form>
                    <?php if ($error): ?>
                        <p style="color:red; text-align:center;"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <p style="color:green; text-align:center;"><?php echo $success; ?></p>
                        <script>
                            setTimeout(function () {
                                window.location.href = "login.php";
                            }, 2000); // 2000 ms = 2 sekundy
                        </script>
                    <?php endif; ?>
                    <?php if ($error || $success): ?>
                        <script>
                            if (history.replaceState) {
                                history.replaceState(null, "", "register.php");
                            }
                        </script>
                    <?php endif; ?>
                <div class="container" style="text-align: center;">
                    <p>Already have an account? <a href="login.php">Log in</a></p>
                </div>
            </div>
        </div>
        <div class="sides"></div>
    </div>
</body>
</html>
