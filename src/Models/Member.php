<?php
namespace Models;

use Interfaces\Entity;
use DateTime;

abstract class Member implements Entity
{
    protected ?int $memberId;
    protected string $memberType;
    protected string $fullName;
    protected string $email;
    protected ?string $phoneNumber;
    protected ?DateTime $membershipEndDate;
    protected int $totalBorrowedBooks;
    
    public function __construct(
        ?int $memberId,
        string $memberType,
        string $fullName,
        string $email,
        ?string $phoneNumber = null,
        ?DateTime $membershipEndDate = null,
        int $totalBorrowedBooks = 0
    ) {
        $this->memberId = $memberId;
        $this->memberType = $memberType;
        $this->fullName = $fullName;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
        $this->membershipEndDate = $membershipEndDate;
        $this->totalBorrowedBooks = $totalBorrowedBooks;
    }
    
    // Abstract methods - must be implemented by child classes
    abstract public function getMaxBooks(): int;
    abstract public function getLoanDays(): int;
    abstract public function getLateFee(): float;
    
    // Getters
    public function getMemberId(): ?int
    {
        return $this->memberId;
    }
    
    public function getMemberType(): string
    {
        return $this->memberType;
    }
    
    public function getFullName(): string
    {
        return $this->fullName;
    }
    
    public function getEmail(): string
    {
        return $this->email;
    }
    
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }
    
    public function getMembershipEndDate(): ?DateTime
    {
        return $this->membershipEndDate;
    }
    
    public function getTotalBorrowedBooks(): int
    {
        return $this->totalBorrowedBooks;
    }
    
    // Setters
    public function setMemberId(int $memberId): void
    {
        $this->memberId = $memberId;
    }
    
    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }
    
    public function setEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->email = $email;
        }
    }
    
    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }
    
    public function setMembershipEndDate(?DateTime $membershipEndDate): void
    {
        $this->membershipEndDate = $membershipEndDate;
    }
    
    public function incrementTotalBorrowedBooks(): void
    {
        $this->totalBorrowedBooks++;
    }
    
    // Business logic methods
    public function isMembershipValid(): bool
    {
        if ($this->membershipEndDate === null) {
            return false;
        }
        return $this->membershipEndDate >= new DateTime();
    }
    
    public function renewMembership(DateTime $newEndDate): void
    {
        $this->membershipEndDate = $newEndDate;
    }
    
    public function canBorrow(int $currentBorrowedCount): bool
    {
        return $this->isMembershipValid() 
            && $currentBorrowedCount < $this->getMaxBooks();
    }
    
    public function validate(): bool
    {
        return !empty($this->fullName) 
            && !empty($this->email) 
            && filter_var($this->email, FILTER_VALIDATE_EMAIL);
    }
    
    public function toArray(): array
    {
        return [
            'member_id' => $this->memberId,
            'member_type' => $this->memberType,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone_number' => $this->phoneNumber,
            'membership_end_date' => $this->membershipEndDate?->format('Y-m-d'),
            'total_borrowed_books' => $this->totalBorrowedBooks
        ];
    }
}