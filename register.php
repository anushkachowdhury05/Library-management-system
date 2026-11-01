<?php
/**
 * Student Registration Page
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to respective dashboard
if (is_logged_in()) {
    header("Location: " . (is_admin() ? 'admin_dashboard.php' : 'student_dashboard.php'));
    exit;
}

$error = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'All fields are required. Please fill in all information.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match. Please re-enter carefully.';
    } else {
        // Check if email already exists
        $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $error = 'An account with this email address already exists. Please log in.';
        } else {
            // Hash password and insert
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
            
            try {
                $insertStmt->execute([$name, $email, $hashedPassword]);
                set_flash('success', 'Registration successful! You can now log in to your student account.');
                header("Location: login.php?role=student");
                exit;
            } catch (PDOException $e) {
                $error = 'A database error occurred during registration. Please try again.';
            }
        }
    }
}

$page_title = 'Student Registration - Library Management System';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-6 col-lg-5">
        <div class="card auth-card overflow-hidden">
            <div class="auth-header">
                <i class="bi bi-person-plus-fill fs-1 d-block mb-2 text-info"></i>
                <h4 class="fw-bold mb-1">Student Registration</h4>
                <p class="text-white-50 small mb-0">Create your library member account</p>
            </div>
            
            <div class="card-body p-4 p-md-5">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="name" name="name" 
                                   placeholder="e.g. John Doe" required value="<?php echo htmlspecialchars($name); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">College Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" class="form-control border-start-0" id="email" name="email" 
                                   placeholder="student@college.edu" required value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" class="form-control border-start-0" id="password" name="password" 
                                   placeholder="At least 6 characters" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0">
                                <i class="bi bi-shield-check"></i>
                            </span>
                            <input type="password" class="form-control border-start-0" id="confirm_password" name="confirm_password" 
                                   placeholder="Re-enter password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 fs-6 mb-3 shadow-sm">
                        <i class="bi bi-check-circle me-2"></i>Create Account
                    </button>
                </form>

                <div class="text-center pt-2 border-top">
                    <p class="text-muted small mb-0">Already registered? <a href="login.php?role=student" class="fw-semibold text-primary">Log in here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
