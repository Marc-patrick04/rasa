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

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $stmt = $db->prepare("INSERT INTO positions (name, description, is_active) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['description'], isset($_POST['is_active']) ? 1 : 0]);
        } elseif ($_POST['action'] === 'edit') {
            $stmt = $db->prepare("UPDATE positions SET name = ?, description = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['description'], isset($_POST['is_active']) ? 1 : 0, $_POST['id']]);
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $db->prepare("DELETE FROM positions WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }
    }
}

// Get all positions
$positions = $db->query("SELECT * FROM positions ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Positions - RASA Admin</title>
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
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle" onclick="toggleSidebar()" id="mobileToggle">
        <span></span>
        <span></span>
        <span></span>
    </button>
    
    <div class="admin-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>RASA Admin</h2>
                <p>Welcome, <?php echo $_SESSION['username']; ?></p>
                <button class="close-sidebar" onclick="toggleSidebar()" id="closeSidebar">×</button>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li class="active"><a href="positions.php">Positions</a></li>
                <li><a href="candidates.php">Candidates</a></li>
                <li><a href="previous-leaders.php">Previous Leaders</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1>Manage Positions</h1>
            
            <button class="btn" onclick="showAddForm()">Add New Position</button>
            
            <!-- Add/Edit Form (hidden by default) -->
            <div id="positionForm" style="display: none; margin: 2rem 0;">
                <div class="form-container">
                    <h3 id="formTitle">Add New Position</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="id" id="positionId" value="">
                        
                        <div class="form-group">
                            <label for="name">Position Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description (Optional)</label>
                            <textarea id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" checked> Active
                            </label>
                        </div>
                        
                        <button type="submit" class="btn">Save Position</button>
                        <button type="button" class="btn btn-outline" onclick="hideForm()">Cancel</button>
                    </form>
                </div>
            </div>
            
            <!-- Positions List -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Position Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($positions as $position): ?>
                        <tr>
                            <td><?php echo $position['id']; ?></td>
                            <td><?php echo htmlspecialchars($position['name']); ?></td>
                            <td><?php echo htmlspecialchars($position['description']); ?></td>
                            <td>
                                <span class="badge <?php echo $position['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $position['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($position['created_at'])); ?></td>
                            <td class="action-buttons">
                                <button class="btn btn-small" onclick="editPosition(<?php echo htmlspecialchars(json_encode($position)); ?>)">Edit</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $position['id']; ?>">
                                    <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
        
        function showAddForm() {
            document.getElementById('formTitle').textContent = 'Add New Position';
            document.getElementById('action').value = 'add';
            document.getElementById('positionId').value = '';
            document.getElementById('name').value = '';
            document.getElementById('description').value = '';
            document.getElementById('positionForm').style.display = 'block';
        }
        
        function editPosition(position) {
            document.getElementById('formTitle').textContent = 'Edit Position';
            document.getElementById('action').value = 'edit';
            document.getElementById('positionId').value = position.id;
            document.getElementById('name').value = position.name;
            document.getElementById('description').value = position.description || '';
            document.getElementById('positionForm').style.display = 'block';
        }
        
        function hideForm() {
            document.getElementById('positionForm').style.display = 'none';
        }
    </script>
</body>
</html>