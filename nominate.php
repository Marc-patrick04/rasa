<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$database = new Database();
$db = $database->getConnection();
$type = isset($_GET['type']) ? $_GET['type'] : 'self';

// Get active positions
$positions = $db->query("SELECT * FROM positions WHERE is_active = true ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

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
            $success = "Self nomination submitted successfully!";
        } else {
            $error = "Error submitting nomination. Please try again.";
        }
    } else {
        // Nominate others - only name, position, reason
        $nominated_by = $_POST['nominated_by'];
        
        $stmt = $db->prepare("INSERT INTO candidates (position_id, full_name, manifesto, nomination_type, nominated_by) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$position_id, $full_name, $manifesto, $nomination_type, $nominated_by])) {
            $success = "Nomination submitted successfully!";
        } else {
            $error = "Error submitting nomination. Please try again.";
        }
    }
}
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
            <li><a href="nominate.php" class="active">Nominate</a></li>
            <li><a href="previous-leaders.php">Previous Leaders</a></li>
            <li><a href="about.php">About RASA</a></li>
            <li><a href="login.php">Admin Login</a></li>
        </ul>
    </nav>
    
    <main class="container">
        <div class="nomination-tabs">
            <a href="?type=self" class="btn <?php echo $type === 'self' ? 'active' : 'btn-outline'; ?>">
                 Self Nomination
            </a>
            <a href="?type=other" class="btn <?php echo $type === 'other' ? 'active' : 'btn-outline'; ?>">
                 Nominate Others
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
                <div class="alert alert-success">
                     <?php echo $success; ?>
                    <br>
                    <small>Thank you for your nomination. The Arbitration Committee will review it shortly.</small>
                </div>
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
                            <?php foreach ($positions as $position): ?>
                                <option value="<?php echo $position['id']; ?>">
                                    <?php echo htmlspecialchars($position['name']); ?>
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
                    
                    <div class="form-group">
                        <label for="year_of_study" class="required-field">Year of Study</label>
                        <select id="year_of_study" name="year_of_study" required>
                            <option value="">-- Select Year --</option>
                            <option value="1">Year 1A</option>
                            <option value="1">Year 1B</option>
                            <option value="2">Year 2</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number" class="required-field">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" required 
                               placeholder=""
                               maxlength="20">
                        
                    </div>
                    
                    <div class="form-group">
                        <label for="manifesto" class="required-field">Other info</label>
                        <textarea id="manifesto" name="manifesto" rows="6" required 
                                  placeholder="any other comment"
                                  maxlength="2000"></textarea>
                        <div class="character-count">
                            <span id="charCount">0</span>/2000 characters
                        </div>
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
                            <?php foreach ($positions as $position): ?>
                                <option value="<?php echo $position['id']; ?>">
                                    <?php echo htmlspecialchars($position['name']); ?>
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
                        <label for="manifesto" class="required-field">Reason for Nomination</label>
                        <textarea id="manifesto" name="manifesto" rows="3" required 
                                  placeholder="Why are you nominating this person?"
                                  maxlength="2000"></textarea>
                        <div class="character-count">
                            <span id="charCount">0</span>/2000 characters
                        </div>
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
            const manifesto = document.getElementById('manifesto');
            
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
            } else {
                // Basic phone number validation (Rwandan format)
                const phoneRegex = /^0[7,8,9]\d{8}$/;
                if (!phoneRegex.test(phoneNumber.value.replace(/\s/g, ''))) {
                    phoneNumber.classList.add('error');
                    alert('Please enter a valid Rwandan phone number (e.g., 078XXXXXXX)');
                    isValid = false;
                }
            }
            
            if (!manifesto.value.trim() || manifesto.value.length < 4) {
                manifesto.classList.add('error');
                alert('Please provide a manifesto with at least 4 characters');
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
            const manifesto = document.getElementById('manifesto');
            
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
            
            if (!manifesto.value.trim() || manifesto.value.length < 4) {
                manifesto.classList.add('error');
                alert('Please provide a reason for nomination with at least 4 characters');
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
    </script>
</body>
</html>