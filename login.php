<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit();
}

// Check for logout message
$logout_message = '';
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $logout_message = 'You have been successfully logged out.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($auth->login($username, $password)) {
        header('Location: admin/dashboard.php');
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - RASA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .logout-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
            border: 1px solid #c3e6cb;
        }
        
        .login-container {
            max-width: 400px;
            margin: 2rem auto;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h2 {
            color: var(--primary-green);
            font-size: 2rem;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        .login-footer a {
            color: var(--primary-green);
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo-container">
                <img src="assets/images/logo.jpg" alt="RASA Logo" class="logo">
                <div class="site-title">
                    <h1>RASA RP MUSANZE COLLEGE</h1>
                    <p>Rwanda Anglican Student Association</p>
                </div>
            </div>
        </div>
    </header>
    
    <main class="container">
        <div class="login-container">
            
            
            <?php if (!empty($logout_message)): ?>
                <div class="logout-message" id="logoutMessage">
                    <?php echo $logout_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required 
                               placeholder="Enter your username"
                               autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required 
                               placeholder="Enter your password">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="remember" value="1"> Remember me
                        </label>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%;">Login</button>
                </form>
            </div>
            
            <div class="login-footer">
                <p><a href="index.php">← Return to Homepage</a></p>
                
            </div>
        </div>
    </main>
    
    <script>
        // Auto-hide logout message after 5 seconds
        setTimeout(function() {
            const logoutMsg = document.getElementById('logoutMessage');
            if (logoutMsg) {
                logoutMsg.style.transition = 'opacity 0.5s';
                logoutMsg.style.opacity = '0';
                setTimeout(function() {
                    logoutMsg.style.display = 'none';
                }, 500);
            }
        }, 5000);
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please enter both username and password');
            }
        });
        
        // Add loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.textContent = 'Logging in...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>