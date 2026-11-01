<?php
/**
 * User Login (Student & Admin)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to respective dashboard
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: student_dashboard.php");
    }
    exit;
}

$error = '';
$prefill_role = isset($_GET['role']) ? $_GET['role'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        // Query user by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verify password with password_verify or plain-text fallback (for initial seed compatibility)
        if ($user && (password_verify($password, $user['password']) || $user['password'] === $password)) {
            // Auto-rehash to secure bcrypt if stored as plain text or legacy hash
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT) || $user['password'] === $password) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateHashStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $updateHashStmt->execute([$newHash, $user['user_id']]);
            }

            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            set_flash('success', "Welcome back, {$user['name']}!");

            // Redirect based on user role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit;
        } else {
            $error = 'Invalid email address or password. Please try again.';
        }
    }
}

$page_title = 'Login - Library Management System';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-6 col-lg-5">
        <div class="card auth-card overflow-hidden">
            <div class="auth-header">
                <i class="bi bi-shield-lock-fill fs-1 d-block mb-2 text-info"></i>
                <h4 class="fw-bold mb-1">Account Login</h4>
                <p class="text-white-50 small mb-0">Sign in to access your digital library portal</p>
            </div>
            
            <div class="card-body p-4 p-md-5">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" class="form-control border-start-0" id="email" name="email" 
                                   placeholder="name@college.edu" required 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ($prefill_role === 'admin' ? 'admin@library.com' : ($prefill_role === 'student' ? 'student@library.com' : ''))); ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="password" class="form-label fw-semibold mb-1">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0">
                                <i class="bi bi-key"></i>
                            </span>
                            <input type="password" class="form-control border-start-0" id="password" name="password" 
                                   placeholder="Enter your password" required
                                   value="<?php echo htmlspecialchars($prefill_role === 'admin' ? 'admin123' : ($prefill_role === 'student' ? 'student123' : '')); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 fs-6 mb-3 shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </form>

                <div class="text-center pt-2 border-top">
                    <p class="text-muted small mb-0">Don't have a student account? <a href="register.php" class="fw-semibold text-primary">Register here</a></p>
                </div>

                <!-- 1-Click Demo Credentials Autofill Helper for Evaluators -->
                <div class="mt-4 p-3 bg-light rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-bold small text-dark"><i class="bi bi-magic me-1 text-primary"></i>Demo Quick Fill:</span>
                        <span class="badge bg-secondary text-uppercase" style="font-size: 0.65rem;">Academic Demo</span>
                    </div>
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="button" id="fillStudentBtn" class="btn btn-outline-primary btn-sm flex-fill">
                            <i class="bi bi-person me-1"></i>Fill Student
                        </button>
                        <button type="button" id="fillAdminBtn" class="btn btn-outline-dark btn-sm flex-fill">
                            <i class="bi bi-shield-check me-1"></i>Fill Admin
                        </button>
                    </div>
                    <div class="mt-2 text-muted" style="font-size: 0.75rem;">
                        <strong>Student:</strong> student@library.com / student123<br>
                        <strong>Admin:</strong> admin@library.com / admin123
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
