<?php
// models/BorrowTransaction.php

namespace Models;

use Interfaces\Entity;
use DateTime;

class BorrowTransaction implements Entity
{
    private ?int $transactionId;
    private int $memberId;
    private string $bookIsbn;
    private int $branchId;
    private DateTime $borrowDate;
    private DateTime $dueDate;
    private ?DateTime $returnDate;
    private float $lateFee;
    
    public function __construct(
        ?int $transactionId,
        int $memberId,
        string $bookIsbn,
        int $branchId,
        DateTime $borrowDate,
        DateTime $dueDate,
        ?DateTime $returnDate = null,
        float $lateFee = 0.0
    ) {
        $this->transactionId = $transactionId;
        $this->memberId = $memberId;
        $this->bookIsbn = $bookIsbn;
        $this->branchId = $branchId;
        $this->borrowDate = $borrowDate;
        $this->dueDate = $dueDate;
        $this->returnDate = $returnDate;
        $this->lateFee = $lateFee;
    }
    
    // Getters
    public function getTransactionId(): ?int
    {
        return $this->transactionId;
    }
    
    public function getMemberId(): int
    {
        return $this->memberId;
    }
    
    public function getBookIsbn(): string
    {
        return $this->bookIsbn;
    }
    
    public function getBranchId(): int
    {
        return $this->branchId;
    }
    
    public function getBorrowDate(): DateTime
    {
        return $this->borrowDate;
    }
    
    public function getDueDate(): DateTime
    {
        return $this->dueDate;
    }
    
    public function getReturnDate(): ?DateTime
    {
        return $this->returnDate;
    }
    
    public function getLateFee(): float
    {
        return $this->lateFee;
    }
    
    // Setters
    public function setTransactionId(int $transactionId): void
    {
        $this->transactionId = $transactionId;
    }
    
    public function setReturnDate(DateTime $returnDate): void
    {
        $this->returnDate = $returnDate;
    }
    
    public function setLateFee(float $lateFee): void
    {
        $this->lateFee = max(0, $lateFee);
    }
    
    // Business methods
    public function calculateDueDate(int $loanDays): DateTime
    {
        $dueDate = clone $this->borrowDate;
        $dueDate->modify("+{$loanDays} days");
        return $dueDate;
    }
    
    public function isOverdue(?DateTime $currentDate = null): bool
    {
        if ($this->returnDate !== null) {
            return false; // Already returned
        }
        
        $checkDate = $currentDate ?? new DateTime();
        return $checkDate > $this->dueDate;
    }
    
    public function calculateLateFee(float $feePerDay, ?DateTime $currentDate = null): float
    {
        if ($this->returnDate !== null) {
            $endDate = $this->returnDate;
        } else {
            $endDate = $currentDate ?? new DateTime();
        }
        
        if ($endDate <= $this->dueDate) {
            return 0.0;
        }
        
        $daysLate = $this->dueDate->diff($endDate)->days;
        return round($daysLate * $feePerDay, 2);
    }
    
    public function processReturn(float $feePerDay): void
    {
        $this->returnDate = new DateTime();
        $this->lateFee = $this->calculateLateFee($feePerDay);
    }
    
    public function validate(): bool
    {
        return $this->memberId > 0 
            && !empty($this->bookIsbn) 
            && $this->branchId > 0
            && $this->borrowDate <= $this->dueDate;
    }
    
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'member_id' => $this->memberId,
            'book_isbn' => $this->bookIsbn,
            'branch_id' => $this->branchId,
            'borrow_date' => $this->borrowDate->format('Y-m-d'),
            'due_date' => $this->dueDate->format('Y-m-d'),
            'return_date' => $this->returnDate?->format('Y-m-d'),
            'late_fee' => $this->lateFee
        ];
    }
}