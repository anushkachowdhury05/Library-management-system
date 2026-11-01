<?php
/**
 * Book Borrowing Controller
 * Software Engineering-I Academic Prototype
 * 
 * Rules:
 * 1. Only authenticated students can borrow books.
 * 2. Checks available_quantity > 0 before proceeding.
 * 3. Prevents duplicate active borrowing of the same title by the same student.
 * 4. Atomically decrements available_quantity by 1 in the books table.
 * 5. Creates a record in transactions table with status 'issued' and 14-day due date.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Ensure user is authenticated as student
require_student();

$userId = $_SESSION['user_id'];
$redirectTo = $_POST['redirect_to'] ?? $_GET['redirect_to'] ?? 'student_dashboard.php';

// Sanitize redirect URL to prevent open redirect
if (!in_array($redirectTo, ['student_dashboard.php', 'catalogue.php'])) {
    $redirectTo = 'student_dashboard.php';
}

$bookId = isset($_POST['book_id']) ? (int)$_POST['book_id'] : (isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0);

if ($bookId <= 0) {
    set_flash('danger', 'Invalid book selection.');
    header("Location: $redirectTo");
    exit;
}

try {
    // Begin atomic transaction
    $pdo->beginTransaction();

    // 1. Fetch book details with row locking for concurrency safety
    $bookStmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ? FOR UPDATE");
    $bookStmt->execute([$bookId]);
    $book = $bookStmt->fetch();

    if (!$book) {
        $pdo->rollBack();
        set_flash('danger', 'Book not found in library catalogue.');
        header("Location: $redirectTo");
        exit;
    }

    // 2. Rule: Check if available quantity > 0
    if ($book['available_quantity'] <= 0) {
        $pdo->rollBack();
        set_flash('danger', 'Book is currently unavailable. All copies are issued.');
        header("Location: $redirectTo");
        exit;
    }

    // 3. Rule: Check if student already has an active issued copy of this book
    $activeStmt = $pdo->prepare("SELECT transaction_id FROM transactions WHERE user_id = ? AND book_id = ? AND status = 'issued' LIMIT 1");
    $activeStmt->execute([$userId, $bookId]);
    if ($activeStmt->fetch()) {
        $pdo->rollBack();
        set_flash('warning', 'You currently already have a borrowed copy of "' . htmlspecialchars($book['title']) . '". Please return it before borrowing another copy.');
        header("Location: $redirectTo");
        exit;
    }

    // 4. Calculate issue date (today) and due date (today + 14 days)
    $issueDate = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime('+14 days'));

    // 5. Insert new transaction record
    $txStmt = $pdo->prepare("
        INSERT INTO transactions (user_id, book_id, issue_date, due_date, status) 
        VALUES (?, ?, ?, ?, 'issued')
    ");
    $txStmt->execute([$userId, $bookId, $issueDate, $dueDate]);

    // 6. Rule: Decrement available_quantity by 1
    $updateStmt = $pdo->prepare("
        UPDATE books 
        SET available_quantity = available_quantity - 1 
        WHERE book_id = ? AND available_quantity > 0
    ");
    $updateStmt->execute([$bookId]);

    // Commit all changes
    $pdo->commit();

    set_flash('success', 'Book borrowed successfully! "' . htmlspecialchars($book['title']) . '" is due on ' . date('M d, Y', strtotime($dueDate)) . '.');
    header("Location: $redirectTo");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'An error occurred while borrowing the book. Please try again.');
    header("Location: $redirectTo");
    exit;
}
