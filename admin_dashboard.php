<?php
/**
 * Admin Dashboard - Library Management System
 * Software Engineering-I Academic Prototype
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Enforce admin privileges
require_admin();

$activeTab = $_GET['tab'] ?? 'books';
if (!in_array($activeTab, ['books', 'issued', 'history', 'students'])) {
    $activeTab = 'books';
}

// ----------------------------------------------------
// 1. Fetch Summary Metrics
// ----------------------------------------------------
// Total Books (Sum of total inventory copies)
$totalBooks = (int)$pdo->query("SELECT SUM(quantity) FROM books")->fetchColumn() ?: 0;
$totalTitles = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn() ?: 0;

// Available Books (Sum of available copies across all titles)
$availableBooks = (int)$pdo->query("SELECT SUM(available_quantity) FROM books")->fetchColumn() ?: 0;

// Issued Books (Count of currently issued transactions)
$issuedBooks = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'issued'")->fetchColumn() ?: 0;

// Total Students
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn() ?: 0;

// ----------------------------------------------------
// 2. Fetch All Books Inventory
// ----------------------------------------------------
$searchQuery = trim($_GET['search'] ?? '');
$bookSql = "SELECT * FROM books WHERE 1=1";
$bookParams = [];

if (!empty($searchQuery)) {
    $bookSql .= " AND (title LIKE ? OR author LIKE ? OR category LIKE ?)";
    $bookParams[] = "%$searchQuery%";
    $bookParams[] = "%$searchQuery%";
    $bookParams[] = "%$searchQuery%";
}
$bookSql .= " ORDER BY book_id ASC";
$booksStmt = $pdo->prepare($bookSql);
$booksStmt->execute($bookParams);
$allBooks = $booksStmt->fetchAll();

// ----------------------------------------------------
// 3. Fetch Currently Issued Books (for Demo acceptance test!)
// ----------------------------------------------------
$issuedStmt = $pdo->query("
    SELECT t.transaction_id, t.issue_date, t.due_date, t.status,
           b.book_id, b.title AS book_title, b.author AS book_author, b.category, b.available_quantity, b.quantity,
           u.user_id, u.name AS student_name, u.email AS student_email
    FROM transactions t
    JOIN books b ON t.book_id = b.book_id
    JOIN users u ON t.user_id = u.user_id
    WHERE t.status = 'issued'
    ORDER BY t.issue_date DESC
");
$issuedRecords = $issuedStmt->fetchAll();

// ----------------------------------------------------
// 4. Fetch All Borrowing History / Audit Records
// ----------------------------------------------------
$historyStmt = $pdo->query("
    SELECT t.transaction_id, t.issue_date, t.due_date, t.return_date, t.status,
           b.book_id, b.title AS book_title, b.category,
           u.user_id, u.name AS student_name, u.email AS student_email
    FROM transactions t
    JOIN books b ON t.book_id = b.book_id
    JOIN users u ON t.user_id = u.user_id
    ORDER BY t.transaction_id DESC
");
$allTransactions = $historyStmt->fetchAll();

// ----------------------------------------------------
// 5. Fetch Student Directory
// ----------------------------------------------------
$studentsStmt = $pdo->query("
    SELECT u.user_id, u.name, u.email, u.created_at,
           (SELECT COUNT(*) FROM transactions WHERE user_id = u.user_id AND status = 'issued') AS currently_issued,
           (SELECT COUNT(*) FROM transactions WHERE user_id = u.user_id) AS total_borrowed
    FROM users u
    WHERE u.role = 'student'
    ORDER BY u.name ASC
");
$studentsList = $studentsStmt->fetchAll();

$page_title = 'Admin Dashboard - Library Management System';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Admin Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
    <div>
        <div class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill mb-2 fw-semibold">
            <i class="bi bi-shield-check me-1"></i> Administrator Portal
        </div>
        <h2 class="fw-bold text-dark mb-0">Library Administration Dashboard</h2>
        <p class="text-muted small mb-0">Monitor catalog circulation, issue & return records, and manage student accounts.</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <a href="add_book.php" class="btn btn-primary-custom shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New Book
        </a>
    </div>
</div>

<!-- Summary Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-bookshelf"></i>
            </div>
            <div>
                <div class="stat-value"><?php echo $totalBooks; ?></div>
                <div class="stat-label">Total Books</div>
                <small class="text-muted"><?php echo $totalTitles; ?> unique titles</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-check2-square"></i>
            </div>
            <div>
                <div class="stat-value text-success"><?php echo $availableBooks; ?></div>
                <div class="stat-label">Available Books</div>
                <small class="text-muted">In stock on shelf</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-arrow-up-right-circle"></i>
            </div>
            <div>
                <div class="stat-value text-warning"><?php echo $issuedBooks; ?></div>
                <div class="stat-label">Issued Books</div>
                <small class="text-muted">Currently with students</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <div class="stat-value"><?php echo $totalStudents; ?></div>
                <div class="stat-label">Total Students</div>
                <small class="text-muted">Registered members</small>
            </div>
        </div>
    </div>
</div>

<!-- Admin Navigation Pills -->
<ul class="nav nav-pills mb-4 gap-2" id="adminTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab === 'books') ? 'active' : ''; ?> fw-semibold rounded-pill px-4" 
                id="books-tab" data-bs-toggle="pill" data-bs-target="#books-section" type="button" role="tab">
            <i class="bi bi-journals me-1"></i> Book Inventory (<?php echo count($allBooks); ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab === 'issued') ? 'active' : ''; ?> fw-semibold rounded-pill px-4 position-relative" 
                id="issued-tab" data-bs-toggle="pill" data-bs-target="#issued-section" type="button" role="tab">
            <i class="bi bi-journal-arrow-up me-1"></i> Issued Books
            <?php if ($issuedBooks > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo $issuedBooks; ?>
                </span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab === 'history') ? 'active' : ''; ?> fw-semibold rounded-pill px-4" 
                id="history-tab" data-bs-toggle="pill" data-bs-target="#history-section" type="button" role="tab">
            <i class="bi bi-clock-history me-1"></i> All Transactions (<?php echo count($allTransactions); ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab === 'students') ? 'active' : ''; ?> fw-semibold rounded-pill px-4" 
                id="students-tab" data-bs-toggle="pill" data-bs-target="#students-section" type="button" role="tab">
            <i class="bi bi-people me-1"></i> Registered Students (<?php echo count($studentsList); ?>)
        </button>
    </li>
</ul>

<!-- Tab Content Area -->
<div class="tab-content" id="adminTabContent">

    <!-- ================= 1. BOOK INVENTORY ================= -->
    <div class="tab-pane fade <?php echo ($activeTab === 'books') ? 'show active' : ''; ?>" id="books-section" role="tabpanel">
        
        <div class="card card-custom p-3 mb-4 shadow-sm">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="liveSearchInput" class="form-control border-start-0" 
                               placeholder="Live search books by title, author, or category...">
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="add_book.php" class="btn btn-primary-custom w-100 w-md-auto">
                        <i class="bi bi-plus-lg me-1"></i> Add New Book
                    </a>
                </div>
            </div>
        </div>

        <div class="card card-custom border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-list-check text-primary me-2"></i>Complete Book Catalogue
                </h6>
                <span class="small text-muted"><?php echo count($allBooks); ?> Titles in Database</span>
            </div>

            <div class="table-responsive">
                <table class="table table-custom mb-0 align-middle">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 80px;">Book ID</th>
                            <th scope="col">Title</th>
                            <th scope="col">Author</th>
                            <th scope="col">Category</th>
                            <th scope="col" class="text-center">Total Copies</th>
                            <th scope="col" class="text-center">Available</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-end" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="catalogueTableBody">
                        <?php if (empty($allBooks)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No books currently in the catalogue.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allBooks as $book): ?>
                                <?php $isAvail = $book['available_quantity'] > 0; ?>
                                <tr>
                                    <td class="fw-bold text-secondary">#<?php echo htmlspecialchars($book['book_id']); ?></td>
                                    <td>
                                        <strong class="book-title text-dark"><?php echo htmlspecialchars($book['title']); ?></strong>
                                    </td>
                                    <td class="book-author text-muted"><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td>
                                        <span class="book-category badge-category"><?php echo htmlspecialchars($book['category']); ?></span>
                                    </td>
                                    <td class="text-center fw-semibold"><?php echo (int)$book['quantity']; ?></td>
                                    <td class="text-center">
                                        <span class="fw-bold <?php echo $isAvail ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo (int)$book['available_quantity']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($isAvail): ?>
                                            <span class="badge-available">
                                                <i class="bi bi-check-circle-fill"></i> Available (<?php echo $book['available_quantity']; ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-unavailable">
                                                <i class="bi bi-x-circle-fill"></i> Unavailable (0)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="delete_book.php" class="d-inline">
                                            <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($book['book_id']); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-confirm-delete" 
                                                    data-book-title="<?php echo htmlspecialchars($book['title']); ?>"
                                                    title="Delete Book">
                                                <i class="bi bi-trash3"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ================= 2. CURRENTLY ISSUED BOOKS (DEMO ACCEPTANCE TEST) ================= -->
    <div class="tab-pane fade <?php echo ($activeTab === 'issued') ? 'show active' : ''; ?>" id="issued-section" role="tabpanel">
        
        <div class="card card-custom border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-arrow-repeat text-warning me-2"></i>Currently Issued Books
                    </h6>
                    <small class="text-muted">Students currently in possession of library books. Process returns below.</small>
                </div>
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-semibold">
                    <?php echo count($issuedRecords); ?> Active Loan(s)
                </span>
            </div>

            <?php if (empty($issuedRecords)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-check-circle-fill fs-1 text-success d-block mb-3"></i>
                    <h5 class="fw-bold text-dark">No Active Loans</h5>
                    <p class="small mb-0">All library books are currently returned and accounted for on shelves.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom mb-0 align-middle">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 80px;">TX ID</th>
                                <th scope="col">Student Name & Email</th>
                                <th scope="col">Book Title (ID)</th>
                                <th scope="col">Category</th>
                                <th scope="col">Issue Date</th>
                                <th scope="col">Due Date</th>
                                <th scope="col" class="text-center">Available Stock</th>
                                <th scope="col" class="text-end" style="width: 170px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issuedRecords as $row): ?>
                                <?php 
                                    $isOverdue = strtotime($row['due_date']) < strtotime(date('Y-m-d'));
                                ?>
                                <tr>
                                    <td class="fw-bold text-secondary">#TX-<?php echo htmlspecialchars($row['transaction_id']); ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($row['student_email']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($row['book_title']); ?></div>
                                        <div class="small text-muted">ID: #<?php echo htmlspecialchars($row['book_id']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge-category"><?php echo htmlspecialchars($row['category']); ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['issue_date'])); ?></td>
                                    <td>
                                        <span class="<?php echo $isOverdue ? 'text-danger fw-bold' : ''; ?>">
                                            <?php echo date('M d, Y', strtotime($row['due_date'])); ?>
                                            <?php if ($isOverdue): ?>
                                                <i class="bi bi-exclamation-circle text-danger ms-1" title="Overdue"></i>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">
                                            <?php echo (int)$row['available_quantity']; ?> of <?php echo (int)$row['quantity']; ?> available
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <!-- RETURN BOOK BUTTON: RESTORES AVAILABLE QUANTITY (PRIMARY ACCEPTANCE TEST STEP 12) -->
                                        <form method="POST" action="return.php" class="d-inline">
                                            <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($row['transaction_id']); ?>">
                                            <input type="hidden" name="redirect_to" value="admin_dashboard.php">
                                            <button type="submit" class="btn btn-sm btn-success shadow-sm">
                                                <i class="bi bi-arrow-return-left me-1"></i> Return Book
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ================= 3. ALL TRANSACTIONS AUDIT LOG ================= -->
    <div class="tab-pane fade <?php echo ($activeTab === 'history') ? 'show active' : ''; ?>" id="history-section" role="tabpanel">
        
        <div class="card card-custom border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-journal-text text-primary me-2"></i>Borrowing & Return History Records
                </h6>
                <span class="small text-muted"><?php echo count($allTransactions); ?> Total Transactions</span>
            </div>

            <?php if (empty($allTransactions)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No transaction records exist yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom mb-0 align-middle">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 80px;">TX ID</th>
                                <th scope="col">Student</th>
                                <th scope="col">Book Title</th>
                                <th scope="col">Issue Date</th>
                                <th scope="col">Due Date</th>
                                <th scope="col">Return Date</th>
                                <th scope="col" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allTransactions as $tx): ?>
                                <tr>
                                    <td class="fw-bold text-secondary">#TX-<?php echo htmlspecialchars($tx['transaction_id']); ?></td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($tx['student_name']); ?></span>
                                        <div class="small text-muted"><?php echo htmlspecialchars($tx['student_email']); ?></div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($tx['book_title']); ?></span>
                                        <div class="small text-muted">ID: #<?php echo htmlspecialchars($tx['book_id']); ?></div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($tx['issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($tx['due_date'])); ?></td>
                                    <td>
                                        <?php if ($tx['return_date']): ?>
                                            <span class="text-success fw-semibold">
                                                <?php echo date('M d, Y', strtotime($tx['return_date'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($tx['status'] === 'issued'): ?>
                                            <span class="badge-status-issued">
                                                <i class="bi bi-clock me-1"></i> Issued
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-status-returned">
                                                <i class="bi bi-check2-circle me-1"></i> Returned
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ================= 4. REGISTERED STUDENTS ================= -->
    <div class="tab-pane fade <?php echo ($activeTab === 'students') ? 'show active' : ''; ?>" id="students-section" role="tabpanel">
        
        <div class="card card-custom border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-people text-primary me-2"></i>Registered Student Members
                </h6>
                <span class="small text-muted"><?php echo count($studentsList); ?> Students Registered</span>
            </div>

            <?php if (empty($studentsList)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-person-x fs-1 d-block mb-3 text-secondary"></i>
                    No students registered in the system yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom mb-0 align-middle">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 90px;">Student ID</th>
                                <th scope="col">Full Name</th>
                                <th scope="col">College Email</th>
                                <th scope="col" class="text-center">Currently Borrowed</th>
                                <th scope="col" class="text-center">Total Lifetime Loans</th>
                                <th scope="col">Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studentsList as $stu): ?>
                                <tr>
                                    <td class="fw-bold text-secondary">#STU-<?php echo str_pad($stu['user_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($stu['name']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($stu['email']); ?></td>
                                    <td class="text-center">
                                        <?php if ((int)$stu['currently_issued'] > 0): ?>
                                            <span class="badge bg-warning text-dark px-2 py-1">
                                                <?php echo (int)$stu['currently_issued']; ?> book(s)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-semibold text-secondary">
                                        <?php echo (int)$stu['total_borrowed']; ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?php echo date('M d, Y', strtotime($stu['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
