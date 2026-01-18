<?php
// repositories/PaymentRepository.php

namespace Repositories;

use Core\Database;
use Models\Payment;
use PDO;
use PDOException;
use DateTime;

class PaymentRepository
{
    private PDO $connection;
    
    public function __construct()
    {
        $this->connection = Database::getInstance()->getConnection();
    }
    
    public function findById(int $paymentId): ?Payment
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM Payment WHERE payment_id = :payment_id
            ");
            $stmt->execute(['payment_id' => $paymentId]);
            $data = $stmt->fetch();
            
            if (!$data) {
                return null;
            }
            
            return $this->mapToPayment($data);
        } catch (PDOException $e) {
            throw new \Exception("Error finding payment: " . $e->getMessage());
        }
    }
    
    public function findByMember(int $memberId): array
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM Payment 
                WHERE member_id = :member_id 
                ORDER BY payment_date DESC
            ");
            $stmt->execute(['member_id' => $memberId]);
            
            $payments = [];
            while ($data = $stmt->fetch()) {
                $payments[] = $this->mapToPayment($data);
            }
            
            return $payments;
        } catch (PDOException $e) {
            throw new \Exception("Error finding member payments: " . $e->getMessage());
        }
    }
    
    public function save(Payment $payment): int
    {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO Payment (member_id, amount, payment_date, payment_method)
                VALUES (:member_id, :amount, :payment_date, :payment_method)
            ");
            
            $stmt->execute([
                'member_id' => $payment->getMemberId(),
                'amount' => $payment->getAmount(),
                'payment_date' => $payment->getPaymentDate()->format('Y-m-d'),
                'payment_method' => $payment->getPaymentMethod()
            ]);
            
            return (int)$this->connection->lastInsertId();
        } catch (PDOException $e) {
            throw new \Exception("Error saving payment: " . $e->getMessage());
        }
    }
    
    private function mapToPayment(array $data): Payment
    {
        return new Payment(
            (int)$data['payment_id'],
            (int)$data['member_id'],
            (float)$data['amount'],
            new DateTime($data['payment_date']),
            $data['payment_method']
        );
    }
}