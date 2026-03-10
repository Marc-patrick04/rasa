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

// Get all positions for filter
$positions = $db->query("SELECT id, name FROM positions ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'delete') {
                $stmt = $db->prepare("DELETE FROM candidates WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $message = "Candidate deleted successfully!";
            } elseif ($_POST['action'] === 'toggle_status') {
                $stmt = $db->prepare("UPDATE candidates SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $message = "Candidate status updated successfully!";
            } elseif ($_POST['action'] === 'delete_all') {
                $db->query("DELETE FROM candidates");
                $message = "All candidates deleted successfully!";
            } elseif ($_POST['action'] === 'export') {
                // Handle export via JavaScript
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Build query with filters
$where_clauses = [];
$params = [];

// Filter by position
if (isset($_GET['position']) && !empty($_GET['position'])) {
    $where_clauses[] = "c.position_id = ?";
    $params[] = $_GET['position'];
}

// Filter by nomination type
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $where_clauses[] = "c.nomination_type = ?";
    $params[] = $_GET['type'];
}

// Filter by status
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where_clauses[] = "c.is_active = ?";
    $params[] = $_GET['status'];
}

// Search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where_clauses[] = "(c.full_name ILIKE ? OR c.student_id ILIKE ? OR c.nominated_by ILIKE ?)";
    $search_term = '%' . $_GET['search'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Build WHERE clause
$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get candidates with filters
$query = "
    SELECT c.*, p.name as position_name 
    FROM candidates c
    LEFT JOIN positions p ON c.position_id = p.id
    $where_sql
    ORDER BY c.created_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM candidates")->fetchColumn(),
    'self' => $db->query("SELECT COUNT(*) FROM candidates WHERE nomination_type = 'self'")->fetchColumn(),
    'other' => $db->query("SELECT COUNT(*) FROM candidates WHERE nomination_type = 'other'")->fetchColumn(),
    'active' => $db->query("SELECT COUNT(*) FROM candidates WHERE is_active = true")->fetchColumn(),
    'inactive' => $db->query("SELECT COUNT(*) FROM candidates WHERE is_active = false")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates - RASA Admin</title>
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
        /* Additional admin styles */
        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .filter-group {
            margin-bottom: 0;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-green);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 0.6rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 0.95rem;
        }
        
        .filter-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.2rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-green);
        }
        
        .stat-card h4 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-green);
        }
        
        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-self {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-other {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.3rem;
            flex-wrap: wrap;
        }
        
        .btn-icon {
            padding: 0.3rem 0.8rem;
            font-size: 0.85rem;
        }
        
        .btn-view {
            background: #17a2b8;
            color: white;
        }
        
        .btn-view:hover {
            background: #138496;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            position: absolute;
            right: 1.5rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .close:hover {
            color: var(--primary-green);
        }
        
        .candidate-detail {
            margin-bottom: 1rem;
        }
        
        .candidate-detail label {
            font-weight: 600;
            color: var(--primary-green);
            display: block;
            margin-bottom: 0.3rem;
        }
        
        .candidate-detail p {
            background: #f9f9f9;
            padding: 0.8rem;
            border-radius: 5px;
            margin: 0;
        }
        
        .bulk-actions {
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .select-all {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .export-options {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }
        
        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .bulk-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .export-options {
                margin-left: 0;
            }
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
                <li class="active"><a href="candidates.php">Candidates</a></li>
                <li><a href="previous-leaders.php">Previous Leaders</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1>Manage Candidates</h1>
            
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
            
            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <h4>Total Candidates</h4>
                    <div class="number"><?php echo $stats['total']; ?></div>
                </div>
                <div class="stat-card">
                    <h4>Self Nominations</h4>
                    <div class="number"><?php echo $stats['self']; ?></div>
                </div>
                <div class="stat-card">
                    <h4>Nominated Others</h4>
                    <div class="number"><?php echo $stats['other']; ?></div>
                </div>
                <div class="stat-card">
                    <h4>Active</h4>
                    <div class="number"><?php echo $stats['active']; ?></div>
                </div>
                <div class="stat-card">
                    <h4>Inactive</h4>
                    <div class="number"><?php echo $stats['inactive']; ?></div>
                </div>
            </div>
            
            <!-- Filters Section -->
            <div class="filters-section">
                <form method="GET" action="" id="filterForm">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label for="position">Filter by Position</label>
                            <select name="position" id="position">
                                <option value="">All Positions</option>
                                <?php foreach ($positions as $position): ?>
                                    <option value="<?php echo $position['id']; ?>" 
                                        <?php echo (isset($_GET['position']) && $_GET['position'] == $position['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($position['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="type">Nomination Type</label>
                            <select name="type" id="type">
                                <option value="">All Types</option>
                                <option value="self" <?php echo (isset($_GET['type']) && $_GET['type'] == 'self') ? 'selected' : ''; ?>>Self Nomination</option>
                                <option value="other" <?php echo (isset($_GET['type']) && $_GET['type'] == 'other') ? 'selected' : ''; ?>>Nominated Others</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select name="status" id="status">
                                <option value="">All Status</option>
                                <option value="1" <?php echo (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo (isset($_GET['status']) && $_GET['status'] == '0') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="search">Search</label>
                            <input type="text" name="search" id="search" 
                                   placeholder="Name, ID, Nominator..." 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn">Apply Filters</button>
                            <a href="candidates.php" class="btn btn-outline">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <div class="select-all">
                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                    <label for="selectAll">Select All</label>
                </div>
                
                <div class="export-options">
                    <button class="btn btn-outline btn-small" onclick="exportCandidates('csv')">📥 Export CSV</button>
                    <button class="btn btn-outline btn-small" onclick="exportCandidates('pdf')">📥 Export PDF</button>
                    <button class="btn btn-outline btn-small" onclick="printCandidates()">🖨️ Print</button>
                  
                </div>
            </div>
            
            <!-- Candidates Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" id="selectAllHeader" onclick="toggleSelectAll()"></th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Type</th>
                            <th>Nominator</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($candidates)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 2rem;">
                                    <p>No candidates found matching your criteria.</p>
                                    <a href="candidates.php" class="btn btn-small">Clear Filters</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($candidates as $candidate): ?>
                            <tr>
                                <td><input type="checkbox" class="candidate-checkbox" value="<?php echo $candidate['id']; ?>"></td>
                                <td>#<?php echo $candidate['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($candidate['full_name']); ?></strong>
                                    <?php if (!empty($candidate['student_id'])): ?>
                                        <br><small style="color: #666;">Church: <?php echo htmlspecialchars($candidate['student_id']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($candidate['position_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $candidate['nomination_type'] === 'self' ? 'badge-self' : 'badge-other'; ?>">
                                        <?php echo $candidate['nomination_type'] === 'self' ? 'Self' : 'Nominated'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($candidate['nomination_type'] === 'other') {
                                        echo htmlspecialchars($candidate['nominated_by'] ?? 'N/A');
                                    } else {
                                        echo '<span style="color: #999;">Self</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($candidate['created_at'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $candidate['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo $candidate['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn btn-small btn-view" onclick="viewCandidate(<?php echo htmlspecialchars(json_encode($candidate)); ?>)">View</button>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle status for this candidate?');">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?php echo $candidate['id']; ?>">
                                        <button type="submit" class="btn btn-small <?php echo $candidate['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                            <?php echo $candidate['is_active'] ? 'Disable' : 'Enable'; ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this candidate?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $candidate['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination (if needed) -->
            <?php if (count($candidates) > 50): ?>
            <div style="margin-top: 1rem; display: flex; justify-content: center; gap: 0.5rem;">
                <button class="btn btn-small">Previous</button>
                <button class="btn btn-small btn-outline">1</button>
                <button class="btn btn-small btn-outline">2</button>
                <button class="btn btn-small btn-outline">3</button>
                <button class="btn btn-small">Next</button>
            </div>
            <?php endif; ?>
            
            <!-- Danger Zone -->
            <div style="margin-top: 2rem; padding: 1rem; background: #fff3f3; border-radius: 5px; border: 1px solid #f5c6cb;">
                <h3 style="color: #dc3545; margin-bottom: 1rem;">⚠️ Danger Zone</h3>
                <p>Delete all candidates. This action cannot be undone.</p>
                <form method="POST" onsubmit="return confirm('ARE YOU ABSOLUTELY SURE? This will delete ALL candidates permanently.');">
                    <input type="hidden" name="action" value="delete_all">
                    <button type="submit" class="btn btn-danger">Delete All Candidates</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- View Candidate Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 style="color: var(--primary-green); margin-bottom: 1.5rem;">Candidate Details</h2>
            
            <div id="candidateDetails"></div>
            
            <div style="margin-top: 2rem; text-align: right;">
                <button class="btn" onclick="closeModal()">Close</button>
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
        
        // View candidate details
        function viewCandidate(candidate) {
            const detailsDiv = document.getElementById('candidateDetails');
            
            let html = `
                <div class="candidate-detail">
                    <label>Full Name</label>
                    <p>${escapeHtml(candidate.full_name || 'N/A')}</p>
                </div>
                
                <div class="candidate-detail">
                    <label>Position</label>
                    <p>${escapeHtml(candidate.position_name || 'N/A')}</p>
                </div>
                
                <div class="candidate-detail">
                    <label>Nomination Type</label>
                    <p>${candidate.nomination_type === 'self' ? 'Self Nomination' : 'Nominated by ' + escapeHtml(candidate.nominated_by)}</p>
                </div>
            `;
            
            if (candidate.student_id) {
                html += `
                    <div class="candidate-detail">
                        <label>Student ID</label>
                        <p>${escapeHtml(candidate.student_id)}</p>
                    </div>
                `;
            }
            
            if (candidate.year_of_study) {
                html += `
                    <div class="candidate-detail">
                        <label>Year of Study</label>
                        <p>Year ${escapeHtml(candidate.year_of_study)}</p>
                    </div>
                `;
            }
            
            if (candidate.phone_number) {
                html += `
                    <div class="candidate-detail">
                        <label>Phone Number</label>
                        <p>${escapeHtml(candidate.phone_number)}</p>
                    </div>
                `;
            }
            
            if (candidate.email) {
                html += `
                    <div class="candidate-detail">
                        <label>Email</label>
                        <p>${escapeHtml(candidate.email)}</p>
                    </div>
                `;
            }
            
            html += `
                <div class="candidate-detail">
                    <label>Manifesto / Reason</label>
                    <p>${escapeHtml(candidate.manifesto || 'No manifesto provided')}</p>
                </div>
                
                <div class="candidate-detail">
                    <label>Status</label>
                    <p><span class="badge ${candidate.is_active ? 'badge-active' : 'badge-inactive'}">${candidate.is_active ? 'Active' : 'Inactive'}</span></p>
                </div>
                
                <div class="candidate-detail">
                    <label>Submitted On</label>
                    <p>${new Date(candidate.created_at).toLocaleString()}</p>
                </div>
            `;
            
            detailsDiv.innerHTML = html;
            document.getElementById('viewModal').style.display = 'block';
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('viewModal').style.display = 'none';
        }
        
        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Select all checkboxes
        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.candidate-checkbox');
            const selectAll = document.getElementById('selectAll');
            const selectAllHeader = document.getElementById('selectAllHeader');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            if (selectAllHeader) {
                selectAllHeader.checked = selectAll.checked;
            }
        }
        
        // Get selected candidate IDs
        function getSelectedIds() {
            const checkboxes = document.querySelectorAll('.candidate-checkbox:checked');
            return Array.from(checkboxes).map(cb => cb.value);
        }
        
        // Confirm bulk delete
        function confirmBulkDelete() {
            const selectedIds = getSelectedIds();
            
            if (selectedIds.length === 0) {
                alert('Please select at least one candidate to delete.');
                return;
            }
            
            if (confirm(`Are you sure you want to delete ${selectedIds.length} candidate(s)?`)) {
                // Create a form to submit selected IDs
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'bulk_delete';
                form.appendChild(actionInput);
                
                const idsInput = document.createElement('input');
                idsInput.type = 'hidden';
                idsInput.name = 'ids';
                idsInput.value = selectedIds.join(',');
                form.appendChild(idsInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Export candidates
        function exportCandidates(format) {
            const selectedIds = getSelectedIds();
            let url = 'export.php?format=' + format;
            
            if (selectedIds.length > 0) {
                url += '&ids=' + selectedIds.join(',');
            }
            
            // Add current filters to export
            const position = document.getElementById('position').value;
            const type = document.getElementById('type').value;
            const status = document.getElementById('status').value;
            const search = document.getElementById('search').value;
            
            if (position) url += '&position=' + position;
            if (type) url += '&type=' + type;
            if (status !== '') url += '&status=' + status;
            if (search) url += '&search=' + encodeURIComponent(search);
            
            window.location.href = url;
        }
        
        // Print candidates
        function printCandidates() {
            window.print();
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>