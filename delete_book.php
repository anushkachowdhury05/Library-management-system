<?php
/**
 * Delete Book Controller
 * Software Engineering-I Academic Prototype
 * 
 * Rules:
 * 1. Admin only.
 * 2. Cannot delete a book if it has active 'issued' transactions.
 * 3. Safely removes the book from books table once verified.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Enforce admin privileges
require_admin();

$bookId = isset($_POST['book_id']) ? (int)$_POST['book_id'] : (isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0);

if ($bookId <= 0) {
    set_flash('danger', 'Invalid book selection.');
    header("Location: admin_dashboard.php");
    exit;
}

try {
    // 1. Fetch book to get title
    $stmt = $pdo->prepare("SELECT title FROM books WHERE book_id = ?");
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();

    if (!$book) {
        set_flash('danger', 'Book not found.');
        header("Location: admin_dashboard.php");
        exit;
    }

    // 2. Rule: Check if there are active loans
    $checkActive = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE book_id = ? AND status = 'issued'");
    $checkActive->execute([$bookId]);
    $activeCount = (int)$checkActive->fetchColumn();

    if ($activeCount > 0) {
        set_flash('danger', "Cannot delete \"{$book['title']}\". There are currently {$activeCount} copy(ies) issued to students. All copies must be returned before deletion.");
        header("Location: admin_dashboard.php");
        exit;
    }

    // 3. Delete any past returned transactions if needed, or foreign key CASCADE handles it
    $deleteStmt = $pdo->prepare("DELETE FROM books WHERE book_id = ?");
    $deleteStmt->execute([$bookId]);

    set_flash('success', "Book deleted successfully! \"{$book['title']}\" (ID #$bookId) has been removed from catalogue.");
    header("Location: admin_dashboard.php");
    exit;

} catch (PDOException $e) {
    set_flash('danger', 'Database error while attempting to delete the book: ' . $e->getMessage());
    header("Location: admin_dashboard.php");
    exit;
}
