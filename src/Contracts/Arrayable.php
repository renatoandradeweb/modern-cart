<?php

declare(strict_types=1);

// src/Contracts/Arrayable.php
namespace ModernCart\Contracts;

interface Arrayable
{
    /**
     * Get the instance as an array.
     */
    public function toArray(): array;
}