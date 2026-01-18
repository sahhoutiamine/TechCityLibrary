<?php
// repositories/BorrowTransactionRepository.php

namespace Repositories;

use Config\Database;
use Models\BorrowTransaction;
use PDO;
use PDOException;
use DateTime;

class BorrowTransactionRepository
{
    private PDO $connection;
    
    public function __construct()
    {
        $this->connection = Database::getInstance()->getConnection();
    }
    
    public function findById(int $transactionId): ?BorrowTransaction
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM Borrow_Transaction WHERE transaction_id = :transaction_id
            ");
            $stmt->execute(['transaction_id' => $transactionId]);
            $data = $stmt->fetch();
            
            if (!$data) {
                return null;
            }
            
            return $this->mapToTransaction($data);
        } catch (PDOException $e) {
            throw new \Exception("Error finding transaction: " . $e->getMessage());
        }
    }
    
    public function findByMember(int $memberId): array
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM Borrow_Transaction 
                WHERE member_id = :member_id 
                ORDER BY borrow_date DESC
            ");
            $stmt->execute(['member_id' => $memberId]);
            
            $transactions = [];
            while ($data = $stmt->fetch()) {
                $transactions[] = $this->mapToTransaction($data);
            }
            
            return $transactions;
        } catch (PDOException $e) {
            throw new \Exception("Error finding member transactions: " . $e->getMessage());
        }
    }
    
    public function findActiveByMember(int $memberId): array
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM Borrow_Transaction 
                WHERE member_id = :member_id AND return_date IS NULL
                ORDER BY borrow_date DESC
            ");
            $stmt->execute(['member_id' => $memberId]);
            
            $transactions = [];
            while ($data = $stmt->fetch()) {
                $transactions[] = $this->mapToTransaction($data);
            }
            
            return $transactions;
        } catch (PDOException $e) {
            throw new \Exception("Error finding active transactions: " . $e->getMessage());
        }
    }
    
    public function findOverdueTransactions(): array
    {
        try {
            $stmt = $this->connection->query("
                SELECT bt.*, m.full_name, m.email, b.title
                FROM Borrow_Transaction bt
                INNER JOIN Member m ON bt.member_id = m.member_id
                INNER JOIN Book b ON bt.book_isbn = b.isbn
                WHERE bt.return_date IS NULL AND bt.due_date < CURDATE()
                ORDER BY bt.due_date ASC
            ");
            
            $transactions = [];
            while ($data = $stmt->fetch()) {
                $transaction = $this->mapToTransaction($data);
                $transactions[] = [
                    'transaction' => $transaction,
                    'member_name' => $data['full_name'],
                    'member_email' => $data['email'],
                    'book_title' => $data['title']
                ];
            }
            
            return $transactions;
        } catch (PDOException $e) {
            throw new \Exception("Error finding overdue transactions: " . $e->getMessage());
        }
    }
    
    public function findByBookAndBranch(string $isbn, int $branchId): array
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM Borrow_Transaction 
                WHERE book_isbn = :isbn AND branch_id = :branch_id AND return_date IS NULL
                ORDER BY borrow_date DESC
            ");
            $stmt->execute([
                'isbn' => $isbn,
                'branch_id' => $branchId
            ]);
            
            $transactions = [];
            while ($data = $stmt->fetch()) {
                $transactions[] = $this->mapToTransaction($data);
            }
            
            return $transactions;
        } catch (PDOException $e) {
            throw new \Exception("Error finding book transactions: " . $e->getMessage());
        }
    }
    
    public function save(BorrowTransaction $transaction): int
    {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO Borrow_Transaction 
                (member_id, book_isbn, branch_id, borrow_date, due_date, return_date, late_fee)
                VALUES (:member_id, :book_isbn, :branch_id, :borrow_date, :due_date, :return_date, :late_fee)
            ");
            
            $stmt->execute([
                'member_id' => $transaction->getMemberId(),
                'book_isbn' => $transaction->getBookIsbn(),
                'branch_id' => $transaction->getBranchId(),
                'borrow_date' => $transaction->getBorrowDate()->format('Y-m-d'),
                'due_date' => $transaction->getDueDate()->format('Y-m-d'),
                'return_date' => $transaction->getReturnDate()?->format('Y-m-d'),
                'late_fee' => $transaction->getLateFee()
            ]);
            
            return (int)$this->connection->lastInsertId();
        } catch (PDOException $e) {
            throw new \Exception("Error saving transaction: " . $e->getMessage());
        }
    }
    
    public function update(BorrowTransaction $transaction): bool
    {
        try {
            $stmt = $this->connection->prepare("
                UPDATE Borrow_Transaction 
                SET return_date = :return_date, late_fee = :late_fee
                WHERE transaction_id = :transaction_id
            ");
            
            return $stmt->execute([
                'transaction_id' => $transaction->getTransactionId(),
                'return_date' => $transaction->getReturnDate()?->format('Y-m-d'),
                'late_fee' => $transaction->getLateFee()
            ]);
        } catch (PDOException $e) {
            throw new \Exception("Error updating transaction: " . $e->getMessage());
        }
    }
    
    public function getMostBorrowedBooks(int $limit = 10, ?DateTime $startDate = null): array
    {
        try {
            $query = "
                SELECT b.isbn, b.title, COUNT(*) as borrow_count
                FROM Borrow_Transaction bt
                INNER JOIN Book b ON bt.book_isbn = b.isbn
            ";
            
            $params = [];
            if ($startDate) {
                $query .= " WHERE bt.borrow_date >= :start_date";
                $params['start_date'] = $startDate->format('Y-m-d');
            }
            
            $query .= "
                GROUP BY b.isbn, b.title
                ORDER BY borrow_count DESC
                LIMIT :limit
            ";
            
            $stmt = $this->connection->prepare($query);
            
            if ($startDate) {
                $stmt->bindValue(':start_date', $params['start_date']);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new \Exception("Error getting most borrowed books: " . $e->getMessage());
        }
    }
    
    private function mapToTransaction(array $data): BorrowTransaction
    {
        return new BorrowTransaction(
            (int)$data['transaction_id'],
            (int)$data['member_id'],
            $data['book_isbn'],
            (int)$data['branch_id'],
            new DateTime($data['borrow_date']),
            new DateTime($data['due_date']),
            $data['return_date'] ? new DateTime($data['return_date']) : null,
            (float)$data['late_fee']
        );
    }
}