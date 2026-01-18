<?php

namespace Models;

use DateTime;

class FacultyMember extends Member
{
    private const MAX_BOOKS = 10;
    private const LOAN_DAYS = 30;
    private const LATE_FEE = 0.25;
    
    private ?string $employeeId;
    private ?string $department;
    
    public function __construct(
        ?int $memberId,
        string $fullName,
        string $email,
        ?string $phoneNumber = null,
        ?DateTime $membershipEndDate = null,
        int $totalBorrowedBooks = 0,
        ?string $employeeId = null,
        ?string $department = null
    ) {
        parent::__construct(
            $memberId,
            'FACULTY',
            $fullName,
            $email,
            $phoneNumber,
            $membershipEndDate,
            $totalBorrowedBooks
        );
        $this->employeeId = $employeeId;
        $this->department = $department;
    }
    
    public function getEmployeeId(): ?string
    {
        return $this->employeeId;
    }
    
    public function getDepartment(): ?string
    {
        return $this->department;
    }
    
    public function setEmployeeId(?string $employeeId): void
    {
        $this->employeeId = $employeeId;
    }
    
    public function setDepartment(?string $department): void
    {
        $this->department = $department;
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
            'employee_id' => $this->employeeId,
            'department' => $this->department
        ]);
    }
}