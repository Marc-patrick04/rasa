<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$database = new Database();
$db = $database->getConnection();

// Get statistics for about page
$stats = [
    'total_members' => $db->query("SELECT COUNT(DISTINCT full_name) FROM candidates")->fetchColumn() ?: 0,
    'total_positions' => $db->query("SELECT COUNT(*) FROM positions WHERE is_active = true")->fetchColumn() ?: 0,
    'total_leaders' => $db->query("SELECT COUNT(*) FROM previous_leaders")->fetchColumn() ?: 0,
    
];

// Get recent leaders for showcase
$recent_leaders = $db->query("
    SELECT pl.*, p.name as position_name 
    FROM previous_leaders pl
    LEFT JOIN positions p ON pl.position_id = p.id
    ORDER BY pl.year_served DESC 
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About RASA - Rwanda Anglican Student Association</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* About page specific styles */
        .about-hero {
            background: linear-gradient(135deg, var(--primary-green), var(--light-green));
            color: var(--white);
            padding: 4rem 2rem;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 3rem;
        }
        
        .about-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .about-hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
            opacity: 0.95;
        }
        
        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 3rem 0;
        }
        
        .mission-box, .vision-box {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .mission-box::before, .vision-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-green);
        }
        
        .mission-box h2, .vision-box h2 {
            color: var(--primary-green);
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }
        
        .slogan-section {
            background: linear-gradient(135deg, var(--primary-green), var(--light-green));
            color: var(--white);
            padding: 4rem 2rem;
            border-radius: 10px;
            margin: 3rem 0;
            text-align: center;
        }
        
        .slogan-items {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }
        
        .slogan-item {
            text-align: center;
            flex: 1;
            min-width: 200px;
        }
        
        .slogan-item h3 {
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: bold;
        }
        
        .slogan-item p {
            font-size: 1.2rem;
            opacity: 0.95;
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }
        
        .value-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .value-card:hover {
            transform: translateY(-5px);
        }
        
        .value-icon {
            width: 80px;
            height: 80px;
            background: var(--light-green);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .value-card h3 {
            color: var(--primary-green);
            margin-bottom: 1rem;
        }
        
        .history-section {
            background: var(--light-gray);
            padding: 3rem;
            border-radius: 10px;
            margin: 3rem 0;
        }
        
        .history-section h2 {
            color: var(--primary-green);
            margin-bottom: 2rem;
        }
        
        .timeline {
            position: relative;
            padding: 2rem 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 100%;
            background: var(--primary-green);
        }
        
        .timeline-item {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            position: relative;
        }
        
        .timeline-item:nth-child(even) {
            flex-direction: row-reverse;
        }
        
        .timeline-content {
            width: 45%;
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .timeline-year {
            color: var(--primary-green);
            font-weight: bold;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .mission-vision {
                grid-template-columns: 1fr;
            }
            
            .timeline::before {
                left: 0;
            }
            
            .timeline-item,
            .timeline-item:nth-child(even) {
                flex-direction: column;
                margin-left: 1rem;
            }
            
            .timeline-content {
                width: 100%;
            }
        }
        
        .leadership-showcase {
            margin: 4rem 0;
        }
        
        .showcase-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .showcase-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .showcase-image {
            height: 200px;
            background: linear-gradient(135deg, var(--primary-green), var(--light-green));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 3rem;
            font-weight: bold;
        }
        
        .showcase-info {
            padding: 1.5rem;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }
        
        .stat-box {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-bottom: 4px solid var(--primary-green);
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary-green);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1rem;
        }
        
        .contact-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 3rem 0;
        }
        
        .contact-info {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
        }
        
        .contact-info h3 {
            color: var(--primary-green);
            margin-bottom: 1.5rem;
        }
        
        .contact-detail {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .contact-icon {
            width: 40px;
            height: 40px;
            background: var(--light-green);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
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
    
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="nominate.php">Nominate</a></li>
            <li><a href="previous-leaders.php">Previous Leaders</a></li>
            <li><a href="about.php" class="active">About RASA</a></li>
            <li><a href="login.php"> Login</a></li>
        </ul>
    </nav>
    
    <main class="container">
        <!-- Hero Section -->
        <section class="about-hero">
            <h1>About Rwanda Anglican Student Association</h1>
            <p>RASA RP MUSANZE COLLEGE - Nurturing faith, leadership, and service</p>
        </section>
        
        <!-- Statistics -->
        <div class="stats-container">
            
            <!-- <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_members']; ?>+</div>
                <div class="stat-label">Active Members</div>
            </div> -->
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_leaders']; ?></div>
                <div class="stat-label">Previous Leaders</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_positions']; ?></div>
                <div class="stat-label">Leadership Positions</div>
            </div>
        </div>
        
        <!-- Mission and Vision -->
        <div class="mission-vision">
            <div class="mission-box">
                <h2>Our Mission</h2>
                <p style="font-size: 1.2rem; line-height: 1.8;">
                    To unite Anglican students at RP MUSANZE COLLEGE in faith, foster spiritual growth, 
                    and develop servant leaders who will make a positive impact in their communities 
                    through the values of Salvation, Love, and Work.
                </p>
            </div>
            
            <div class="vision-box">
                <h2>Our Vision</h2>
                <p style="font-size: 1.2rem; line-height: 1.8;">
                    To be a beacon of Christian leadership and academic excellence, producing 
                    graduates who exemplify Christ-like character and contribute meaningfully to 
                    the church and society in Rwanda and beyond.
                </p>
            </div>
        </div>
        
        <!-- Slogan Section -->
        <section class="slogan-section">
            <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Our Guiding Principles</h2>
            <p style="font-size: 1.2rem; margin-bottom: 2rem;">The three pillars of RASA</p>
            
            <div class="slogan-items">
                <div class="slogan-item">
                    <h3>AGAKIZA</h3>
                    <p>Salvation</p>
                    <small>Seeking spiritual redemption and eternal life through Christ</small>
                </div>
                <div class="slogan-item">
                    <h3>URUKUNDO</h3>
                    <p>Love</p>
                    <small>Demonstrating God's love to all through acts of kindness and compassion</small>
                </div>
                <div class="slogan-item">
                    <h3>UMURIMO</h3>
                    <p>Work</p>
                    <small>Serving diligently in God's vineyard and our community</small>
                </div>
            </div>
        </section>
        
        
        
        
        
        <!-- Recent Leaders Showcase -->
        <?php if (!empty($recent_leaders)): ?>
        <section class="leadership-showcase">
            <h2 style="color: var(--primary-green); text-align: center;">Recent Leaders</h2>
            <p style="text-align: center; margin-bottom: 2rem;">Some of our dedicated servants who have led RASA</p>
            
            <div class="showcase-grid">
                <?php foreach ($recent_leaders as $leader): ?>
                <div class="showcase-card">
                    <div class="showcase-image">
                        <?php 
                        $initials = '';
                        $names = explode(' ', $leader['full_name']);
                        foreach ($names as $name) {
                            $initials .= strtoupper(substr($name, 0, 1));
                        }
                        echo substr($initials, 0, 2);
                        ?>
                    </div>
                    <div class="showcase-info">
                        <h3 style="color: var(--primary-green);"><?php echo htmlspecialchars($leader['full_name']); ?></h3>
                        <p><strong><?php echo htmlspecialchars($leader['position_name'] ?? 'Leader'); ?></strong></p>
                        <p style="color: #666;"><?php echo $leader['year_served']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Contact Information -->
        
        
        <!-- Join Us Section -->
        <section style="background: linear-gradient(135deg, var(--primary-green), var(--light-green)); color: var(--white); padding: 3rem; border-radius: 10px; margin: 3rem 0; text-align: center;">
            <h2 style="font-size: 2rem; margin-bottom: 1rem;">Join RASA Today</h2>
            <p style="font-size: 1.2rem; margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                Be part of a community that grows in faith, builds lasting friendships, and develops leadership skills.
            </p>
            <a href="nominate.php" class="btn" style="background: var(--white); color: var(--primary-green); font-size: 1.2rem; padding: 1rem 2rem;">Get Involved</a>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2026 RASA RP MUSANZE COLLEGE. All rights reserved.</p>
        <p>AGAKIZA - URUKUNDO - UMURIMO</p>
    </footer>
    
    <script>
        // Contact form handler
        function submitContactForm(event) {
            event.preventDefault();
            
            const form = document.getElementById('contactForm');
            const response = document.getElementById('formResponse');
            const formData = new FormData(form);
            
            // Simulate form submission (in production, send to server)
            response.style.display = 'block';
            response.className = 'alert alert-success';
            response.innerHTML = 'Thank you for your message! We will get back to you soon.';
            response.style.background = '#d4edda';
            response.style.color = '#155724';
            response.style.padding = '1rem';
            response.style.borderRadius = '5px';
            
            form.reset();
            
            // Hide after 5 seconds
            setTimeout(() => {
                response.style.display = 'none';
            }, 5000);
            
            return false;
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.value-card, .timeline-item, .stat-box').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>