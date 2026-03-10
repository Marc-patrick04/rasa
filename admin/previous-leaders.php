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

// Get all positions for dropdown
$positions = $db->query("SELECT id, name FROM positions ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
                $position_id = !empty($_POST['position_id']) ? $_POST['position_id'] : null;
                $full_name = $_POST['full_name'];
                $year_served = $_POST['year_served'];
                $achievements = $_POST['achievements'];
                $photo_url = $_POST['photo_url'] ?? null;
                
                if ($_POST['action'] === 'add') {
                    $stmt = $db->prepare("
                        INSERT INTO previous_leaders (position_id, full_name, year_served, achievements, photo_url) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$position_id, $full_name, $year_served, $achievements, $photo_url]);
                    $message = "Leader added successfully!";
                } else {
                    $stmt = $db->prepare("
                        UPDATE previous_leaders 
                        SET position_id = ?, full_name = ?, year_served = ?, achievements = ?, photo_url = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$position_id, $full_name, $year_served, $achievements, $photo_url, $_POST['id']]);
                    $message = "Leader updated successfully!";
                }
            } elseif ($_POST['action'] === 'delete') {
                $stmt = $db->prepare("DELETE FROM previous_leaders WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $message = "Leader deleted successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get all previous leaders with position names
$query = "
    SELECT pl.*, p.name as position_name 
    FROM previous_leaders pl
    LEFT JOIN positions p ON pl.position_id = p.id
    ORDER BY pl.year_served DESC, pl.full_name ASC
";
$previous_leaders = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Previous Leaders - RASA Admin</title>
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
        .photo-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
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
                <li class="active"><a href="previous-leaders.php">Previous Leaders</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1>Manage Previous Leaders</h1>
            
            <?php if (isset($message)): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <button class="btn" onclick="showAddForm()">Add New Leader</button>
            
            <!-- Add/Edit Form -->
            <div id="leaderForm" style="display: none; margin: 2rem 0;">
                <div class="form-container">
                    <h3 id="formTitle">Add New Leader</h3>
                    <form method="POST" action="" onsubmit="return validateForm()">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="id" id="leaderId" value="">
                        
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" required maxlength="100">
                        </div>
                        
                        <div class="form-group">
                            <label for="position_id">Position</label>
                            <select id="position_id" name="position_id">
                                <option value="">-- Select Position --</option>
                                <?php foreach ($positions as $position): ?>
                                    <option value="<?php echo $position['id']; ?>">
                                        <?php echo htmlspecialchars($position['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="year_served">Year Served *</label>
                            <input type="text" id="year_served" name="year_served" required 
                                   placeholder="e.g., 2023-2024 or 2023" 
                                   pattern="\d{4}(-\d{4})?" 
                                   title="Enter year as YYYY or YYYY-YYYY">
                        </div>
                        
                        <div class="form-group">
                            <label for="achievements">Achievements / Description</label>
                            <textarea id="achievements" name="achievements" rows="5" 
                                      placeholder="Describe their achievements, contributions, and legacy..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="photo_url">Photo URL (Optional)</label>
                            <input type="url" id="photo_url" name="photo_url" 
                                   placeholder="https://example.com/photo.jpg">
                            <small style="color: #666;">Enter a URL for the leader's photo (optional)</small>
                        </div>
                        
                        <div id="photoPreviewContainer" style="display: none; margin-bottom: 1rem;">
                            <label>Photo Preview:</label>
                            <img id="photoPreview" class="photo-preview" src="" alt="Preview">
                        </div>
                        
                        <button type="submit" class="btn">Save Leader</button>
                        <button type="button" class="btn btn-outline" onclick="hideForm()">Cancel</button>
                    </form>
                </div>
            </div>
            
            <!-- Leaders List -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Year Served</th>
                            <th>Achievements</th>
                            <th>Added On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($previous_leaders as $leader): ?>
                        <tr>
                            <td><?php echo $leader['id']; ?></td>
                            <td>
                                <?php if (!empty($leader['photo_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($leader['photo_url']); ?>" 
                                         alt="Photo" class="photo-preview">
                                <?php else: ?>
                                    <span style="color: #999;">No photo</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($leader['full_name']); ?></strong></td>
                            <td>
                                <?php if ($leader['position_name']): ?>
                                    <span class="badge badge-success"><?php echo htmlspecialchars($leader['position_name']); ?></span>
                                <?php else: ?>
                                    <span style="color: #999;">Not specified</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($leader['year_served']); ?></td>
                            <td>
                                <?php 
                                $achievements = $leader['achievements'];
                                if ($achievements) {
                                    echo strlen($achievements) > 50 ? substr(htmlspecialchars($achievements), 0, 50) . '...' : htmlspecialchars($achievements);
                                } else {
                                    echo '<span style="color: #999;">No achievements listed</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($leader['created_at'])); ?></td>
                            <td class="action-buttons">
                                <button class="btn btn-small" onclick="editLeader(<?php echo htmlspecialchars(json_encode($leader)); ?>)">Edit</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this leader?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $leader['id']; ?>">
                                    <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($previous_leaders)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem;">
                                <p>No previous leaders found. Click "Add New Leader" to add one.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Export Section -->
            <div style="margin-top: 2rem; padding: 1rem; background: var(--light-gray); border-radius: 5px;">
                <h3>Export Data</h3>
                <p>Download the list of previous leaders for your records.</p>
                <button class="btn btn-outline" onclick="exportToCSV()">Export to CSV</button>
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
        
        function showAddForm() {
            document.getElementById('formTitle').textContent = 'Add New Leader';
            document.getElementById('action').value = 'add';
            document.getElementById('leaderId').value = '';
            document.getElementById('full_name').value = '';
            document.getElementById('position_id').value = '';
            document.getElementById('year_served').value = '';
            document.getElementById('achievements').value = '';
            document.getElementById('photo_url').value = '';
            document.getElementById('photoPreviewContainer').style.display = 'none';
            document.getElementById('leaderForm').style.display = 'block';
            document.getElementById('full_name').focus();
        }
        
        function editLeader(leader) {
            document.getElementById('formTitle').textContent = 'Edit Leader';
            document.getElementById('action').value = 'edit';
            document.getElementById('leaderId').value = leader.id;
            document.getElementById('full_name').value = leader.full_name || '';
            document.getElementById('position_id').value = leader.position_id || '';
            document.getElementById('year_served').value = leader.year_served || '';
            document.getElementById('achievements').value = leader.achievements || '';
            document.getElementById('photo_url').value = leader.photo_url || '';
            
            // Show photo preview if URL exists
            if (leader.photo_url) {
                document.getElementById('photoPreview').src = leader.photo_url;
                document.getElementById('photoPreviewContainer').style.display = 'block';
            } else {
                document.getElementById('photoPreviewContainer').style.display = 'none';
            }
            
            document.getElementById('leaderForm').style.display = 'block';
        }
        
        function hideForm() {
            document.getElementById('leaderForm').style.display = 'none';
        }
        
        // Photo URL preview
        document.getElementById('photo_url').addEventListener('input', function(e) {
            const url = e.target.value;
            if (url) {
                document.getElementById('photoPreview').src = url;
                document.getElementById('photoPreviewContainer').style.display = 'block';
            } else {
                document.getElementById('photoPreviewContainer').style.display = 'none';
            }
        });
        
        // Form validation
        function validateForm() {
            const name = document.getElementById('full_name').value.trim();
            const year = document.getElementById('year_served').value.trim();
            
            if (!name) {
                alert('Please enter the leader\'s full name');
                return false;
            }
            
            if (!year) {
                alert('Please enter the year served');
                return false;
            }
            
            // Validate year format
            const yearPattern = /^\d{4}(-\d{4})?$/;
            if (!yearPattern.test(year)) {
                alert('Please enter a valid year format (e.g., 2023 or 2023-2024)');
                return false;
            }
            
            return true;
        }
        
        // Export to CSV
        function exportToCSV() {
            const leaders = <?php echo json_encode($previous_leaders); ?>;
            
            if (leaders.length === 0) {
                alert('No data to export');
                return;
            }
            
            // Define CSV headers
            const headers = ['Name', 'Position', 'Year Served', 'Achievements', 'Added Date'];
            
            // Convert to CSV
            let csvContent = headers.join(',') + '\n';
            
            leaders.forEach(leader => {
                const row = [
                    `"${leader.full_name || ''}"`,
                    `"${leader.position_name || ''}"`,
                    `"${leader.year_served || ''}"`,
                    `"${(leader.achievements || '').replace(/"/g, '""')}"`,
                    `"${leader.created_at || ''}"`
                ];
                csvContent += row.join(',') + '\n';
            });
            
            // Download CSV
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'rasa_previous_leaders.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>