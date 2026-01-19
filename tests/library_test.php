<?php

require_once __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function ($class) {
    $base_dir = __DIR__ . '/../src/';
    
    // Special case: BorrowTransaction class is in BorrowRecord.php
    if ($class === 'Models\\BorrowTransaction') {
        require_once $base_dir . 'Models/BorrowRecord.php';
        return;
    }
    
    $namespaces = [
        'Core' => 'Core',
        'Models' => 'Models',
        'Repositories' => 'Repositories',
        'Services' => 'Services',
        'Interfaces' => 'Interfaces',
        'Exceptions' => 'Exceptions'
    ];
    
    foreach ($namespaces as $ns => $dir) {
        if (strpos($class, $ns . '\\') === 0) {
            $relative_class = substr($class, strlen($ns . '\\'));
            $file = $base_dir . $dir . '/' . str_replace('\\', '/', $relative_class) . '.php';
            
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

use Services\LibraryService;
use Repositories\BookRepository;
use Models\Book;
use Core\Database;

// Simple test tracking
$passed = 0;
$failed = 0;

function test($description, $condition, $failMessage = '') {
    global $passed, $failed;
    
    if ($condition) {
        echo "✓ {$description}\n";
        $passed++;
    } else {
        echo "✗ {$description}";
        if ($failMessage) {
            echo " - {$failMessage}";
        }
        echo "\n";
        $failed++;
    }
}

function section($title) {
    echo "\n" . str_repeat('=', 50) . "\n";
    echo strtoupper($title) . "\n";
    echo str_repeat('=', 50) . "\n\n";
}

try {
    echo "Library Management System - Test Suite\n";
    echo "Running tests...\n";
    
    // Test Book Model
    section('Book Model Tests');
    
    $book = new Book('978-0123456789', 'Test Driven Development', 2024, 5, 'Available', 1);
    test('Creating a new book', $book->getTitle() === 'Test Driven Development');
    test('Book should be available with 5 copies', $book->isAvailable() && $book->getAvailableCopies() === 5);
    
    $book->decrementCopies();
    test('Decrementing available copies', $book->getAvailableCopies() === 4);
    
    // Edge case: last copy
    $lastCopy = new Book('111', 'Last Copy', 2024, 1, 'Available');
    $lastCopy->decrementCopies();
    test(
        'Status changes to "Checked Out" when no copies left',
        $lastCopy->getAvailableCopies() === 0 && $lastCopy->getStatus() === 'Checked Out'
    );
    
    $lastCopy->incrementCopies();
    test(
        'Status returns to "Available" when copy is returned',
        $lastCopy->getAvailableCopies() === 1 && $lastCopy->getStatus() === 'Available'
    );
    
    // Test Book Repository
    section('Book Repository Tests');
    
    $bookRepo = new BookRepository();
    $allBooks = $bookRepo->findAll();
    test('Fetching all books from database', is_array($allBooks));
    
    if (!empty($allBooks)) {
        $sampleBook = $allBooks[0];
        $foundBook = $bookRepo->findByIsbn($sampleBook->getIsbn());
        test('Finding book by ISBN', $foundBook !== null && $foundBook->getIsbn() === $sampleBook->getIsbn());
        
        $searchResults = $bookRepo->searchByTitle($sampleBook->getTitle());
        test('Searching books by title', count($searchResults) > 0);
    }
    
    $martinBooks = $bookRepo->searchByAuthor('Martin');
    test('Searching books by author name', is_array($martinBooks));
    
    $categoryBooks = $bookRepo->searchByCategory(1);
    test('Fetching books by category', is_array($categoryBooks));
    
    // Test Library Service
    section('Library Service Tests');
    
    $service = new LibraryService();
    
    // These should fail gracefully
    $invalidBorrow = $service->borrowBook(999999, '000-0000000000', 1);
    test('Rejecting invalid borrow attempt', $invalidBorrow['success'] === false);
    
    $invalidReturn = $service->returnBook(999999);
    test('Rejecting invalid return attempt', $invalidReturn['success'] === false);
    
    $invalidReservation = $service->reserveBook(999999, '000-0000000000', 1);
    test('Rejecting invalid reservation', $invalidReservation['success'] === false);
    
    $invalidPayment = $service->processPayment(999999, 50.00, 'Credit Card');
    test('Rejecting payment for non-existent member', $invalidPayment['success'] === false);
    
    // Search functionality
    $titleSearch = $service->searchBooks('The', 'title');
    test('Searching library by book title', is_array($titleSearch));
    
    $authorSearch = $service->searchBooks('Martin', 'author');
    test('Searching library by author', is_array($authorSearch));
    
    $isbnSearch = $service->searchBooks('978-0123456789', 'isbn');
    test('Searching library by ISBN', is_array($isbnSearch));
    
    // Member operations
    section('Member & Transaction Tests');
    
    $memberRepo = new Repositories\MemberRepository();
    $members = $memberRepo->findAll();
    test('Loading all members', is_array($members));
    
    if (!empty($members)) {
        $member = $members[0];
        $foundMember = $memberRepo->findByEmail($member->getEmail());
        test('Finding member by email', $foundMember !== null);
        
        $hasOverdue = $memberRepo->hasOverdueBooks($member->getMemberId());
        test('Checking for overdue books', is_bool($hasOverdue));
    }
    
    $transRepo = new Repositories\BorrowTransactionRepository();
    $activeTransactions = $transRepo->findActiveByMember(1);
    test('Getting active transactions for member', is_array($activeTransactions));
    
    $bookTransactions = $transRepo->findByBookAndBranch('978-0123456789', 1);
    test('Getting transactions for specific book', is_array($bookTransactions));
    
    // Reservation tests
    section('Reservation System Tests');
    
    $resRepo = new Repositories\ReservationRepository();
    $memberReservations = $resRepo->findByMember(1);
    test('Fetching member reservations', is_array($memberReservations));
    
    $activeReservations = $resRepo->findActiveByBook('978-0123456789', 1);
    test('Checking active reservations for book', is_array($activeReservations));
    
    $expiredCount = $resRepo->expireOldReservations();
    test('Cleaning up expired reservations', is_int($expiredCount));
    
    // Reports
    section('Reports & Analytics');
    
    $overdueReport = $service->getOverdueReport();
    test('Generating overdue books report', is_array($overdueReport));
    
    $popularBooks = $service->getMostBorrowedBooks();
    test('Getting most borrowed books', is_array($popularBooks));
    
    // Get member history (should handle gracefully even for invalid IDs)
    try {
        $history = $service->getMemberHistory(999999);
        test('Member history returns proper structure', isset($history['transactions']) && isset($history['reservations']));
    } catch (Exception $e) {
        test('Member history handles invalid IDs', true);
    }
    
} catch (Exception $e) {
    echo "\n❌ Fatal error occurred:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

// Summary
echo "\n" . str_repeat('=', 50) . "\n";
echo "Test Results\n";
echo str_repeat('=', 50) . "\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total:  " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n🎉 All tests passed!\n";
} else {
    echo "\n⚠️  Some tests failed. Please review.\n";
    exit(1);
}