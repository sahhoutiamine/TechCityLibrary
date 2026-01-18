<?php
// repositories/ReservationRepository.php

namespace Repositories;

use Config\Database;
use Models\Reservation;
use PDO;
use PDOException;
use DateTime;

class ReservationRepository
{
    private PDO $connection;
    
    public function __construct()
    {
        $this->connection = Database::getInstance()->getConnection();
    }
    
    public function findById(int $reservationId): ?Reservation
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM Reservation WHERE reservation_id = :reservation_id
            ");
            $stmt->execute(['reservation_id' => $reservationId]);
            $data = $stmt->fetch();
            
            if (!$data) {
                return null;
            }
            
            return $this->mapToReservation($data);
        } catch (PDOException $e) {
            throw new \Exception("Error finding reservation: " . $e->getMessage());
        }
    }
    
    public function findByMember(int $memberId): array
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM Reservation 
                WHERE member_id = :member_id 
                ORDER BY reservation_date DESC
            ");
            $stmt->execute(['member_id' => $memberId]);
            
            $reservations = [];
            while ($data = $stmt->fetch()) {
                $reservations[] = $this->mapToReservation($data);
            }
            
            return $reservations;
        } catch (PDOException $e) {
            throw new \Exception("Error finding member reservations: " . $e->getMessage());
        }
    }
    
    public function findActiveByBook(string $isbn, int $branchId): array
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM Reservation 
                WHERE book_isbn = :isbn 
                AND branch_id = :branch_id 
                AND status IN ('PENDING', 'READY')
                ORDER BY reservation_date ASC
            ");
            $stmt->execute([
                'isbn' => $isbn,
                'branch_id' => $branchId
            ]);
            
            $reservations = [];
            while ($data = $stmt->fetch()) {
                $reservations[] = $this->mapToReservation($data);
            }
            
            return $reservations;
        } catch (PDOException $e) {
            throw new \Exception("Error finding book reservations: " . $e->getMessage());
        }
    }
    
    public function save(Reservation $reservation): int
    {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO Reservation 
                (member_id, book_isbn, branch_id, reservation_date, expiry_date, status)
                VALUES (:member_id, :book_isbn, :branch_id, :reservation_date, :expiry_date, :status)
            ");
            
            $stmt->execute([
                'member_id' => $reservation->getMemberId(),
                'book_isbn' => $reservation->getBookIsbn(),
                'branch_id' => $reservation->getBranchId(),
                'reservation_date' => $reservation->getReservationDate()->format('Y-m-d'),
                'expiry_date' => $reservation->getExpiryDate()->format('Y-m-d'),
                'status' => $reservation->getStatus()
            ]);
            
            return (int)$this->connection->lastInsertId();
        } catch (PDOException $e) {
            throw new \Exception("Error saving reservation: " . $e->getMessage());
        }
    }
    
    public function update(Reservation $reservation): bool
    {
        try {
            $stmt = $this->connection->prepare("
                UPDATE Reservation 
                SET status = :status, expiry_date = :expiry_date
                WHERE reservation_id = :reservation_id
            ");
            
            return $stmt->execute([
                'reservation_id' => $reservation->getReservationId(),
                'status' => $reservation->getStatus(),
                'expiry_date' => $reservation->getExpiryDate()->format('Y-m-d')
            ]);
        } catch (PDOException $e) {
            throw new \Exception("Error updating reservation: " . $e->getMessage());
        }
    }
    
    public function expireOldReservations(): int
    {
        try {
            $stmt = $this->connection->prepare("
                UPDATE Reservation 
                SET status = 'EXPIRED'
                WHERE status IN ('PENDING', 'READY') 
                AND expiry_date < CURDATE()
            ");
            $stmt->execute();
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new \Exception("Error expiring reservations: " . $e->getMessage());
        }
    }
    
    private function mapToReservation(array $data): Reservation
    {
        return new Reservation(
            (int)$data['reservation_id'],
            (int)$data['member_id'],
            $data['book_isbn'],
            (int)$data['branch_id'],
            new DateTime($data['reservation_date']),
            new DateTime($data['expiry_date']),
            $data['status']
        );
    }
}