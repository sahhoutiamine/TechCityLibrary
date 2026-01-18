<?php
// repositories/MemberRepository.php

namespace Repositories;

use Core\Database;
use Models\Member;
use Models\StudentMember;
use Models\FacultyMember;
use PDO;
use PDOException;
use DateTime;

class MemberRepository
{
    private PDO $connection;
    
    public function __construct()
    {
        $this->connection = Database::getInstance()->getConnection();
    }
    
    public function findById(int $memberId): ?Member
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM Member WHERE member_id = :member_id
            ");
            $stmt->execute(['member_id' => $memberId]);
            $data = $stmt->fetch();
            
            if (!$data) {
                return null;
            }
            
            return $this->mapToMember($data);
        } catch (PDOException $e) {
            throw new \Exception("Error finding member: " . $e->getMessage());
        }
    }
    
    public function findByEmail(string $email): ?Member
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM Member WHERE email = :email
            ");
            $stmt->execute(['email' => $email]);
            $data = $stmt->fetch();
            
            if (!$data) {
                return null;
            }
            
            return $this->mapToMember($data);
        } catch (PDOException $e) {
            throw new \Exception("Error finding member by email: " . $e->getMessage());
        }
    }
    
    public function findAll(): array
    {
        try {
            $stmt = $this->connection->query("
                SELECT * FROM Member ORDER BY full_name
            ");
            
            $members = [];
            while ($data = $stmt->fetch()) {
                $members[] = $this->mapToMember($data);
            }
            
            return $members;
        } catch (PDOException $e) {
            throw new \Exception("Error finding members: " . $e->getMessage());
        }
    }
    
    public function save(Member $member): int
    {
        try {
            $this->connection->beginTransaction();
            
            // Insert into Member table
            $stmt = $this->connection->prepare("
                INSERT INTO Member (member_type, full_name, email, phone_number, membership_end_date, total_borrowed_books)
                VALUES (:member_type, :full_name, :email, :phone_number, :membership_end_date, :total_borrowed_books)
            ");
            
            $stmt->execute([
                'member_type' => $member->getMemberType(),
                'full_name' => $member->getFullName(),
                'email' => $member->getEmail(),
                'phone_number' => $member->getPhoneNumber(),
                'membership_end_date' => $member->getMembershipEndDate()?->format('Y-m-d'),
                'total_borrowed_books' => $member->getTotalBorrowedBooks()
            ]);
            
            $memberId = (int)$this->connection->lastInsertId();
            
            // Insert into specific member type table
            if ($member instanceof StudentMember) {
                $stmt = $this->connection->prepare("
                    INSERT INTO Student_Member (member_id, student_id)
                    VALUES (:member_id, :student_id)
                ");
                $stmt->execute([
                    'member_id' => $memberId,
                    'student_id' => $member->getStudentId()
                ]);
            } elseif ($member instanceof FacultyMember) {
                $stmt = $this->connection->prepare("
                    INSERT INTO Faculty_Member (member_id, employee_id, department)
                    VALUES (:member_id, :employee_id, :department)
                ");
                $stmt->execute([
                    'member_id' => $memberId,
                    'employee_id' => $member->getEmployeeId(),
                    'department' => $member->getDepartment()
                ]);
            }
            
            $this->connection->commit();
            return $memberId;
        } catch (PDOException $e) {
            $this->connection->rollBack();
            throw new \Exception("Error saving member: " . $e->getMessage());
        }
    }
    
    public function update(Member $member): bool
    {
        try {
            $this->connection->beginTransaction();
            
            $stmt = $this->connection->prepare("
                UPDATE Member 
                SET full_name = :full_name,
                    email = :email,
                    phone_number = :phone_number,
                    membership_end_date = :membership_end_date,
                    total_borrowed_books = :total_borrowed_books
                WHERE member_id = :member_id
            ");
            
            $stmt->execute([
                'member_id' => $member->getMemberId(),
                'full_name' => $member->getFullName(),
                'email' => $member->getEmail(),
                'phone_number' => $member->getPhoneNumber(),
                'membership_end_date' => $member->getMembershipEndDate()?->format('Y-m-d'),
                'total_borrowed_books' => $member->getTotalBorrowedBooks()
            ]);
            
            // Update specific member type table
            if ($member instanceof StudentMember) {
                $stmt = $this->connection->prepare("
                    UPDATE Student_Member 
                    SET student_id = :student_id
                    WHERE member_id = :member_id
                ");
                $stmt->execute([
                    'member_id' => $member->getMemberId(),
                    'student_id' => $member->getStudentId()
                ]);
            } elseif ($member instanceof FacultyMember) {
                $stmt = $this->connection->prepare("
                    UPDATE Faculty_Member 
                    SET employee_id = :employee_id, department = :department
                    WHERE member_id = :member_id
                ");
                $stmt->execute([
                    'member_id' => $member->getMemberId(),
                    'employee_id' => $member->getEmployeeId(),
                    'department' => $member->getDepartment()
                ]);
            }
            
            $this->connection->commit();
            return true;
        } catch (PDOException $e) {
            $this->connection->rollBack();
            throw new \Exception("Error updating member: " . $e->getMessage());
        }
    }
    
    public function getCurrentBorrowedCount(int $memberId): int
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT COUNT(*) as count
                FROM Borrow_Transaction
                WHERE member_id = :member_id AND return_date IS NULL
            ");
            $stmt->execute(['member_id' => $memberId]);
            $result = $stmt->fetch();
            
            return (int)$result['count'];
        } catch (PDOException $e) {
            throw new \Exception("Error getting borrowed count: " . $e->getMessage());
        }
    }
    
    public function getTotalUnpaidFees(int $memberId): float
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT COALESCE(SUM(late_fee), 0) as total_fees
                FROM Borrow_Transaction
                WHERE member_id = :member_id AND return_date IS NOT NULL AND late_fee > 0
            ");
            $stmt->execute(['member_id' => $memberId]);
            $result = $stmt->fetch();
            
            // Get total paid
            $stmt = $this->connection->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_paid
                FROM Payment
                WHERE member_id = :member_id
            ");
            $stmt->execute(['member_id' => $memberId]);
            $paid = $stmt->fetch();
            
            return max(0, (float)$result['total_fees'] - (float)$paid['total_paid']);
        } catch (PDOException $e) {
            throw new \Exception("Error calculating unpaid fees: " . $e->getMessage());
        }
    }
    
    public function hasOverdueBooks(int $memberId): bool
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT COUNT(*) as count
                FROM Borrow_Transaction
                WHERE member_id = :member_id 
                AND return_date IS NULL 
                AND due_date < CURDATE()
            ");
            $stmt->execute(['member_id' => $memberId]);
            $result = $stmt->fetch();
            
            return (int)$result['count'] > 0;
        } catch (PDOException $e) {
            throw new \Exception("Error checking overdue books: " . $e->getMessage());
        }
    }
    
    private function mapToMember(array $data): Member
    {
        $memberType = $data['member_type'];
        $memberId = (int)$data['member_id'];
        
        $membershipEndDate = $data['membership_end_date'] 
            ? new DateTime($data['membership_end_date']) 
            : null;
        
        if ($memberType === 'STUDENT') {
            // Get student-specific data
            $stmt = $this->connection->prepare("
                SELECT * FROM Student_Member WHERE member_id = :member_id
            ");
            $stmt->execute(['member_id' => $memberId]);
            $studentData = $stmt->fetch();
            
            return new StudentMember(
                $memberId,
                $data['full_name'],
                $data['email'],
                $data['phone_number'],
                $membershipEndDate,
                (int)$data['total_borrowed_books'],
                $studentData['student_id'] ?? null
            );
        } elseif ($memberType === 'FACULTY') {
            // Get faculty-specific data
            $stmt = $this->connection->prepare("
                SELECT * FROM Faculty_Member WHERE member_id = :member_id
            ");
            $stmt->execute(['member_id' => $memberId]);
            $facultyData = $stmt->fetch();
            
            return new FacultyMember(
                $memberId,
                $data['full_name'],
                $data['email'],
                $data['phone_number'],
                $membershipEndDate,
                (int)$data['total_borrowed_books'],
                $facultyData['employee_id'] ?? null,
                $facultyData['department'] ?? null
            );
        }
        
        throw new \Exception("Unknown member type: {$memberType}");
    }
}