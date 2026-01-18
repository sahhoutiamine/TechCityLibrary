<?php

namespace Models;

use Interfaces\Entity;

class LibraryBranch implements Entity
{
    private ?int $branchId;
    private string $name;
    private ?string $location;
    private ?string $contactNumber;
    
    public function __construct(
        ?int $branchId,
        string $name,
        ?string $location = null,
        ?string $contactNumber = null
    ) {
        $this->branchId = $branchId;
        $this->name = $name;
        $this->location = $location;
        $this->contactNumber = $contactNumber;
    }
    
    // Getters
    public function getBranchId(): ?int
    {
        return $this->branchId;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getLocation(): ?string
    {
        return $this->location;
    }
    
    public function getContactNumber(): ?string
    {
        return $this->contactNumber;
    }
    
    // Setters
    public function setBranchId(int $branchId): void
    {
        $this->branchId = $branchId;
    }
    
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    
    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }
    
    public function setContactNumber(?string $contactNumber): void
    {
        $this->contactNumber = $contactNumber;
    }
    
    public function validate(): bool
    {
        return !empty($this->name);
    }
    
    public function toArray(): array
    {
        return [
            'branch_id' => $this->branchId,
            'name' => $this->name,
            'location' => $this->location,
            'contact_number' => $this->contactNumber
        ];
    }
}