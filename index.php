<?php
/**
 * Home Page - Library Management System
 * Software Engineering-I Academic Prototype
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Fetch quick statistics for display
$totalBooks = 0;
$totalAvailable = 0;
$totalIssued = 0;
$totalStudents = 0;

try {
    $totalBooks = (int)$pdo->query("SELECT SUM(quantity) FROM books")->fetchColumn() ?: 0;
    $totalAvailable = (int)$pdo->query("SELECT SUM(available_quantity) FROM books")->fetchColumn() ?: 0;
    $totalIssued = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'issued'")->fetchColumn() ?: 0;
    $totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn() ?: 0;
} catch (Exception $e) {
    // Graceful fallback if query fails
}

$page_title = 'Welcome - College Library Management System';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<div class="row align-items-center py-4 mb-4">
    <div class="col-lg-7 pe-lg-5">
        <div class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill mb-3 fw-semibold">
            <i class="bi bi-mortarboard-fill me-1"></i> Software Engineering-I Academic Project
        </div>
        <h1 class="display-4 fw-bold text-dark lh-tight mb-3">
            LIBRARY MANAGEMENT SYSTEM
        </h1>
        <p class="lead text-muted mb-4 fs-5">
            <strong>Smart and Simple Digital Library Management.</strong> Designed to streamline catalogue management, book borrowing, and returns for college students and academic administrators.
        </p>

        <div class="d-flex flex-wrap gap-3 mb-4">
            <?php if (is_logged_in()): ?>
                <?php if (is_admin()): ?>
                    <a href="admin_dashboard.php" class="btn btn-primary-custom btn-lg shadow-sm">
                        <i class="bi bi-speedometer2 me-2"></i>Go to Admin Dashboard
                    </a>
                <?php else: ?>
                    <a href="student_dashboard.php" class="btn btn-primary-custom btn-lg shadow-sm">
                        <i class="bi bi-journal-bookmark me-2"></i>Go to Student Dashboard
                    </a>
                <?php endif; ?>
                <a href="catalogue.php" class="btn btn-outline-navy btn-lg">
                    <i class="bi bi-search me-2"></i>Browse Catalogue
                </a>
            <?php else: ?>
                <a href="login.php?role=student" class="btn btn-primary-custom btn-lg shadow-sm">
                    <i class="bi bi-person-fill me-2"></i>Student Login
                </a>
                <a href="login.php?role=admin" class="btn btn-outline-navy btn-lg">
                    <i class="bi bi-shield-lock-fill me-2"></i>Admin Login
                </a>
                <a href="catalogue.php" class="btn btn-accent-custom btn-lg">
                    <i class="bi bi-search me-2"></i>Search Catalogue
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5 mt-4 mt-lg-0">
        <!-- Digital Library Showcase Card -->
        <div class="card card-custom p-4 border-0 shadow">
            <div class="d-flex align-items-center gap-3 mb-4 border-bottom pb-3">
                <div class="stat-icon primary">
                    <i class="bi bi-book-half"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Digital Library Portal</h5>
                    <small class="text-muted">Instant Access & Real-Time Tracking</small>
                </div>
            </div>

            <!-- Quick Live Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 text-center border">
                        <div class="text-primary fw-bold fs-3"><?php echo $totalBooks; ?></div>
                        <div class="small text-muted fw-semibold">Total Stock</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 text-center border">
                        <div class="text-success fw-bold fs-3"><?php echo $totalAvailable; ?></div>
                        <div class="small text-muted fw-semibold">Available Books</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 text-center border">
                        <div class="text-warning fw-bold fs-3"><?php echo $totalIssued; ?></div>
                        <div class="small text-muted fw-semibold">Currently Issued</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 text-center border">
                        <div class="text-info fw-bold fs-3"><?php echo $totalStudents; ?></div>
                        <div class="small text-muted fw-semibold">Active Students</div>
                    </div>
                </div>
            </div>

            <!-- Academic Demo Acceptance Test Scenario Info Box -->
            <div class="alert alert-info border-0 rounded-3 mb-0 small">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-info-circle-fill text-primary"></i>
                    <strong>Academic Evaluation Demo:</strong>
                </div>
                <p class="mb-1 text-secondary">
                    Demonstrates book search, 1-click borrowing, real-time quantity decrement, admin audit records, and automated return inventory restock.
                </p>
                <div class="d-flex justify-content-between pt-1">
                    <span>Admin: <code>admin@library.com</code></span>
                    <span>Student: <code>student@library.com</code></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="row g-4 my-4">
    <div class="col-md-4">
        <div class="card card-custom h-100 p-4">
            <div class="stat-icon primary mb-3">
                <i class="bi bi-search"></i>
            </div>
            <h5 class="fw-bold text-dark">Instant Catalogue Search</h5>
            <p class="text-muted small mb-0">
                Quickly locate books by title, author, or academic subject area with real-time live availability counts and stock status.
            </p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom h-100 p-4">
            <div class="stat-icon success mb-3">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <h5 class="fw-bold text-dark">Seamless Borrow & Return</h5>
            <p class="text-muted small mb-0">
                Automated inventory updates safeguard against zero-quantity borrowing, calculate 14-day due dates, and restore stock upon return.
            </p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom h-100 p-4">
            <div class="stat-icon warning mb-3">
                <i class="bi bi-shield-check"></i>
            </div>
            <h5 class="fw-bold text-dark">Admin Inventory Control</h5>
            <p class="text-muted small mb-0">
                Librarians can easily add new acquisitions, monitor all active loans, manage returns, and view complete borrowing audit logs.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
