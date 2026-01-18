<?php

namespace Models;

use Interfaces\Entity;

class Book implements Entity
{
    private string $isbn;
    private string $title;
    private int $publicationYear;
    private int $availableCopies;
    private string $status;
    private ?int $categoryId;
    private ?Category $category = null;
    private array $authors = [];
    
    public function __construct(
        string $isbn,
        string $title,
        int $publicationYear,
        int $availableCopies = 0,
        string $status = 'Available',
        ?int $categoryId = null
    ) {
        $this->isbn = $isbn;
        $this->title = $title;
        $this->publicationYear = $publicationYear;
        $this->availableCopies = $availableCopies;
        $this->status = $status;
        $this->categoryId = $categoryId;
    }
    
    // Getters
    public function getIsbn(): string
    {
        return $this->isbn;
    }
    
    public function getTitle(): string
    {
        return $this->title;
    }
    
    public function getPublicationYear(): int
    {
        return $this->publicationYear;
    }
    
    public function getAvailableCopies(): int
    {
        return $this->availableCopies;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }
    
    public function getCategory(): ?Category
    {
        return $this->category;
    }
    
    public function getAuthors(): array
    {
        return $this->authors;
    }
    
    // Setters
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
    
    public function setAvailableCopies(int $copies): void
    {
        $this->availableCopies = max(0, $copies);
    }
    
    public function setStatus(string $status): void
    {
        $validStatuses = ['Available', 'Checked Out', 'Reserved', 'Under Maintenance'];
        if (in_array($status, $validStatuses)) {
            $this->status = $status;
        }
    }
    
    public function setCategory(?Category $category): void
    {
        $this->category = $category;
        if ($category) {
            $this->categoryId = $category->getCategoryId();
        }
    }
    
    public function setAuthors(array $authors): void
    {
        $this->authors = $authors;
    }
    
    public function addAuthor(Author $author): void
    {
        $this->authors[] = $author;
    }
    
    // Business methods
    public function isAvailable(): bool
    {
        return $this->availableCopies > 0 && $this->status === 'Available';
    }
    
    public function decrementCopies(): void
    {
        if ($this->availableCopies > 0) {
            $this->availableCopies--;
            if ($this->availableCopies === 0) {
                $this->status = 'Checked Out';
            }
        }
    }
    
    public function incrementCopies(): void
    {
        $this->availableCopies++;
        if ($this->availableCopies > 0 && $this->status === 'Checked Out') {
            $this->status = 'Available';
        }
    }
    
    public function validate(): bool
    {
        return !empty($this->isbn) 
            && !empty($this->title) 
            && $this->publicationYear > 0 
            && $this->availableCopies >= 0;
    }
    
    public function toArray(): array
    {
        return [
            'isbn' => $this->isbn,
            'title' => $this->title,
            'publication_year' => $this->publicationYear,
            'available_copies' => $this->availableCopies,
            'status' => $this->status,
            'category_id' => $this->categoryId
        ];
    }
}