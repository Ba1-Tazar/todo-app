<?php
session_start([
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'
]);

// Get rate limit info from session
$rateLimitInfo = $_SESSION['rate_limit_info'] ?? null;
$lastAttempt = $rateLimitInfo['last_attempt'] ?? time();
$attempts = $rateLimitInfo['attempts'] ?? 0;

// Calculate remaining time
$remaining_time = 300 - (time() - $lastAttempt);
$remaining_minutes = ceil($remaining_time / 60);

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';">
    <title>Too Many Attempts</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .error-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            text-align: center;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            color: #721c24;
        }
        .error-container h1 {
            font-size: 2rem;
            margin-bottom: 20px;
        }
        .error-container p {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        .error-container a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #721c24;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .error-container a:hover {
            background-color: #5a151a;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Too Many Attempts</h1>
        <p>You have exceeded the maximum number of registration attempts. Please try again later.</p>
        <p>Attempts: <?php echo htmlspecialchars($attempts); ?>/5</p>
        <p>You can try again in <?php echo htmlspecialchars($remaining_minutes); ?> minutes.</p>
        <a href="../index.php">Return to Homepage</a>
        <a href="register.php" id="tryAgain" style="display:none; margin-left:10px;">Try Again</a>
    </div>

    <script>
        // Enable try again button when time is up
        setTimeout(function() {
            document.getElementById('tryAgain').style.display = 'inline-block';
        }, <?php echo $remaining_time * 1000; ?>);
        
        // Update countdown every second
        let remaining = <?php echo $remaining_time; ?>;
        const countdownElement = document.querySelector('.error-container p:last-of-type');
        
        const countdownInterval = setInterval(function() {
            remaining--;
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            
            countdownElement.textContent = `You can try again in ${minutes}m ${seconds}s`;
            
            if (remaining <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    </script>
</body>
</html>