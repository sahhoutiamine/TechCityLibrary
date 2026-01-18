<?php

namespace Models;

use Interfaces\Entity;

class Category implements Entity
{
    private ?int $categoryId;
    private string $name;
    
    public function __construct(?int $categoryId, string $name)
    {
        $this->categoryId = $categoryId;
        $this->name = $name;
    }
    
    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setCategoryId(int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }
    
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    
    public function validate(): bool
    {
        return !empty($this->name);
    }
    
    public function toArray(): array
    {
        return [
            'category_id' => $this->categoryId,
            'name' => $this->name
        ];
    }
}