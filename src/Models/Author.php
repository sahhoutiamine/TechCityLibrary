<?php

namespace Models;

use Interfaces\Entity;
use DateTime;

class Author implements Entity
{
    private ?int $authorId;
    private string $name;
    private ?string $biography;
    private ?string $nationality;
    private ?DateTime $birthDate;
    private ?string $primaryGenre;
    private array $books = [];
    
    public function __construct(
        ?int $authorId,
        string $name,
        ?string $biography = null,
        ?string $nationality = null,
        ?DateTime $birthDate = null,
        ?string $primaryGenre = null
    ) {
        $this->authorId = $authorId;
        $this->name = $name;
        $this->biography = $biography;
        $this->nationality = $nationality;
        $this->birthDate = $birthDate;
        $this->primaryGenre = $primaryGenre;
    }
    
    // Getters
    public function getAuthorId(): ?int
    {
        return $this->authorId;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getBiography(): ?string
    {
        return $this->biography;
    }
    
    public function getNationality(): ?string
    {
        return $this->nationality;
    }
    
    public function getBirthDate(): ?DateTime
    {
        return $this->birthDate;
    }
    
    public function getPrimaryGenre(): ?string
    {
        return $this->primaryGenre;
    }
    
    public function getBooks(): array
    {
        return $this->books;
    }
    
    // Setters
    public function setAuthorId(int $authorId): void
    {
        $this->authorId = $authorId;
    }
    
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    
    public function setBiography(?string $biography): void
    {
        $this->biography = $biography;
    }
    
    public function setBooks(array $books): void
    {
        $this->books = $books;
    }
    
    public function addBook(Book $book): void
    {
        $this->books[] = $book;
    }
    
    public function validate(): bool
    {
        return !empty($this->name);
    }
    
    public function toArray(): array
    {
        return [
            'author_id' => $this->authorId,
            'name' => $this->name,
            'biography' => $this->biography,
            'nationality' => $this->nationality,
            'birth_date' => $this->birthDate?->format('Y-m-d'),
            'primary_genre' => $this->primaryGenre
        ];
    }
}