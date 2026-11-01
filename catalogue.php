<?php
/**
 * Dedicated Book Catalogue Page
 * Software Engineering-I Academic Prototype
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

// Fetch distinct categories for the filter dropdown
$catStmt = $pdo->query("SELECT DISTINCT category FROM books ORDER BY category ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Build SQL query with optional filters
$query = "SELECT * FROM books WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR author LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
}

$query .= " ORDER BY book_id ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();

// If student is logged in, find which books they currently have borrowed
$borrowedBookIds = [];
if (is_student()) {
    $bStmt = $pdo->prepare("SELECT book_id FROM transactions WHERE user_id = ? AND status = 'issued'");
    $bStmt->execute([$_SESSION['user_id']]);
    $borrowedBookIds = $bStmt->fetchAll(PDO::FETCH_COLUMN);
}

$page_title = 'Book Catalogue - Library Management System';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header Banner -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">
            <i class="bi bi-journals text-primary me-2"></i>Book Catalogue
        </h2>
        <p class="text-muted mb-0">Browse through the college library collection and check real-time availability.</p>
    </div>
    <div class="mt-3 mt-md-0">
        <?php if (is_admin()): ?>
            <a href="add_book.php" class="btn btn-primary-custom shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Add New Book
            </a>
        <?php elseif (is_student()): ?>
            <a href="student_dashboard.php" class="btn btn-outline-navy">
                <i class="bi bi-bookmark-check me-1"></i> My Borrowed Books
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Search & Filter Controls -->
<div class="card card-custom p-3 mb-4 shadow-sm">
    <form method="GET" action="catalogue.php" class="row g-2 align-items-center">
        <div class="col-md-6 col-lg-7">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="liveSearchInput" name="search" class="form-control border-start-0" 
                       placeholder="Search books by title or author... (e.g. Database System Concepts)" 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        
        <div class="col-md-4 col-lg-3">
            <select id="categoryFilter" name="category" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary-custom w-100">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            <?php if (!empty($search) || !empty($category)): ?>
                <a href="catalogue.php" class="btn btn-outline-secondary" title="Clear Filters">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Books Catalogue Table -->
<div class="card card-custom border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <span class="fw-bold text-dark">
            <i class="bi bi-list-columns-reverse me-1 text-primary"></i> Available Titles (<?php echo count($books); ?> books listed)
        </span>
        <span class="badge bg-light text-dark border">
            Live Inventory
        </span>
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
                    <th scope="col" class="text-end" style="width: 160px;">Action</th>
                </tr>
            </thead>
            <tbody id="catalogueTableBody">
                <?php if (empty($books)): ?>
                    <tr class="no-results-row">
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-emoji-neutral fs-2 d-block mb-2 text-secondary"></i>
                            No books match the specified search criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>
                        <?php
                            $isAvailable = $book['available_quantity'] > 0;
                            $alreadyBorrowed = in_array($book['book_id'], $borrowedBookIds);
                        ?>
                        <tr>
                            <td class="fw-bold text-secondary">#<?php echo htmlspecialchars($book['book_id']); ?></td>
                            <td>
                                <strong class="book-title text-dark"><?php echo htmlspecialchars($book['title']); ?></strong>
                            </td>
                            <td class="book-author text-muted"><?php echo htmlspecialchars($book['author']); ?></td>
                            <td>
                                <span class="book-category badge-category">
                                    <?php echo htmlspecialchars($book['category']); ?>
                                </span>
                            </td>
                            <td class="text-center fw-semibold"><?php echo (int)$book['quantity']; ?></td>
                            <td class="text-center">
                                <span class="fw-bold <?php echo $isAvailable ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo (int)$book['available_quantity']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($isAvailable): ?>
                                    <span class="badge-available">
                                        <i class="bi bi-check-circle-fill"></i> Available (<?php echo $book['available_quantity']; ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="badge-unavailable">
                                        <i class="bi bi-x-circle-fill"></i> Out of Stock
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if (is_student()): ?>
                                    <?php if ($alreadyBorrowed): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle p-2">
                                            <i class="bi bi-bookmark-check-fill me-1"></i> Already Issued
                                        </span>
                                    <?php elseif ($isAvailable): ?>
                                        <form method="POST" action="borrow.php" class="d-inline">
                                            <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($book['book_id']); ?>">
                                            <input type="hidden" name="redirect_to" value="catalogue.php">
                                            <button type="submit" class="btn btn-sm btn-primary-custom">
                                                <i class="bi bi-bag-plus me-1"></i> Borrow
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled>
                                            <i class="bi bi-dash-circle me-1"></i> Unavailable
                                        </button>
                                    <?php endif; ?>
                                <?php elseif (is_admin()): ?>
                                    <a href="admin_dashboard.php?tab=books&edit=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-gear me-1"></i> Manage
                                    </a>
                                <?php else: ?>
                                    <a href="login.php?role=student" class="btn btn-sm btn-outline-navy">
                                        <i class="bi bi-box-arrow-in-right me-1"></i> Login to Borrow
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
