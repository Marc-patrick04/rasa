<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$database = new Database();
$db = $database->getConnection();
$type = isset($_GET['type']) ? $_GET['type'] : 'self';

// Get active positions
$positions = $db->query("SELECT * FROM positions WHERE is_active = true ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position_id = $_POST['position_id'];
    $full_name = $_POST['full_name'];
    $nomination_type = $_POST['nomination_type'];
    $manifesto = $_POST['manifesto'];
    
    if ($nomination_type === 'self') {
        // Self nomination fields
        $student_id = $_POST['student_id'];
        $year_of_study = $_POST['year_of_study'];
        $phone_number = $_POST['phone_number'];
        $nominated_by = $full_name;
        
        $stmt = $db->prepare("INSERT INTO candidates (position_id, full_name, student_id, year_of_study, phone_number, manifesto, nomination_type, nominated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$position_id, $full_name, $student_id, $year_of_study, $phone_number, $manifesto, $nomination_type, $nominated_by])) {
            // Redirect to prevent form resubmission on refresh
            header("Location: nominate.php?type=" . $nomination_type . "&success=1");
            exit();
        } else {
            $error = "Error submitting nomination. Please try again.";
        }
    } else {
        // Nominate others - only name, position, reason
        $nominated_by = $_POST['nominated_by'];
        
        $stmt = $db->prepare("INSERT INTO candidates (position_id, full_name, manifesto, nomination_type, nominated_by) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$position_id, $full_name, $manifesto, $nomination_type, $nominated_by])) {
            // Redirect to prevent form resubmission on refresh
            header("Location: nominate.php?type=" . $nomination_type . "&success=1");
            exit();
        } else {
            $error = "Error submitting nomination. Please try again.";
        }
    }
}

