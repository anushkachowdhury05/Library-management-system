<?php
/**
 * Add New Book Controller & Page
 * Software Engineering-I Academic Prototype
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Enforce admin privileges
require_admin();

$error = '';
$book_id = '';
$title = '';
$author = '';
$category = '';
$quantity = 1;

// Suggest next book ID automatically
try {
    $maxId = (int)$pdo->query("SELECT MAX(book_id) FROM books")->fetchColumn();
    $suggestedId = $maxId ? ($maxId + 1) : 101;
    if ($suggestedId < 101) $suggestedId = 101;
    $book_id = $suggestedId;
} catch (Exception $e) {
    $suggestedId = 109;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id  = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    $title    = trim($_POST['title'] ?? '');
    $author   = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Validation
    if ($book_id <= 0) {
        $error = 'Please provide a valid positive numeric Book ID.';
    } elseif (empty($title)) {
        $error = 'Book title is required.';
    } elseif (empty($author)) {
        $error = 'Author name is required.';
    } elseif (empty($category)) {
        $error = 'Category / Subject is required.';
    } elseif ($quantity < 1) {
        $error = 'Total quantity must be at least 1 copy.';
    } else {
        // Check for duplicate Book ID
        $checkStmt = $pdo->prepare("SELECT book_id FROM books WHERE book_id = ? LIMIT 1");
        $checkStmt->execute([$book_id]);
        if ($checkStmt->fetch()) {
            $error = "A book with ID #{$book_id} already exists in the catalogue. Please choose a different ID.";
        } else {
            try {
                // Insert new book with available_quantity = quantity
                $insertStmt = $pdo->prepare("
                    INSERT INTO books (book_id, title, author, category, quantity, available_quantity) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insertStmt->execute([$book_id, $title, $author, $category, $quantity, $quantity]);

                set_flash('success', "Book added successfully! \"$title\" (ID #$book_id) is now available in the library catalogue.");
                header("Location: admin_dashboard.php");
                exit;
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Add New Book - Admin Portal';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-8 col-lg-6">
        <div class="card card-custom shadow-sm border-0 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Add New Book to Catalogue
                </h5>
                <a href="admin_dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>

            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="add_book.php" novalidate>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="book_id" class="form-label fw-semibold">Book ID <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="book_id" name="book_id" 
                                   required min="1" value="<?php echo htmlspecialchars($book_id); ?>">
                            <div class="form-text small">e.g. 109</div>
                        </div>

                        <div class="col-md-8">
                            <label for="category" class="form-label fw-semibold">Category / Field <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category" name="category" 
                                   placeholder="e.g. Programming, DBMS, AI, OS" required
                                   value="<?php echo htmlspecialchars($category); ?>">
                        </div>

                        <div class="col-12">
                            <label for="title" class="form-label fw-semibold">Book Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   placeholder="e.g. Design Patterns: Elements of Reusable Object-Oriented Software" 
                                   required value="<?php echo htmlspecialchars($title); ?>">
                        </div>

                        <div class="col-md-8">
                            <label for="author" class="form-label fw-semibold">Author(s) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="author" name="author" 
                                   placeholder="e.g. Erich Gamma, Richard Helm" 
                                   required value="<?php echo htmlspecialchars($author); ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="quantity" class="form-label fw-semibold">Total Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   required min="1" max="100" value="<?php echo htmlspecialchars($quantity); ?>">
                            <div class="form-text small">Initial stock count</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="admin_dashboard.php" class="btn btn-outline-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary-custom px-4 shadow-sm">
                            <i class="bi bi-check-circle me-1"></i> Save Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
