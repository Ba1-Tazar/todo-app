<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TodoApp - Simple Task Manager</title>
    <link rel="stylesheet" href="./css/index.css" />
</head>
<body>
    <header>
        <div class="logo">TodoApp</div>
        <nav>
            <a href="#features">Features</a>
            <a href="#about">About</a>
            <a href="auth/login.php">Login</a>
        </nav>
    </header>
    
    <main class="hero">
        <h1>Organize Your Life</h1>
        <p>Simple, beautiful task management to help you stay productive and stress-free. Access your todos anywhere, anytime.</p>
        <div>
            <a href="auth/register.php" class="btn">Get Started - It's Free</a>
            <a href="#demo" class="btn btn-outline">See Demo</a>
        </div>
    </main>
    
    <section id="features" style="padding: 4rem 1rem; background-color: rgba(0,0,0,0.5); width: 100%; margin-top: 2rem;">
        <h2 style="text-align: center; margin-bottom: 2rem;">Key Features</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; max-width: 1000px; margin: 0 auto;">
            <div style="background-color: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 8px;">
                <h3 style="color: #4cc9f0; margin-bottom: 1rem;">Simple Interface</h3>
                <p>Intuitive design that helps you focus on what matters.</p>
            </div>
            <div style="background-color: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 8px;">
                <h3 style="color: #4cc9f0; margin-bottom: 1rem;">Cross-Platform</h3>
                <p>Works on all your devices seamlessly.</p>
            </div>
            <div style="background-color: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 8px;">
                <h3 style="color: #4cc9f0; margin-bottom: 1rem;">Secure</h3>
                <p>Your data is always protected.</p>
            </div>
        </div>
    </section>

    
</body>
</html> 