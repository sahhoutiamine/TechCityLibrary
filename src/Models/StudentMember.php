<?php
// models/StudentMember.php

namespace Models;

use DateTime;

class StudentMember extends Member
{
    private const MAX_BOOKS = 3;
    private const LOAN_DAYS = 14;
    private const LATE_FEE = 0.50;
    
    private ?string $studentId;
    
    public function __construct(
        ?int $memberId,
        string $fullName,
        string $email,
        ?string $phoneNumber = null,
        ?DateTime $membershipEndDate = null,
        int $totalBorrowedBooks = 0,
        ?string $studentId = null
    ) {
        parent::__construct(
            $memberId,
            'STUDENT',
            $fullName,
            $email,
            $phoneNumber,
            $membershipEndDate,
            $totalBorrowedBooks
        );
        $this->studentId = $studentId;
    }
    
    public function getStudentId(): ?string
    {
        return $this->studentId;
    }
    
    public function setStudentId(?string $studentId): void
    {
        $this->studentId = $studentId;
    }
    
    public function getMaxBooks(): int
    {
        return self::MAX_BOOKS;
    }
    
    public function getLoanDays(): int
    {
        return self::LOAN_DAYS;
    }
    
    public function getLateFee(): float
    {
        return self::LATE_FEE;
    }
    
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'student_id' => $this->studentId
        ]);
    }
}