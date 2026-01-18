<?php

namespace Models;

use Interfaces\Entity;
use DateTime;

class Payment implements Entity
{
    private ?int $paymentId;
    private int $memberId;
    private float $amount;
    private DateTime $paymentDate;
    private string $paymentMethod;
    
    public function __construct(
        ?int $paymentId,
        int $memberId,
        float $amount,
        DateTime $paymentDate,
        string $paymentMethod
    ) {
        $this->paymentId = $paymentId;
        $this->memberId = $memberId;
        $this->amount = $amount;
        $this->paymentDate = $paymentDate;
        $this->paymentMethod = $paymentMethod;
    }
    
    // Getters
    public function getPaymentId(): ?int
    {
        return $this->paymentId;
    }
    
    public function getMemberId(): int
    {
        return $this->memberId;
    }
    
    public function getAmount(): float
    {
        return $this->amount;
    }
    
    public function getPaymentDate(): DateTime
    {
        return $this->paymentDate;
    }
    
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }
    
    // Setters
    public function setPaymentId(int $paymentId): void
    {
        $this->paymentId = $paymentId;
    }
    
    // Business methods
    public function processPayment(): bool
    {
        // In a real system, this would integrate with payment gateway
        return $this->amount > 0;
    }
    
    public function validate(): bool
    {
        return $this->memberId > 0 
            && $this->amount > 0 
            && !empty($this->paymentMethod);
    }
    
    public function toArray(): array
    {
        return [
            'payment_id' => $this->paymentId,
            'member_id' => $this->memberId,
            'amount' => $this->amount,
            'payment_date' => $this->paymentDate->format('Y-m-d'),
            'payment_method' => $this->paymentMethod
        ];
    }
}