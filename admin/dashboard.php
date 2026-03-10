<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';
$database = new Database();
$db = $database->getConnection();

// Get counts for dashboard
$positions_count = $db->query("SELECT COUNT(*) FROM positions")->fetchColumn();
$candidates_count = $db->query("SELECT COUNT(*) FROM candidates WHERE is_active = true")->fetchColumn();
$previous_leaders_count = $db->query("SELECT COUNT(*) FROM previous_leaders")->fetchColumn();

// Get current time for greeting
$hour = date('H');
$greeting = '';
if ($hour < 12) {
    $greeting = 'Good Morning';
} elseif ($hour < 18) {
    $greeting = 'Good Afternoon';
} else {
    $greeting = 'Good Evening';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RASA</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .user-info p {
            margin: 0.5rem 0;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            margin-top: 1rem;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
        .logout-confirm {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            z-index: 1000;
            display: none;
            max-width: 400px;
            width: 90%;
        }
        
        .logout-confirm.active {
            display: block;
        }
        
        .logout-confirm h3 {
            color: var(--primary-green);
            margin-bottom: 1rem;
        }
        
        .logout-confirm p {
            margin-bottom: 2rem;
            color: #666;
        }
        
        .confirm-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }
        
        .overlay.active {
            display: block;
        }
        
        /* Mobile Toggle Button */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            width: 40px;
            height: 40px;
            background: var(--primary-green);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            z-index: 1001;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .mobile-toggle span {
            display: block;
            width: 25px;
            height: 3px;
            background: white;
            margin: 3px 0;
            transition: all 0.3s ease;
            transform-origin: center;
        }
        
        .mobile-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        
        .mobile-toggle.active span:nth-child(2) {
            opacity: 0;
        }
        
        .mobile-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }
        
        .mobile-toggle:hover {
            background: var(--light-green);
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        /* Close Sidebar Button */
        .close-sidebar {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .close-sidebar:hover {
            background: rgba(255,255,255,0.1);
        }
        
        /* Responsive Sidebar */
        @media (max-width: 768px) {
            .mobile-toggle {
                display: flex;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 250px;
                height: 100%;
                z-index: 1000;
                transition: left 0.3s ease;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .close-sidebar {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }
            
            .admin-container {
                flex-direction: column;
            }
            
            /* Overlay for mobile */
            .sidebar.active + .main-content {
                filter: blur(2px);
            }
        }
        
        @media (min-width: 769px) {
            .sidebar {
                position: relative;
                left: 0;
                width: 250px;
                height: auto;
                box-shadow: none;
            }
            
            .main-content {
                margin-left: 0;
                width: calc(100% - 250px);
            }
            
            .admin-container {
                flex-direction: row;
            }
        }
    </style>
</head>
<body>
    <!-- Logout Confirmation Modal -->
    <div class="overlay" id="overlay"></div>
    <div class="logout-confirm" id="logoutConfirm">
        <h3>Confirm Logout</h3>
        <p>Are you sure you want to logout from the admin panel?</p>
        <div class="confirm-buttons">
            <button class="btn btn-outline" onclick="hideLogoutConfirm()">Cancel</button>
            <a href="logout.php" class="btn btn-danger">Yes, Logout</a>
        </div>
    </div>
    
    <div class="admin-container">
        <!-- Mobile Toggle Button -->
        <button class="mobile-toggle" onclick="toggleSidebar()" id="mobileToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>RASA Admin</h2>
                <button class="close-sidebar" onclick="toggleSidebar()" id="closeSidebar">×</button>
            </div>
            
            <ul class="sidebar-menu">
                <li class="active"><a href="dashboard.php">Dashboard</a></li>
                <li><a href="positions.php">Positions</a></li>
                <li><a href="candidates.php">Candidates</a></li>
                <li><a href="previous-leaders.php">Previous Leaders</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="#" onclick="showLogoutConfirm(); return false;">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <!-- Rest of your dashboard content remains the same -->
            <h1>Dashboard</h1>
            
            <div class="card-container">
                <div class="card">
                    <h3>Total Positions</h3>
                    <p style="font-size: 2rem; font-weight: bold;"><?php echo $positions_count; ?></p>
                    <a href="positions.php" class="btn btn-small">Manage</a>
                </div>
                
                <div class="card">
                    <h3>Active Candidates</h3>
                    <p style="font-size: 2rem; font-weight: bold;"><?php echo $candidates_count; ?></p>
                    <a href="candidates.php" class="btn btn-small">Manage</a>
                </div>
                
                <div class="card">
                    <h3>Previous Leaders</h3>
                    <p style="font-size: 2rem; font-weight: bold;"><?php echo $previous_leaders_count; ?></p>
                    <a href="previous-leaders.php" class="btn btn-small">Manage</a>
                </div>
            </div>
            
            <div style="margin-top: 2rem;">
                <h2>Recent Activity</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent = $db->query("
                                SELECT c.*, p.name as position_name 
                                FROM candidates c 
                                JOIN positions p ON c.position_id = p.id 
                                WHERE c.is_active = true 
                                ORDER BY c.created_at DESC 
                                LIMIT 5
                            ")->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($recent as $candidate): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($candidate['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($candidate['position_name']); ?></td>
                                <td><?php echo $candidate['nomination_type'] === 'self' ? 'Self' : 'Nominated'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($candidate['created_at'])); ?></td>
                                <td><span class="badge badge-success">Active</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.getElementById('mobileToggle');
            
            sidebar.classList.toggle('active');
            mobileToggle.classList.toggle('active');
            
            // Prevent body scroll when sidebar is open on mobile
            if (sidebar.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
        
        function showLogoutConfirm() {
            document.getElementById('overlay').classList.add('active');
            document.getElementById('logoutConfirm').classList.add('active');
        }
        
        function hideLogoutConfirm() {
            document.getElementById('overlay').classList.remove('active');
            document.getElementById('logoutConfirm').classList.remove('active');
        }
        
        // Close modal when clicking overlay
        document.getElementById('overlay').addEventListener('click', hideLogoutConfirm);
        
        // Keyboard shortcut: Ctrl+Shift+L for logout
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'L') {
                e.preventDefault();
                showLogoutConfirm();
            }
        });
        
        // Auto logout after 30 minutes of inactivity (optional)
        let inactivityTimer;
        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(function() {
                if (confirm('You have been inactive for 30 minutes. Do you want to stay logged in?')) {
                    resetInactivityTimer();
                } else {
                    window.location.href = 'logout.php';
                }
            }, 30 * 60 * 1000); // 30 minutes
        }
        
        // Reset timer on user activity
        document.addEventListener('mousemove', resetInactivityTimer);
        document.addEventListener('keypress', resetInactivityTimer);
        resetInactivityTimer();
    </script>
</body>
</html>