// Check for success parameter from redirect
$success = isset($_GET['success']) ? "Self nomination submitted successfully!" : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nominate - RASA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .nomination-tabs {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .nomination-tabs .btn {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            min-width: 200px;
        }
        
        .nomination-tabs .btn.active {
            background: var(--primary-green);
            color: white;
        }
        
        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .required-field::after {
            content: " *";
            color: red;
            font-weight: bold;
        }
        
        .field-note {
            color: #666;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-header h2 {
            color: var(--primary-green);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-green);
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-green);
        }
        
        .form-group input.error,
        .form-group select.error,
        .form-group textarea.error {
            border-color: #dc3545;
        }
        
        .submit-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s ease;
        }
        
        .submit-btn:hover {
            background: var(--light-green);
        }
        
        .submit-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .nomination-tabs {
                flex-direction: column;
                align-items: center;
            }
            
            .nomination-tabs .btn {
                width: 100%;
                max-width: 300px;
            }
        }

        /* Simple style for other nomination form */
        .simple-form .form-group {
            margin-bottom: 1.5rem;
        }
        
        .simple-form textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .character-count {
            text-align: right;
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        /* Success Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 500px;
            width: 90%;
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-icon {
            font-size: 3rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        
        .modal-title {
            font-size: 1.5rem;
            color: #28a745;
            margin-bottom: 1rem;
            font-weight: bold;
        }
        
        .modal-message {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .modal-btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .modal-btn-primary {
            background-color: #28a745;
            color: white;
        }
        
        .modal-btn-primary:hover {
            background-color: #218838;
        }
        
        .modal-btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .modal-btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <!-- Success Modal -->
    <div id="successModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-icon">✅</div>
            <div class="modal-title">Success!</div>
            <div class="modal-message" id="modalMessage">Your nomination has been submitted successfully!</div>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-primary" onclick="closeSuccessModal()">Continue</button>
            </div>
        </div>
    </div>
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
            <li><a href="nominate.php" class="active">Nominate/Kwamamaza</a></li>
            <li><a href="previous-leaders.php">Previous Leaders</a></li>
            <li><a href="about.php">About RASA</a></li>
            <li><a href="login.php">Login</a></li>
        </ul>
    </nav>
    
    <main class="container">
        <div class="nomination-tabs">
            <a href="?type=self" class="btn <?php echo $type === 'self' ? 'active' : 'btn-outline'; ?>">
                 Self Nomination/Gutanga candidatire yawe
            </a>
            <a href="?type=other" class="btn <?php echo $type === 'other' ? 'active' : 'btn-outline'; ?>">
                 Nominate Others/Gutanga umukandida
            </a>
        </div>
        
        <div class="form-container">
            <div class="form-header">
                <h2><?php echo $type === 'self' ? 'Self Nomination Form' : 'Nominate Others Form'; ?></h2>
                <p><?php echo $type === 'self' 
                    ? 'Submit your candidacy for a leadership position' 
                    : 'Nominate a fellow student for a leadership position'; ?>
                </p>
            </div>
            
            <?php if (isset($success)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showSuccessModal('<?php echo addslashes($success); ?>');
                    });
                </script>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    ❌ <?php echo $error; ?>
                    <br>
                    <small>Please try again or contact the administrator.</small>
                </div>
            <?php endif; ?>
            
            <?php if ($type === 'self'): ?>
                <!-- SELF NOMINATION FORM - All fields except email -->
                <form method="POST" action="" id="selfNominationForm" onsubmit="return validateSelfForm()">
                    <input type="hidden" name="nomination_type" value="self">
                    
                    <div class="form-group">
                        <label for="position_id" class="required-field">Position</label>
                        <select id="position_id" name="position_id" required>
                            <option value="">-- Select a Position --</option>
                            <?php $counter = 1; ?>
                            <?php foreach ($positions as $position): ?>
                                <option value="<?php echo $position['id']; ?>">
                                    <?php echo $counter++; ?>. <?php echo htmlspecialchars($position['name']); ?>
                                    <?php if (!empty($position['description'])): ?>
                                        - <?php echo htmlspecialchars($position['description']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name" class="required-field">Your Full Name</label>
                        <input type="text" id="full_name" name="full_name" required 
                               placeholder="Enter your full name "
                               maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="student_id" class="required-field">Your local church</label>
                        <input type="text" id="student_id" name="student_id" required 
                               placeholder=""
                               maxlength="50">

                    </div>
                    
                    <div class="form-group" style="display: none;">
                        <label for="manifesto" class="required-field">Other info</label>
                        <textarea id="manifesto" name="manifesto" rows="6" 
                                  placeholder="any other comment"
                                  maxlength="2000">Not applicable</textarea>
                        <div class="character-count">
                            <span id="charCount">13</span>/2000 characters
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number" class="required-field">Phone Number</label>
                        <input type="tel" name="phone_number" required 
                               placeholder=""
                               maxlength="20" >
                        
                    </div>
                    
                    <div class="form-group">
                        <!-- <label for="manifesto" class="required-field">Other info</label> -->
                        <textarea id="manifesto" name="manifesto" rows="6" value="NOT APPLICABLE" 
                                  placeholder="any other comment"
                                  maxlength="2000" hidden ></textarea>
                        <!-- <div class="character-count">
                            <span id="charCount">0</span>/2000 characters
                        </div> -->
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn">
                        Submit Self Nomination
                    </button>
                </form>
            <?php else: ?>
                <!-- NOMINATE OTHERS FORM - Only name, position, reason, and nominator name -->
                <form method="POST" action="" id="otherNominationForm" class="simple-form" onsubmit="return validateOtherForm()">
                    <input type="hidden" name="nomination_type" value="other">
                    
                    <div class="form-group">
                        <label for="position_id" class="required-field">Position</label>
                        <select id="position_id" name="position_id" required>
                            <option value="">-- Select a Position --</option>
                            <?php $counter = 1; ?>
                            <?php foreach ($positions as $position): ?>
                                <option value="<?php echo $position['id']; ?>">
                                    <?php echo $counter++; ?>. <?php echo htmlspecialchars($position['name']); ?>
                                    <?php if (!empty($position['description'])): ?>
                                        - <?php echo htmlspecialchars($position['description']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name" class="required-field">Candidate's Full Name</label>
                        <input type="text" id="full_name" name="full_name" required 
                               placeholder=""
                               maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <!-- <label for="nominated_by" class="required-field" hidden>Your Name (Nominator)</label> -->
                        <input type="text" id="nominated_by" name="nominated_by" required 
                               placeholder="Enter your full name"
                               maxlength="100"  value="unknown" hidden>
                       
                    
                    <div class="form-group">
                        <!-- <label for="manifesto" class="required-field">Reason for Nomination</label> -->
                        <textarea id="manifesto" name="manifesto" rows="3" value="NOT APPLICABLE" 
                                  placeholder="Why are you nominating this person?"
                                  maxlength="2000" hidden></textarea>
                        <!-- <div class="character-count">
                            <span id="charCount">0</span>/2000 characters
                        </div> -->
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn">
                        Submit Nomination
                    </button>
                </form>
            <?php endif; ?>
            
            
        </div>
    </main>
    
    <footer>
        <p>&copy; 2026 RASA RP MUSANZE COLLEGE. All rights reserved.</p>
        <p>AGAKIZA - URUKUNDO - UMURIMO</p>
    </footer>
    
    <script>
        // Character counter for manifesto
        document.addEventListener('DOMContentLoaded', function() {
            const manifesto = document.getElementById('manifesto');
            if (manifesto) {
                manifesto.addEventListener('input', function() {
                    const charCount = document.getElementById('charCount');
                    if (charCount) {
                        charCount.textContent = this.value.length;
                    }
                });
            }
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                document.querySelectorAll('.alert').forEach(alert => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.style.display = 'none', 500);
                });
            }, 5000);
        });
        
        // Self nomination form validation
        function validateSelfForm() {
            const position = document.getElementById('position_id');
            const fullName = document.getElementById('full_name');
            const studentId = document.getElementById('student_id');
            const yearOfStudy = document.getElementById('year_of_study');
            const phoneNumber = document.getElementById('phone_number');
            
            // Reset previous error styles
            document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
            
            let isValid = true;
            
            if (!position.value) {
                position.classList.add('error');
                isValid = false;
            }
            
            if (!fullName.value.trim()) {
                fullName.classList.add('error');
                isValid = false;
            }
            
            if (!studentId.value.trim()) {
                studentId.classList.add('error');
                isValid = false;
            }
            
            if (!yearOfStudy.value) {
                yearOfStudy.classList.add('error');
                isValid = false;
            }
            
            if (!phoneNumber.value.trim()) {
                phoneNumber.classList.add('error');
                isValid = false;
            }
            
            if (!isValid) {
                alert('Please fill in all required fields correctly');
            }
            
            return isValid;
        }
        
        // Other nomination form validation
        function validateOtherForm() {
            const position = document.getElementById('position_id');
            const fullName = document.getElementById('full_name');
            const nominatedBy = document.getElementById('nominated_by');
            
            // Reset previous error styles
            document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
            
            let isValid = true;
            
            if (!position.value) {
                position.classList.add('error');
                isValid = false;
            }
            
            if (!fullName.value.trim()) {
                fullName.classList.add('error');
                isValid = false;
            }
            
            if (!nominatedBy.value.trim()) {
                nominatedBy.classList.add('error');
                isValid = false;
            }
            
            if (!isValid) {
                alert('Please fill in all required fields');
            }
            
            return isValid;
        }
        
        // Prevent multiple form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Submitting...';
                }
            });
        });
        
        // Success Modal Functions
        function showSuccessModal(message) {
            const modal = document.getElementById('successModal');
            const modalMessage = document.getElementById('modalMessage');
            
            // Update message if provided
            if (message) {
                modalMessage.innerHTML = message + '<br><small>Thank you for your nomination. The Arbitration Committee will review it shortly.</small>';
            }
            
            // Show modal with animation
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.style.opacity = '1';
            }, 10);
        }
        
        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
                // Redirect to home page after closing modal
                window.location.href = 'index.php';
            }, 300);
        }
    </script>
</body>
</html>
