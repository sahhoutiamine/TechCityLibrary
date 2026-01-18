<?php

namespace Models;

use Interfaces\Entity;
use DateTime;

class Reservation implements Entity
{
    private ?int $reservationId;
    private int $memberId;
    private string $bookIsbn;
    private int $branchId;
    private DateTime $reservationDate;
    private DateTime $expiryDate;
    private string $status; 
    public function __construct(
        ?int $reservationId,
        int $memberId,
        string $bookIsbn,
        int $branchId,
        DateTime $reservationDate,
        DateTime $expiryDate,
        string $status = 'PENDING'
    ) {
        $this->reservationId = $reservationId;
        $this->memberId = $memberId;
        $this->bookIsbn = $bookIsbn;
        $this->branchId = $branchId;
        $this->reservationDate = $reservationDate;
        $this->expiryDate = $expiryDate;
        $this->status = $status;
    }
    
    // Getters
    public function getReservationId(): ?int
    {
        return $this->reservationId;
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
    
    public function getReservationDate(): DateTime
    {
        return $this->reservationDate;
    }
    
    public function getExpiryDate(): DateTime
    {
        return $this->expiryDate;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    // Setters
    public function setReservationId(int $reservationId): void
    {
        $this->reservationId = $reservationId;
    }
    
    public function setStatus(string $status): void
    {
        $validStatuses = ['PENDING', 'READY', 'EXPIRED', 'FULFILLED'];
        if (in_array($status, $validStatuses)) {
            $this->status = $status;
        }
    }
    
    // Business methods
    public function notifyMember(): void
    {
        // Set status to READY when book becomes available
        $this->status = 'READY';
        
        // Set expiry date to 48 hours from now
        $this->expiryDate = (new DateTime())->modify('+2 days');
    }
    
    public function cancelReservation(): void
    {
        $this->status = 'EXPIRED';
    }
    
    public function isExpired(?DateTime $currentDate = null): bool
    {
        $checkDate = $currentDate ?? new DateTime();
        return $checkDate > $this->expiryDate && $this->status !== 'FULFILLED';
    }
    
    public function validate(): bool
    {
        return $this->memberId > 0 
            && !empty($this->bookIsbn) 
            && $this->branchId > 0;
    }
    
    public function toArray(): array
    {
        return [
            'reservation_id' => $this->reservationId,
            'member_id' => $this->memberId,
            'book_isbn' => $this->bookIsbn,
            'branch_id' => $this->branchId,
            'reservation_date' => $this->reservationDate->format('Y-m-d'),
            'expiry_date' => $this->expiryDate->format('Y-m-d'),
            'status' => $this->status
        ];
    }
}