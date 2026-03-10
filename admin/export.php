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

// Get format from URL
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
$ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
$position = isset($_GET['position']) ? $_GET['position'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$where_clauses = [];
$params = [];

// Filter by selected IDs
if (!empty($ids) && $ids[0] !== '') {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $where_clauses[] = "c.id IN ($placeholders)";
    $params = array_merge($params, $ids);
}

// Filter by position
if (!empty($position)) {
    $where_clauses[] = "c.position_id = ?";
    $params[] = $position;
}

// Filter by nomination type
if (!empty($type)) {
    $where_clauses[] = "c.nomination_type = ?";
    $params[] = $type;
}

// Filter by status
if ($status !== '') {
    $where_clauses[] = "c.is_active = ?";
    $params[] = $status;
}

// Search
if (!empty($search)) {
    $where_clauses[] = "(c.full_name ILIKE ? OR c.student_id ILIKE ? OR c.nominated_by ILIKE ?)";
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Build WHERE clause
$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get candidates data
$query = "
    SELECT 
        c.id,
        c.full_name,
        c.student_id,
        c.year_of_study,
        c.phone_number,
        c.email,
        c.manifesto,
        c.nomination_type,
        c.nominated_by,
        c.is_active,
        c.created_at,
        p.name as position_name,
        p.description as position_description
    FROM candidates c
    LEFT JOIN positions p ON c.position_id = p.id
    $where_sql
    ORDER BY c.created_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get export filename
$filename = 'rasa_candidates_' . date('Y-m-d_His');

// Handle different export formats
switch ($format) {
    case 'csv':
        exportCSV($candidates, $filename);
        break;
    case 'excel':
        exportExcel($candidates, $filename);
        break;
    case 'pdf':
        exportPDF($candidates, $filename);
        break;
    case 'json':
        exportJSON($candidates, $filename);
        break;
    case 'xml':
        exportXML($candidates, $filename);
        break;
    default:
        exportCSV($candidates, $filename);
}

/**
 * Export as CSV
 */
function exportCSV($candidates, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV headers
    fputcsv($output, [
        'ID',
        'Full Name',
        'Church',
        'Year of Study',
        'Phone Number',
        'Email',
        'Position',
        'Position Description',
        'Nomination Type',
        'Nominated By',
        'Status',
        'Submission Date',
        'Manifesto'
    ]);
    
    // Add data rows
    foreach ($candidates as $candidate) {
        fputcsv($output, [
            $candidate['id'],
            $candidate['full_name'],
            $candidate['student_id'] ?? 'N/A',
            $candidate['year_of_study'] ?? 'N/A',
            $candidate['phone_number'] ?? 'N/A',
            $candidate['email'] ?? 'N/A',
            $candidate['position_name'] ?? 'N/A',
            $candidate['position_description'] ?? 'N/A',
            $candidate['nomination_type'] === 'self' ? 'Self Nomination' : 'Nominated by Others',
            $candidate['nomination_type'] === 'self' ? 'Self' : ($candidate['nominated_by'] ?? 'N/A'),
            $candidate['is_active'] ? 'Active' : 'Inactive',
            date('Y-m-d H:i:s', strtotime($candidate['created_at'])),
            $candidate['manifesto'] ?? ''
        ]);
    }
    
    fclose($output);
    exit();
}

/**
 * Export as Excel (CSV with .xls extension)
 */
function exportExcel($candidates, $filename) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>RASA Candidates Export</title>';
    echo '<style>';
    echo 'th { background-color: #2e7d32; color: white; padding: 8px; }';
    echo 'td { padding: 6px; border: 1px solid #ccc; }';
    echo '.active { color: green; }';
    echo '.inactive { color: red; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<h2>RASA Candidates List</h2>';
    echo '<p>Exported on: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<p>Total candidates: ' . count($candidates) . '</p>';
    
    echo '<table border="1" cellspacing="0" cellpadding="5">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Full Name</th>';
    echo '<th>Student ID</th>';
    echo '<th>Year</th>';
    echo '<th>Phone</th>';
    echo '<th>Email</th>';
    echo '<th>Position</th>';
    echo '<th>Type</th>';
    echo '<th>Nominator</th>';
    echo '<th>Status</th>';
    echo '<th>Date</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($candidates as $candidate) {
        $status_class = $candidate['is_active'] ? 'active' : 'inactive';
        echo '<tr>';
        echo '<td>' . $candidate['id'] . '</td>';
        echo '<td>' . htmlspecialchars($candidate['full_name']) . '</td>';
        echo '<td>' . htmlspecialchars($candidate['student_id'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($candidate['year_of_study'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($candidate['phone_number'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($candidate['email'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($candidate['position_name'] ?? 'N/A') . '</td>';
        echo '<td>' . ($candidate['nomination_type'] === 'self' ? 'Self' : 'Other') . '</td>';
        echo '<td>' . htmlspecialchars($candidate['nominated_by'] ?? 'N/A') . '</td>';
        echo '<td class="' . $status_class . '">' . ($candidate['is_active'] ? 'Active' : 'Inactive') . '</td>';
        echo '<td>' . date('Y-m-d', strtotime($candidate['created_at'])) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    echo '<h3>Summary Statistics</h3>';
    $self_count = count(array_filter($candidates, function($c) { return $c['nomination_type'] === 'self'; }));
    $other_count = count(array_filter($candidates, function($c) { return $c['nomination_type'] === 'other'; }));
    $active_count = count(array_filter($candidates, function($c) { return $c['is_active']; }));
    
    echo '<ul>';
    echo '<li>Total Candidates: ' . count($candidates) . '</li>';
    echo '<li>Self Nominations: ' . $self_count . '</li>';
    echo '<li>Nominated Others: ' . $other_count . '</li>';
    echo '<li>Active Candidates: ' . $active_count . '</li>';
    echo '<li>Inactive Candidates: ' . (count($candidates) - $active_count) . '</li>';
    echo '</ul>';
    
    echo '</body>';
    echo '</html>';
    exit();
}

/**
 * Export as PDF (using HTML2PDF approach)
 */
function exportPDF($candidates, $filename) {
    // Since we can't guarantee a PDF library is installed,
    // we'll create an HTML file that can be printed to PDF
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.html"');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>RASA Candidates Export</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 30px; }';
    echo 'h1 { color: #2e7d32; }';
    echo 'h2 { color: #4caf50; }';
    echo 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    echo 'th { background: #2e7d32; color: white; padding: 10px; text-align: left; }';
    echo 'td { padding: 8px; border-bottom: 1px solid #ddd; }';
    echo 'tr:hover { background: #f5f5f5; }';
    echo '.header { text-align: center; margin-bottom: 30px; }';
    echo '.footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }';
    echo '.badge { padding: 3px 8px; border-radius: 3px; font-size: 11px; }';
    echo '.badge-active { background: #d4edda; color: #155724; }';
    echo '.badge-inactive { background: #f8d7da; color: #721c24; }';
    echo '.badge-self { background: #e3f2fd; color: #1976d2; }';
    echo '.badge-other { background: #fff3e0; color: #f57c00; }';
    echo '@media print { .no-print { display: none; } }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<div class="header">';
    echo '<h1>RASA RP MUSANZE COLLEGE</h1>';
    echo '<h2>Candidates List Report</h2>';
    echo '<p>Generated on: ' . date('F j, Y H:i:s') . '</p>';
    echo '</div>';
    
    // Summary section
    $self_count = count(array_filter($candidates, function($c) { return $c['nomination_type'] === 'self'; }));
    $other_count = count(array_filter($candidates, function($c) { return $c['nomination_type'] === 'other'; }));
    $active_count = count(array_filter($candidates, function($c) { return $c['is_active']; }));
    
    echo '<div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px;">';
    echo '<h3>Summary Statistics</h3>';
    echo '<table style="width: auto;">';
    echo '<tr><td><strong>Total Candidates:</strong></td><td>' . count($candidates) . '</td></tr>';
    echo '<tr><td><strong>Self Nominations:</strong></td><td>' . $self_count . '</td></tr>';
    echo '<tr><td><strong>Nominated Others:</strong></td><td>' . $other_count . '</td></tr>';
    echo '<tr><td><strong>Active Candidates:</strong></td><td>' . $active_count . '</td></tr>';
    echo '<tr><td><strong>Inactive Candidates:</strong></td><td>' . (count($candidates) - $active_count) . '</td></tr>';
    echo '</table>';
    echo '</div>';
    
    // Candidates table
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Name</th>';
    echo '<th>Position</th>';
    echo '<th>Type</th>';
    echo '<th>Nominator</th>';
    echo '<th>Contact</th>';
    echo '<th>Status</th>';
    echo '<th>Date</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($candidates as $candidate) {
        echo '<tr>';
        echo '<td>#' . $candidate['id'] . '</td>';
        echo '<td><strong>' . htmlspecialchars($candidate['full_name']) . '</strong><br>';
        if ($candidate['student_id']) {
            echo '<small>Church: ' . htmlspecialchars($candidate['student_id']) . '</small>';
        }
        echo '</td>';
        echo '<td>' . htmlspecialchars($candidate['position_name'] ?? 'N/A') . '</td>';
        echo '<td><span class="badge badge-' . $candidate['nomination_type'] . '">' . 
             ($candidate['nomination_type'] === 'self' ? 'Self' : 'Other') . '</span></td>';
        echo '<td>' . htmlspecialchars($candidate['nominated_by'] ?? 'N/A') . '</td>';
        echo '<td>';
        if ($candidate['phone_number']) {
            echo ' ' . htmlspecialchars($candidate['phone_number']) . '<br>';
        }
        if ($candidate['email']) {
            echo ' ' . htmlspecialchars($candidate['email']);
        }
        echo '</td>';
        echo '<td><span class="badge badge-' . ($candidate['is_active'] ? 'active' : 'inactive') . '">' . 
             ($candidate['is_active'] ? 'Active' : 'Inactive') . '</span></td>';
        echo '<td>' . date('M d, Y', strtotime($candidate['created_at'])) . '</td>';
        echo '</tr>';
        
        // Add manifesto as a separate row if exists
        if (!empty($candidate['manifesto'])) {
            echo '<tr style="background: #f9f9f9;">';
            echo '<td colspan="8" style="padding-left: 50px;">';
            echo '<small><strong>Manifesto/Reason:</strong> ' . htmlspecialchars($candidate['manifesto']) . '</small>';
            echo '</td>';
            echo '</tr>';
        }
    }
    
    echo '</tbody>';
    echo '</table>';
    
    echo '<div class="footer">';
    echo '<p>Rwanda Anglican Student Association - RP MUSANZE COLLEGE</p>';
    echo '<p>AGAKIZA - URUKUNDO - UMURIMO</p>';
    echo '</div>';
    
    echo '<div class="no-print" style="margin-top: 20px; text-align: center;">';
    echo '<button onclick="window.print()" style="padding: 10px 20px; background: #2e7d32; color: white; border: none; border-radius: 5px; cursor: pointer;">Print / Save as PDF</button>';
    echo '</div>';
    
    echo '</body>';
    echo '</html>';
    exit();
}

/**
 * Export as JSON
 */
function exportJSON($candidates, $filename) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    
    $data = [
        'export_date' => date('Y-m-d H:i:s'),
        'total_candidates' => count($candidates),
        'filters_applied' => [
            'position' => $_GET['position'] ?? 'all',
            'type' => $_GET['type'] ?? 'all',
            'status' => $_GET['status'] ?? 'all',
            'search' => $_GET['search'] ?? ''
        ],
        'candidates' => $candidates
    ];
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Export as XML
 */
function exportXML($candidates, $filename) {
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xml"');
    
    $xml = new XMLWriter();
    $xml->openURI('php://output');
    $xml->startDocument('1.0', 'UTF-8');
    $xml->setIndent(true);
    
    // Root element
    $xml->startElement('rasa_candidates');
    
    // Metadata
    $xml->startElement('metadata');
    $xml->writeElement('export_date', date('Y-m-d H:i:s'));
    $xml->writeElement('total_candidates', count($candidates));
    $xml->startElement('filters');
    $xml->writeElement('position', $_GET['position'] ?? 'all');
    $xml->writeElement('type', $_GET['type'] ?? 'all');
    $xml->writeElement('status', $_GET['status'] ?? 'all');
    $xml->writeElement('search', $_GET['search'] ?? '');
    $xml->endElement(); // filters
    $xml->endElement(); // metadata
    
    // Candidates
    $xml->startElement('candidates');
    
    foreach ($candidates as $candidate) {
        $xml->startElement('candidate');
        $xml->writeAttribute('id', $candidate['id']);
        
        $xml->writeElement('full_name', $candidate['full_name']);
        $xml->writeElement('student_id', $candidate['student_id'] ?? '');
        $xml->writeElement('year_of_study', $candidate['year_of_study'] ?? '');
        $xml->writeElement('phone_number', $candidate['phone_number'] ?? '');
        $xml->writeElement('email', $candidate['email'] ?? '');
        
        $xml->startElement('position');
        $xml->writeElement('name', $candidate['position_name'] ?? '');
        $xml->writeElement('description', $candidate['position_description'] ?? '');
        $xml->endElement();
        
        $xml->startElement('nomination');
        $xml->writeElement('type', $candidate['nomination_type']);
        $xml->writeElement('nominated_by', $candidate['nomination_type'] === 'self' ? 'Self' : ($candidate['nominated_by'] ?? ''));
        $xml->endElement();
        
        $xml->writeElement('manifesto', $candidate['manifesto'] ?? '');
        $xml->writeElement('status', $candidate['is_active'] ? 'active' : 'inactive');
        $xml->writeElement('submission_date', date('Y-m-d H:i:s', strtotime($candidate['created_at'])));
        
        $xml->endElement(); // candidate
    }
    
    $xml->endElement(); // candidates
    $xml->endElement(); // rasa_candidates
    
    $xml->endDocument();
    $xml->flush();
    exit();
}

// If no candidates found
if (empty($candidates)) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<html><body>';
    echo '<h2>No candidates found for export</h2>';
    echo '<p>No data matches your export criteria.</p>';
    echo '<p><a href="candidates.php">Return to Candidates</a></p>';
    echo '</body></html>';
    exit();
}
?>