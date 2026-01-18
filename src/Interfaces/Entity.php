<?php

namespace Interfaces;

interface Entity
{
    public function toArray(): array;
    public function validate(): bool;
}