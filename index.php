<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RASA - Rwanda Anglican Student Association</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
    
    <nav>
        <ul>
            <li><a href="index.php" class="active">Home</a></li>
            <li><a href="nominate.php">Nominate/Kwamamaza</a></li>
            <li><a href="previous-leaders.php">Previous Leaders</a></li>
            <li><a href="about.php">About RASA</a></li>
            <li><a href="login.php">Login</a></li>
        </ul>
    </nav>
    
    <main class="container">
        <section class="hero">
            <h2>Welcome to RASA RP MUSANZE COLLEGE</h2>
            <p>Empowering Students Through Faith and Leadership</p>
            <div class="slogan">
                <span>AGAKIZA</span> | <span>URUKUNDO</span> | <span>UMURIMO</span>
            </div>
            <p>Salvation | Love | Work</p>
        </section>
        
        <div class="card-container">
            <div class="card">
                <h3>Self Nomination</h3>
                <p>Nominate yourself for a leadership position</p>
                <a href="nominate.php?type=self" class="btn">Nominate Self/Gutanga candidatire yawe</a>
            </div>
            
            <div class="card">
                <h3>Nominate Others</h3>
                <p>Nominate a fellow student for leadership</p>
                <a href="nominate.php?type=other" class="btn">Nominate Others/Gutanga umukandida</a>
            </div>
            
            <div class="card">
                <h3>Previous Leaders</h3>
                <p>View our past leaders and their achievements</p>
                <a href="previous-leaders.php" class="btn">View Leaders</a>
            </div>
            
            <div class="card">
                <h3>About RASA</h3>
                <p>Learn more about our association and values</p>
                <a href="about.php" class="btn">Learn More</a>
            </div>
        </div>
    </main>
    
    <footer>
        <p>&copy; 2026 RASA RP MUSANZE COLLEGE. All rights reserved.</p>
        <p>AGAKIZA - URUKUNDO - UMURIMO</p>
    </footer>
    
    <script src="assets/js/main.js"></script>
</body>
</html>