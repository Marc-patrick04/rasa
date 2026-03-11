<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$database = new Database();
$db = $database->getConnection();

// Get all previous leaders with position names
$query = "
    SELECT pl.*, p.name as position_name, p.description as position_description
    FROM previous_leaders pl
    LEFT JOIN positions p ON pl.position_id = p.id
    ORDER BY pl.year_served DESC, pl.full_name ASC
";
$previous_leaders = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Group leaders by year for better display
$leaders_by_year = [];
foreach ($previous_leaders as $leader) {
    $year = $leader['year_served'];
    if (!isset($leaders_by_year[$year])) {
        $leaders_by_year[$year] = [];
    }
    $leaders_by_year[$year][] = $leader;
}

// Get unique years for filter
$years = array_keys($leaders_by_year);
rsort($years);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previous Leaders - RASA RP MUSANZE COLLEGE</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Additional styles for previous leaders page */
        .leaders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .leader-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border: 1px solid #eee;
        }
        
        .leader-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(46, 125, 50, 0.2);
        }
        
        .leader-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, var(--primary-green), var(--light-green));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 4rem;
            font-weight: bold;
        }
        
        .leader-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .leader-info {
            padding: 1.5rem;
        }
        
        .leader-name {
            color: var(--primary-green);
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .leader-position {
            color: var(--light-green);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .leader-year {
            background: var(--light-gray);
            display: inline-block;
            padding: 0.2rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            color: var(--primary-green);
            font-weight: 600;
        }
        
        .leader-achievements {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .year-section {
            margin: 3rem 0;
        }
        
        .year-title {
            color: var(--primary-green);
            font-size: 2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid var(--light-green);
            display: inline-block;
        }
        
        .filter-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin: 2rem 0;
            justify-content: center;
        }
        
        .filter-btn {
            padding: 0.5rem 1.5rem;
            border: 2px solid var(--primary-green);
            background: transparent;
            color: var(--primary-green);
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary-green);
            color: var(--white);
        }
        
        .no-leaders {
            text-align: center;
            padding: 3rem;
            background: var(--light-gray);
            border-radius: 10px;
            color: #666;
            font-size: 1.2rem;
        }
        
        .leader-initials {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-green), var(--light-green));
            color: var(--white);
            font-size: 3rem;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .leaders-grid {
                grid-template-columns: 1fr;
            }
            
            .year-title {
                font-size: 1.5rem;
            }
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
            <li><a href="previous-leaders.php" class="active">Previous Leaders</a></li>
            <li><a href="about.php">About RASA</a></li>
            <li><a href="login.php"> Login</a></li>
        </ul>
    </nav>
    
    <main class="container">
        <section class="hero" style="background: linear-gradient(rgba(46, 125, 50, 0.9), rgba(76, 175, 80, 0.9));">
            <h2>Our Previous Leaders</h2>
            <p>Honoring those who have served RASA with dedication and passion</p>
            <div class="slogan">
                <span>AGAKIZA</span> | <span>URUKUNDO</span> | <span>UMURIMO</span>
            </div>
        </section>
        
        <?php if (!empty($years)): ?>
            <!-- Filter Buttons -->
            <div class="filter-buttons">
                <button class="filter-btn active" onclick="filterLeaders('all')">All Years</button>
                <?php foreach ($years as $year): ?>
                    <button class="filter-btn" onclick="filterLeaders('<?php echo $year; ?>')"><?php echo $year; ?></button>
                <?php endforeach; ?>
            </div>
            
            <!-- Leaders Display -->
            <div id="leaders-container">
                <?php foreach ($leaders_by_year as $year => $leaders): ?>
                    <div class="year-section" data-year="<?php echo $year; ?>">
                        <h2 class="year-title"><?php echo $year; ?> Leadership</h2>
                        <div class="leaders-grid">
                            <?php foreach ($leaders as $leader): ?>
                                <div class="leader-card">
                                    <div class="leader-image">
                                        <?php if (!empty($leader['photo_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($leader['photo_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($leader['full_name']); ?>">
                                        <?php else: ?>
                                            <div class="leader-initials">
                                                <?php 
                                                $initials = '';
                                                $names = explode(' ', $leader['full_name']);
                                                foreach ($names as $name) {
                                                    $initials .= strtoupper(substr($name, 0, 1));
                                                }
                                                echo substr($initials, 0, 2);
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="leader-info">
                                        <div class="leader-name"><?php echo htmlspecialchars($leader['full_name']); ?></div>
                                        <div class="leader-position"><?php echo htmlspecialchars($leader['position_name'] ?? 'Position Not Specified'); ?></div>
                                        <div class="leader-year"><?php echo $year; ?></div>
                                        <?php if (!empty($leader['achievements'])): ?>
                                            <div class="leader-achievements">
                                                <strong>Achievements:</strong>
                                                <p><?php echo nl2br(htmlspecialchars($leader['achievements'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-leaders">
                <h3>No Previous Leaders Found</h3>
                <p>Check back soon as we update our history!</p>
            </div>
        <?php endif; ?>
        
        <!-- Legacy Section -->
        <section style="margin: 4rem 0; text-align: center;">
            <h2 style="color: var(--primary-green); margin-bottom: 2rem;">Our Legacy of Leadership</h2>
            <div class="card-container">
                <div class="card">
                    <h3>Total Leaders</h3>
                    <p style="font-size: 2.5rem; font-weight: bold; color: var(--primary-green);">
                        <?php echo count($previous_leaders); ?>
                    </p>
                </div>
                
                <div class="card">
                    <h3>Different Positions</h3>
                    <p style="font-size: 2.5rem; font-weight: bold; color: var(--primary-green);">
                        <?php 
                        $unique_positions = array_unique(array_column($previous_leaders, 'position_name'));
                        echo count(array_filter($unique_positions)); 
                        ?>
                    </p>
                </div>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2026 RASA RP MUSANZE COLLEGE. All rights reserved.</p>
        <p>AGAKIZA - URUKUNDO - UMURIMO</p>
    </footer>
    
    <script>
        function filterLeaders(year) {
            // Update active button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Show/hide year sections
            const sections = document.querySelectorAll('.year-section');
            if (year === 'all') {
                sections.forEach(section => {
                    section.style.display = 'block';
                });
            } else {
                sections.forEach(section => {
                    if (section.dataset.year === year) {
                        section.style.display = 'block';
                    } else {
                        section.style.display = 'none';
                    }
                });
            }
        }
    </script>
</body>
</html>