<?php

namespace Services;

use Core\Database;
use Models\Member;
use Models\Book;
use Models\BorrowTransaction;
use Models\Reservation;
use Models\Payment;
use Repositories\MemberRepository;
use Repositories\BookRepository;
use Repositories\BorrowTransactionRepository;
use Repositories\ReservationRepository;
use Repositories\PaymentRepository;
use DateTime;
use Exception;
use PDO;

class LibraryService
{
    private MemberRepository $memberRepo;
    private BookRepository $bookRepo;
    private BorrowTransactionRepository $transactionRepo;
    private ReservationRepository $reservationRepo;
    private PaymentRepository $paymentRepo;
    private PDO $connection;
    
    public function __construct()
    {
        $this->memberRepo = new MemberRepository();
        $this->bookRepo = new BookRepository();
        $this->transactionRepo = new BorrowTransactionRepository();
        $this->reservationRepo = new ReservationRepository();
        $this->paymentRepo = new PaymentRepository();
        $this->connection = Database::getInstance()->getConnection();
    }
    
    /**
     * Borrow a book - orchestrates the complete borrowing workflow
     */
    public function borrowBook(int $memberId, string $isbn, int $branchId): array
    {
        try {
            $this->connection->beginTransaction();
            
            // 1. Validate member
            $member = $this->memberRepo->findById($memberId);
            if (!$member) {
                throw new Exception("Member not found");
            }
            
            if (!$member->isMembershipValid()) {
                throw new Exception("Membership has expired");
            }
            
            // 2. Check current borrowed count
            $currentBorrowedCount = $this->memberRepo->getCurrentBorrowedCount($memberId);
            if (!$member->canBorrow($currentBorrowedCount)) {
                throw new Exception("Maximum borrowing limit reached ({$member->getMaxBooks()} books)");
            }
            
            // 3. Check for overdue books
            if ($this->memberRepo->hasOverdueBooks($memberId)) {
                throw new Exception("Cannot borrow: You have overdue books");
            }
            
            // 4. Check unpaid fees
            $unpaidFees = $this->memberRepo->getTotalUnpaidFees($memberId);
            if ($unpaidFees > 10) {
                throw new Exception("Cannot borrow: Unpaid fees exceed $10 (Current: $" . number_format($unpaidFees, 2) . ")");
            }
            
            // 5. Check book availability at branch
            $book = $this->bookRepo->findByIsbn($isbn);
            if (!$book) {
                throw new Exception("Book not found");
            }
            
            $availableCopies = $this->bookRepo->checkAvailabilityAtBranch($isbn, $branchId);
            if ($availableCopies <= 0) {
                throw new Exception("Book not available at this branch");
            }
            
            // 6. Calculate due date
            $borrowDate = new DateTime();
            $dueDate = (clone $borrowDate)->modify("+{$member->getLoanDays()} days");
            
            // 7. Create borrow transaction
            $transaction = new BorrowTransaction(
                null,
                $memberId,
                $isbn,
                $branchId,
                $borrowDate,
                $dueDate
            );
            
            $transactionId = $this->transactionRepo->save($transaction);
            $transaction->setTransactionId($transactionId);
            
            // 8. Update inventory
            $this->bookRepo->updateBranchInventory($isbn, $branchId, $availableCopies - 1);
            
            // 9. Update book available copies
            $book->decrementCopies();
            $this->bookRepo->update($book);
            
            // 10. Update member's total borrowed books
            $member->incrementTotalBorrowedBooks();
            $this->memberRepo->update($member);
            
            $this->connection->commit();
            
            return [
                'success' => true,
                'message' => 'Book borrowed successfully',
                'transaction_id' => $transactionId,
                'due_date' => $dueDate->format('Y-m-d'),
                'book_title' => $book->getTitle()
            ];
            
        } catch (Exception $e) {
            $this->connection->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Return a book - handles the complete return workflow
     */
    public function returnBook(int $transactionId): array
    {
        try {
            $this->connection->beginTransaction();
            
            // 1. Find transaction
            $transaction = $this->transactionRepo->findById($transactionId);
            if (!$transaction) {
                throw new Exception("Transaction not found");
            }
            
            if ($transaction->getReturnDate() !== null) {
                throw new Exception("Book already returned");
            }
            
            // 2. Get member to calculate late fee
            $member = $this->memberRepo->findById($transaction->getMemberId());
            if (!$member) {
                throw new Exception("Member not found");
            }
            
            // 3. Process return and calculate late fee
            $transaction->processReturn($member->getLateFee());
            $this->transactionRepo->update($transaction);
            
            // 4. Update book inventory
            $book = $this->bookRepo->findByIsbn($transaction->getBookIsbn());
            if ($book) {
                $currentCopies = $this->bookRepo->checkAvailabilityAtBranch(
                    $transaction->getBookIsbn(), 
                    $transaction->getBranchId()
                );
                $this->bookRepo->updateBranchInventory(
                    $transaction->getBookIsbn(), 
                    $transaction->getBranchId(), 
                    $currentCopies + 1
                );
                
                $book->incrementCopies();
                $this->bookRepo->update($book);
            }
            
            // 5. Check for reservations
            $reservations = $this->reservationRepo->findActiveByBook(
                $transaction->getBookIsbn(), 
                $transaction->getBranchId()
            );
            
            $nextReservation = null;
            if (!empty($reservations)) {
                $nextReservation = $reservations[0];
                $nextReservation->notifyMember();
                $this->reservationRepo->update($nextReservation);
            }
            
            $this->connection->commit();
            
            $result = [
                'success' => true,
                'message' => 'Book returned successfully',
                'late_fee' => $transaction->getLateFee()
            ];
            
            if ($transaction->getLateFee() > 0) {
                $result['message'] .= ' - Late fee: $' . number_format($transaction->getLateFee(), 2);
            }
            
            if ($nextReservation) {
                $result['message'] .= ' - Book reserved for next member';
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->connection->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Reserve a book
     */
    public function reserveBook(int $memberId, string $isbn, int $branchId): array
    {
        try {
            $this->connection->beginTransaction();
            
            // 1. Validate member
            $member = $this->memberRepo->findById($memberId);
            if (!$member || !$member->isMembershipValid()) {
                throw new Exception("Invalid or expired membership");
            }
            
            // 2. Check if book exists
            $book = $this->bookRepo->findByIsbn($isbn);
            if (!$book) {
                throw new Exception("Book not found");
            }
            
            // 3. Check if book is available (don't reserve if available)
            $availableCopies = $this->bookRepo->checkAvailabilityAtBranch($isbn, $branchId);
            if ($availableCopies > 0) {
                throw new Exception("Book is currently available - please borrow directly");
            }
            
            // 4. Check if member already has active reservation for this book
            $memberReservations = $this->reservationRepo->findByMember($memberId);
            foreach ($memberReservations as $res) {
                if ($res->getBookIsbn() === $isbn && 
                    $res->getBranchId() === $branchId && 
                    in_array($res->getStatus(), ['PENDING', 'READY'])) {
                    throw new Exception("You already have an active reservation for this book");
                }
            }
            
            // 5. Create reservation
            $reservationDate = new DateTime();
            $expiryDate = (clone $reservationDate)->modify('+7 days'); // Default 7 days for pending
            
            $reservation = new Reservation(
                null,
                $memberId,
                $isbn,
                $branchId,
                $reservationDate,
                $expiryDate,
                'PENDING'
            );
            
            $reservationId = $this->reservationRepo->save($reservation);
            
            $this->connection->commit();
            
            return [
                'success' => true,
                'message' => 'Book reserved successfully',
                'reservation_id' => $reservationId
            ];
            
        } catch (Exception $e) {
            $this->connection->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process payment for late fees
     */
    public function processPayment(int $memberId, float $amount, string $paymentMethod): array
    {
        try {
            $this->connection->beginTransaction();
            
            $member = $this->memberRepo->findById($memberId);
            if (!$member) {
                throw new Exception("Member not found");
            }
            
            $payment = new Payment(
                null,
                $memberId,
                $amount,
                new DateTime(),
                $paymentMethod
            );
            
            if (!$payment->processPayment()) {
                throw new Exception("Payment processing failed");
            }
            
            $paymentId = $this->paymentRepo->save($payment);
            
            $this->connection->commit();
            
            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment_id' => $paymentId,
                'amount' => $amount
            ];
            
        } catch (Exception $e) {
            $this->connection->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get member borrowing history
     */
    public function getMemberHistory(int $memberId): array
    {
        $transactions = $this->transactionRepo->findByMember($memberId);
        $reservations = $this->reservationRepo->findByMember($memberId);
        $payments = $this->paymentRepo->findByMember($memberId);
        
        return [
            'transactions' => $transactions,
            'reservations' => $reservations,
            'payments' => $payments
        ];
    }
    
    /**
     * Search books with various criteria
     */
    public function searchBooks(string $searchTerm, string $searchType = 'title'): array
    {
        switch ($searchType) {
            case 'author':
                return $this->bookRepo->searchByAuthor($searchTerm);
            case 'isbn':
                $book = $this->bookRepo->findByIsbn($searchTerm);
                return $book ? [$book] : [];
            case 'title':
            default:
                return $this->bookRepo->searchByTitle($searchTerm);
        }
    }
    
    /**
     * Get overdue report
     */
    public function getOverdueReport(): array
    {
        return $this->transactionRepo->findOverdueTransactions();
    }
    
    /**
     * Get most borrowed books
     */
    public function getMostBorrowedBooks(int $limit = 10, ?DateTime $startDate = null): array
    {
        return $this->transactionRepo->getMostBorrowedBooks($limit, $startDate);
    }
}