<?php

namespace App\Contracts;

interface SessionInterface
{
    public function start(): bool;

    public function save(): void;

    public function isActive(): bool;

    public function set(string $key, mixed $value = null): void;

    public function has(string $key): bool;

    public function get(string $key): mixed;

    public function forget(string|array $keys): void;

    public function regenerate(): bool;

    public function flash(string $key, array $messages): void;

    public function getFlash(string $key): array;
}