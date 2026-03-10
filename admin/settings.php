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

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            // Change admin password
            if ($_POST['action'] === 'change_password') {
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Verify current password
                $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($current_password, $user['password'])) {
                    if ($new_password === $confirm_password) {
                        if (strlen($new_password) >= 8) {
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                            $message = "Password changed successfully!";
                        } else {
                            $error = "New password must be at least 8 characters long.";
                        }
                    } else {
                        $error = "New passwords do not match.";
                    }
                } else {
                    $error = "Current password is incorrect.";
                }
            }
            
            // Update site settings
            elseif ($_POST['action'] === 'update_settings') {
                $site_name = $_POST['site_name'];
                $site_slogan = $_POST['site_slogan'];
                $contact_email = $_POST['contact_email'];
                $contact_phone = $_POST['contact_phone'];
                $address = $_POST['address'];
                $meeting_time = $_POST['meeting_time'];
                $enable_nominations = isset($_POST['enable_nominations']) ? 1 : 0;
                $require_approval = isset($_POST['require_approval']) ? 1 : 0;
                
                // You would typically store these in a settings table
                // For now, we'll simulate by storing in session or config file
                $_SESSION['settings'] = [
                    'site_name' => $site_name,
                    'site_slogan' => $site_slogan,
                    'contact_email' => $contact_email,
                    'contact_phone' => $contact_phone,
                    'address' => $address,
                    'meeting_time' => $meeting_time,
                    'enable_nominations' => $enable_nominations,
                    'require_approval' => $require_approval
                ];
                
                $message = "Settings updated successfully!";
            }
            
            // Create new admin user
            elseif ($_POST['action'] === 'create_admin') {
                $username = $_POST['username'];
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Check if username exists
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                
                if (!$stmt->fetch()) {
                    if ($password === $confirm_password) {
                        if (strlen($password) >= 8) {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                            $stmt->execute([$username, $hashed_password]);
                            $message = "New admin user created successfully!";
                        } else {
                            $error = "Password must be at least 8 characters long.";
                        }
                    } else {
                        $error = "Passwords do not match.";
                    }
                } else {
                    $error = "Username already exists.";
                }
            }
            
            // Backup database
            elseif ($_POST['action'] === 'backup') {
                // This would create a database backup
                // For now, we'll just show a message
                $message = "Database backup created successfully! (Demo mode)";
            }
            
            // Clear all data
            elseif ($_POST['action'] === 'clear_data' && $_POST['confirm'] === 'CLEAR ALL DATA') {
                $db->query("TRUNCATE TABLE candidates RESTART IDENTITY CASCADE");
                $db->query("TRUNCATE TABLE previous_leaders RESTART IDENTITY CASCADE");
                $message = "All data cleared successfully!";
            }
            
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get all admin users
$admins = $db->query("SELECT id, username, created_at FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Get current settings (from session or defaults)
$settings = $_SESSION['settings'] ?? [
    'site_name' => 'RASA RP MUSANZE COLLEGE',
    'site_slogan' => 'AGAKIZA - URUKUNDO - UMURIMO',
    'contact_email' => 'rasa@rpmusanze.edu.rw',
    'contact_phone' => '+250 788 888 888',
    'address' => 'RP MUSANZE COLLEGE, Musanze District, Northern Province, Rwanda',
    'meeting_time' => 'Sundays at 9:00 AM, Wednesdays at 4:00 PM',
    'enable_nominations' => 1,
    'require_approval' => 1
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - RASA Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
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
        /* Settings page specific styles */
        .settings-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .settings-card h2 {
            color: var(--primary-green);
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light-green);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .settings-card h2 i {
            font-size: 1.5rem;
        }
        
        .settings-section {
            margin-bottom: 2rem;
        }
        
        .settings-section h3 {
            color: #666;
            font-size: 1rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-row {
            margin-bottom: 1rem;
        }
        
        .form-row label {
            display: block;
            margin-bottom: 0.3rem;
            color: #555;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-row input[type="text"],
        .form-row input[type="email"],
        .form-row input[type="password"],
        .form-row input[type="tel"],
        .form-row textarea,
        .form-row select {
            width: 100%;
            padding: 0.7rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }
        
        .form-row input:focus,
        .form-row textarea:focus,
        .form-row select:focus {
            outline: none;
            border-color: var(--primary-green);
        }
        
        .form-row textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .checkbox-row input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-row label {
            cursor: pointer;
            color: #555;
        }
        
        .admin-list {
            list-style: none;
            padding: 0;
        }
        
        .admin-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem;
            background: #f9f9f9;
            border-radius: 5px;
            margin-bottom: 0.5rem;
        }
        
        .admin-info {
            display: flex;
            flex-direction: column;
        }
        
        .admin-username {
            font-weight: 600;
            color: var(--primary-green);
        }
        
        .admin-date {
            font-size: 0.8rem;
            color: #999;
        }
        
        .admin-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .danger-zone {
            background: #fff3f3;
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .danger-zone h3 {
            color: #dc3545;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .danger-button {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .danger-button:hover {
            background: #c82333;
        }
        
        .danger-button:disabled {
            background: #e0a0a5;
            cursor: not-allowed;
        }
        
        .confirmation-input {
            margin: 1rem 0;
            padding: 0.7rem;
            border: 2px solid #dc3545;
            border-radius: 5px;
            width: 100%;
            font-size: 0.9rem;
        }
        
        .confirmation-input::placeholder {
            color: #999;
            font-style: italic;
        }
        
        .settings-footer {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0 5px 5px 0;
        }
        
        .info-box p {
            margin: 0;
            color: #0c5460;
        }
        
        .password-strength {
            margin-top: 0.3rem;
            font-size: 0.85rem;
        }
        
        .strength-weak {
            color: #dc3545;
        }
        
        .strength-medium {
            color: #ffc107;
        }
        
        .strength-strong {
            color: #28a745;
        }
        
        .btn-save {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: background 0.3s;
        }
        
        .btn-save:hover {
            background: var(--light-green);
        }
        
        .btn-save:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
        }
        
        .tab-container {
            margin-bottom: 2rem;
        }
        
        .tabs {
            display: flex;
            gap: 0.5rem;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 0.5rem;
        }
        
        .tab {
            padding: 0.5rem 1rem;
            cursor: pointer;
            border-radius: 5px 5px 0 0;
            transition: all 0.3s;
        }
        
        .tab:hover {
            background: #f0f0f0;
        }
        
        .tab.active {
            background: var(--primary-green);
            color: white;
        }
        
        .tab-content {
            display: none;
            padding: 2rem 0;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Mobile Toggle Button -->
        <button class="mobile-toggle" id="mobileToggle" onclick="toggleSidebar()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>RASA Admin</h2>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="positions.php">Positions</a></li>
                <li><a href="candidates.php">Candidates</a></li>
                <li><a href="previous-leaders.php">Previous Leaders</a></li>
                <li class="active"><a href="settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1>Settings</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    ✅ <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    ❌ <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Tabs Navigation -->
            <div class="tab-container">
                <div class="tabs">
                    <div class="tab active" onclick="showTab('general')"> General</div>
                    <div class="tab" onclick="showTab('security')"> Security</div>
                    <div class="tab" onclick="showTab('admins')"> Admin Users</div>
                   
                </div>
            </div>
            
            <!-- General Settings Tab -->
            <div id="tab-general" class="tab-content active">
                <div class="settings-card">
                    <h2><span></span> General Settings</h2>
                    
                    <form method="POST" action="" onsubmit="return validateGeneralSettings()">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="info-box">
                            <p>Configure your RASA website information and preferences.</p>
                        </div>
                    
                        
                        <h3 style="margin: 1.5rem 0 1rem;">Nomination Settings</h3>
                        
                        <div class="checkbox-row">
                            <input type="checkbox" id="enable_nominations" name="enable_nominations" 
                                   <?php echo $settings['enable_nominations'] ? 'checked' : ''; ?>>
                            <label for="enable_nominations">Enable nominations (allow students to nominate)</label>
                        </div>
                        
                        <div class="checkbox-row">
                            <input type="checkbox" id="require_approval" name="require_approval" 
                                   <?php echo $settings['require_approval'] ? 'checked' : ''; ?>>
                            <label for="require_approval">Require admin approval for nominations</label>
                        </div>
                        
                        <button type="submit" class="btn-save">Save General Settings</button>
                    </form>
                </div>
            </div>
            
            <!-- Security Tab -->
            <div id="tab-security" class="tab-content">
                <div class="settings-card">
                    <h2><span></span> Change Password</h2>
                    
                    <div class="info-box">
                        <p> Update your admin password regularly for security.</p>
                    </div>
                    
                    <form method="POST" action="" onsubmit="return validatePasswordForm()">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-row">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required 
                                   onkeyup="checkPasswordStrength()">
                            <div id="passwordStrength" class="password-strength"></div>
                        </div>
                        
                        <div class="form-row">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   onkeyup="checkPasswordMatch()">
                            <div id="passwordMatch" class="password-strength"></div>
                        </div>
                        
                        <button type="submit" class="btn-save">Change Password</button>
                    </form>
                </div>
                
                
            </div>
            
            <!-- Admin Users Tab -->
            <div id="tab-admins" class="tab-content">
                <div class="settings-card">
                    <h2><span></span> Admin Users</h2>
                    
                    <div class="info-box">
                        <p> Manage administrators who have access to the system.</p>
                    </div>
                    
                    <h3>Current Administrators</h3>
                    <ul class="admin-list">
                        <?php foreach ($admins as $admin): ?>
                            <li class="admin-item">
                                <div class="admin-info">
                                    <span class="admin-username"><?php echo htmlspecialchars($admin['username']); ?></span>
                                    <span class="admin-date">Added: <?php echo date('M d, Y', strtotime($admin['created_at'])); ?></span>
                                </div>
                                <div class="admin-actions">
                                    <?php if ($admin['username'] !== $_SESSION['username']): ?>
                                        <button class="btn btn-small btn-danger" onclick="deleteAdmin(<?php echo $admin['id']; ?>)">Remove</button>
                                    <?php else: ?>
                                        <span class="badge badge-active">Current User</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <h3 style="margin-top: 2rem;">Add New Admin</h3>
                    <form method="POST" action="" onsubmit="return validateNewAdminForm()">
                        <input type="hidden" name="action" value="create_admin">
                        
                        <div class="form-row">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="password">Password</label>
                            <input type="password" id="admin_password" name="password" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="admin_confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn-save">Create Admin User</button>
                    </form>
                </div>
            </div>
            
            
            
            <!-- Danger Zone -->
            <div class="danger-zone">
                <h3><span>⚠️</span> Danger Zone</h3>
                <p>These actions are irreversible. Please be certain.</p>
                
                <div style="margin: 1.5rem 0;">
                    <h4>Clear All Data</h4>
                    <p>This will delete all candidates and previous leaders. Admin users and positions will remain.</p>
                    <form method="POST" action="" onsubmit="return validateClearData()">
                        <input type="hidden" name="action" value="clear_data">
                        <input type="text" class="confirmation-input" id="confirm" name="confirm" 
                               placeholder="Type 'CLEAR ALL DATA' to confirm" required>
                        <button type="submit" class="danger-button" style="margin-top: 1rem;">Clear All Data</button>
                    </form>
                </div>
                
                <hr style="margin: 2rem 0;">
                
                <div>
                    <h4>Delete System</h4>
                    <p>Completely remove all data and system files. (Disabled for safety)</p>
                    <button class="danger-button" disabled>Delete Everything</button>
                </div>
            </div>
            
            <div class="settings-footer">
                <p>RASA Admin Panel v1.0.0 | &copy; <?php echo date('Y'); ?> Rwanda Anglican Student Association</p>
            </div>
        </div>
    </div>
    
    <script>
        // Mobile Toggle Function
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
        
        // Tab switching
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
        
        // Password strength checker
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            
            let strengthText = '';
            let strengthClass = '';
            
            if (strength < 3) {
                strengthText = 'Weak password';
                strengthClass = 'strength-weak';
            } else if (strength < 5) {
                strengthText = 'Medium password';
                strengthClass = 'strength-medium';
            } else {
                strengthText = 'Strong password';
                strengthClass = 'strength-strong';
            }
            
            strengthDiv.innerHTML = strengthText;
            strengthDiv.className = 'password-strength ' + strengthClass;
        }
        
        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirm === '') {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirm) {
                matchDiv.innerHTML = '✓ Passwords match';
                matchDiv.className = 'password-strength strength-strong';
            } else {
                matchDiv.innerHTML = '✗ Passwords do not match';
                matchDiv.className = 'password-strength strength-weak';
            }
        }
        
        // Form validations
        function validatePasswordForm() {
            const current = document.getElementById('current_password').value;
            const newPass = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (!current || !newPass || !confirm) {
                alert('Please fill in all password fields');
                return false;
            }
            
            if (newPass.length < 8) {
                alert('New password must be at least 8 characters long');
                return false;
            }
            
            if (newPass !== confirm) {
                alert('New passwords do not match');
                return false;
            }
            
            return true;
        }
        
        function validateNewAdminForm() {
            const username = document.getElementById('username').value;
            const password = document.getElementById('admin_password').value;
            const confirm = document.getElementById('admin_confirm_password').value;
            
            if (!username || !password || !confirm) {
                alert('Please fill in all fields');
                return false;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                return false;
            }
            
            if (password !== confirm) {
                alert('Passwords do not match');
                return false;
            }
            
            return true;
        }
        
        function validateGeneralSettings() {
            const siteName = document.getElementById('site_name').value;
            const email = document.getElementById('contact_email').value;
            
            if (!siteName) {
                alert('Site name is required');
                return false;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                return false;
            }
            
            return true;
        }
        
        function validateClearData() {
            const confirm = document.getElementById('confirm').value;
            
            if (confirm !== 'CLEAR ALL DATA') {
                alert('Please type "CLEAR ALL DATA" exactly to confirm');
                return false;
            }
            
            return confirm('ARE YOU ABSOLUTELY SURE? This will delete ALL candidates and previous leaders. This action cannot be undone.');
        }
        
        function deleteAdmin(adminId) {
            if (confirm('Are you sure you want to remove this admin user?')) {
                // Implement delete functionality
                alert('Delete functionality would go here. Admin ID: ' + adminId);
            }
        }
        
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
        
        // Toggle confirmation input based on checkbox
        document.addEventListener('DOMContentLoaded', function() {
            const dangerCheckbox = document.getElementById('confirm_danger');
            if (dangerCheckbox) {
                dangerCheckbox.addEventListener('change', function() {
                    document.getElementById('confirm_input').disabled = !this.checked;
                });
            }
        });
    </script>
</body>
</html>