<?php

namespace Repositories;

use Core\Database;
use Models\Book;
use Models\Author;
use Models\Category;
use PDO;
use PDOException;

class BookRepository
{
    private PDO $connection;
    
    public function __construct()
    {
        $this->connection = Database::getInstance()->getConnection();
    }
    
    public function findByIsbn(string $isbn): ?Book
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT b.*, c.name as category_name
                FROM Book b
                LEFT JOIN Category c ON b.category_id = c.category_id
                WHERE b.isbn = :isbn
            ");
            $stmt->execute(['isbn' => $isbn]);
            $data = $stmt->fetch();
            
            if (!$data) {
                return null;
            }
            
            $book = $this->mapToBook($data);
            
            // Load authors
            $book->setAuthors($this->findAuthorsByIsbn($isbn));
            
            return $book;
        } catch (PDOException $e) {
            throw new \Exception("Error finding book: " . $e->getMessage());
        }
    }
    
    public function findAll(): array
    {
        try {
            $stmt = $this->connection->query("
                SELECT b.*, c.name as category_name
                FROM Book b
                LEFT JOIN Category c ON b.category_id = c.category_id
                ORDER BY b.title
            ");
            
            $books = [];
            while ($data = $stmt->fetch()) {
                $book = $this->mapToBook($data);
                $book->setAuthors($this->findAuthorsByIsbn($book->getIsbn()));
                $books[] = $book;
            }
            
            return $books;
        } catch (PDOException $e) {
            throw new \Exception("Error finding books: " . $e->getMessage());
        }
    }
    
    public function searchByTitle(string $title): array
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT b.*, c.name as category_name
                FROM Book b
                LEFT JOIN Category c ON b.category_id = c.category_id
                WHERE b.title LIKE :title
                ORDER BY b.title
            ");
            $stmt->execute(['title' => "%{$title}%"]);
            
            $books = [];
            while ($data = $stmt->fetch()) {
                $book = $this->mapToBook($data);
                $book->setAuthors($this->findAuthorsByIsbn($book->getIsbn()));
                $books[] = $book;
            }
            
            return $books;
        } catch (PDOException $e) {
            throw new \Exception("Error searching books: " . $e->getMessage());
        }
    }
    
    public function searchByAuthor(string $authorName): array
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT DISTINCT b.*, c.name as category_name
                FROM Book b
                LEFT JOIN Category c ON b.category_id = c.category_id
                INNER JOIN Book_Author ba ON b.isbn = ba.book_isbn
                INNER JOIN Author a ON ba.author_id = a.author_id
                WHERE a.name LIKE :author
                ORDER BY b.title
            ");
            $stmt->execute(['author' => "%{$authorName}%"]);
            
            $books = [];
            while ($data = $stmt->fetch()) {
                $book = $this->mapToBook($data);
                $book->setAuthors($this->findAuthorsByIsbn($book->getIsbn()));
                $books[] = $book;
            }
            
            return $books;
        } catch (PDOException $e) {
            throw new \Exception("Error searching by author: " . $e->getMessage());
        }
    }
    
    public function searchByCategory(int $categoryId): array
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT b.*, c.name as category_name
                FROM Book b
                LEFT JOIN Category c ON b.category_id = c.category_id
                WHERE b.category_id = :category_id
                ORDER BY b.title
            ");
            $stmt->execute(['category_id' => $categoryId]);
            
            $books = [];
            while ($data = $stmt->fetch()) {
                $book = $this->mapToBook($data);
                $book->setAuthors($this->findAuthorsByIsbn($book->getIsbn()));
                $books[] = $book;
            }
            
            return $books;
        } catch (PDOException $e) {
            throw new \Exception("Error searching by category: " . $e->getMessage());
        }
    }
    
    public function save(Book $book): bool
    {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO Book (isbn, title, publication_year, available_copies, status, category_id)
                VALUES (:isbn, :title, :publication_year, :available_copies, :status, :category_id)
            ");
            
            return $stmt->execute([
                'isbn' => $book->getIsbn(),
                'title' => $book->getTitle(),
                'publication_year' => $book->getPublicationYear(),
                'available_copies' => $book->getAvailableCopies(),
                'status' => $book->getStatus(),
                'category_id' => $book->getCategoryId()
            ]);
        } catch (PDOException $e) {
            throw new \Exception("Error saving book: " . $e->getMessage());
        }
    }
    
    public function update(Book $book): bool
    {
        try {
            $stmt = $this->connection->prepare("
                UPDATE Book 
                SET title = :title, 
                    publication_year = :publication_year,
                    available_copies = :available_copies,
                    status = :status,
                    category_id = :category_id
                WHERE isbn = :isbn
            ");
            
            return $stmt->execute([
                'isbn' => $book->getIsbn(),
                'title' => $book->getTitle(),
                'publication_year' => $book->getPublicationYear(),
                'available_copies' => $book->getAvailableCopies(),
                'status' => $book->getStatus(),
                'category_id' => $book->getCategoryId()
            ]);
        } catch (PDOException $e) {
            throw new \Exception("Error updating book: " . $e->getMessage());
        }
    }
    
    public function checkAvailabilityAtBranch(string $isbn, int $branchId): int
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT copies 
                FROM Branch_Inventory 
                WHERE book_isbn = :isbn AND branch_id = :branch_id
            ");
            $stmt->execute([
                'isbn' => $isbn,
                'branch_id' => $branchId
            ]);
            
            $result = $stmt->fetch();
            return $result ? (int)$result['copies'] : 0;
        } catch (PDOException $e) {
            throw new \Exception("Error checking availability: " . $e->getMessage());
        }
    }
    
    public function updateBranchInventory(string $isbn, int $branchId, int $copies): bool
    {
        try {
            $stmt = $this->connection->prepare("
                UPDATE Branch_Inventory 
                SET copies = :copies 
                WHERE book_isbn = :isbn AND branch_id = :branch_id
            ");
            
            return $stmt->execute([
                'copies' => $copies,
                'isbn' => $isbn,
                'branch_id' => $branchId
            ]);
        } catch (PDOException $e) {
            throw new \Exception("Error updating inventory: " . $e->getMessage());
        }
    }
    
    private function findAuthorsByIsbn(string $isbn): array
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT a.*
                FROM Author a
                INNER JOIN Book_Author ba ON a.author_id = ba.author_id
                WHERE ba.book_isbn = :isbn
            ");
            $stmt->execute(['isbn' => $isbn]);
            
            $authors = [];
            while ($data = $stmt->fetch()) {
                $authors[] = new Author(
                    $data['author_id'],
                    $data['name'],
                    $data['biography'],
                    $data['nationality'],
                    $data['birth_date'] ? new \DateTime($data['birth_date']) : null,
                    $data['primary_genre']
                );
            }
            
            return $authors;
        } catch (PDOException $e) {
            throw new \Exception("Error finding authors: " . $e->getMessage());
        }
    }
    
    private function mapToBook(array $data): Book
    {
        $book = new Book(
            $data['isbn'],
            $data['title'],
            (int)$data['publication_year'],
            (int)$data['available_copies'],
            $data['status'],
            $data['category_id'] ? (int)$data['category_id'] : null
        );
        
        if (isset($data['category_name'])) {
            $category = new Category($data['category_id'], $data['category_name']);
            $book->setCategory($category);
        }
        
        return $book;
    }
}