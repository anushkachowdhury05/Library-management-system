<?php
/**
 * Book Return Controller
 * Software Engineering-I Academic Prototype
 * 
 * Rules:
 * 1. Authenticated students can return their own borrowed books.
 * 2. Administrators can process returns on behalf of students from the Admin Portal.
 * 3. Verifies transaction is currently in 'issued' status (prevents duplicate returns).
 * 4. Atomically increments available_quantity by 1 in books table (up to total quantity).
 * 5. Updates transactions record status to 'returned' and logs today's return_date.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Ensure user is logged in
require_login();

$userId = $_SESSION['user_id'];
$isAdmin = is_admin();

$redirectTo = $_POST['redirect_to'] ?? $_GET['redirect_to'] ?? ($isAdmin ? 'admin_dashboard.php' : 'student_dashboard.php');
if (!in_array($redirectTo, ['admin_dashboard.php', 'student_dashboard.php', 'catalogue.php'])) {
    $redirectTo = $isAdmin ? 'admin_dashboard.php' : 'student_dashboard.php';
}

$transactionId = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : (isset($_GET['transaction_id']) ? (int)$_GET['transaction_id'] : 0);

if ($transactionId <= 0) {
    set_flash('danger', 'Invalid transaction identifier provided.');
    header("Location: $redirectTo");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Fetch transaction record with lock
    $txStmt = $pdo->prepare("
        SELECT t.*, b.title, b.quantity, b.available_quantity 
        FROM transactions t
        JOIN books b ON t.book_id = b.book_id
        WHERE t.transaction_id = ? FOR UPDATE
    ");
    $txStmt->execute([$transactionId]);
    $tx = $txStmt->fetch();

    if (!$tx) {
        $pdo->rollBack();
        set_flash('danger', 'Borrowing transaction record not found.');
        header("Location: $redirectTo");
        exit;
    }

    // 2. Authorization check: if not admin, must be the borrower student
    if (!$isAdmin && (int)$tx['user_id'] !== (int)$userId) {
        $pdo->rollBack();
        set_flash('danger', 'Unauthorized: You may only return books borrowed under your own account.');
        header("Location: student_dashboard.php");
        exit;
    }

    // 3. Rule: Check if transaction is still active ('issued')
    if ($tx['status'] !== 'issued') {
        $pdo->rollBack();
        set_flash('warning', 'This book has already been marked as returned.');
        header("Location: $redirectTo");
        exit;
    }

    $returnDate = date('Y-m-d');

    // 4. Update transaction status and return date
    $updateTx = $pdo->prepare("
        UPDATE transactions 
        SET status = 'returned', return_date = ? 
        WHERE transaction_id = ? AND status = 'issued'
    ");
    $updateTx->execute([$returnDate, $transactionId]);

    // 5. Rule: Increment book available_quantity by 1 (cannot exceed original total quantity)
    $updateBook = $pdo->prepare("
        UPDATE books 
        SET available_quantity = LEAST(quantity, available_quantity + 1) 
        WHERE book_id = ?
    ");
    $updateBook->execute([$tx['book_id']]);

    // Commit transaction
    $pdo->commit();

    set_flash('success', 'Book returned successfully! "' . htmlspecialchars($tx['title']) . '" is now back in library inventory.');
    header("Location: $redirectTo");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'An error occurred while returning the book. Please try again.');
    header("Location: $redirectTo");
    exit;
}
