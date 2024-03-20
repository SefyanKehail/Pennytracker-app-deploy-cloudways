<?php

namespace App\Contracts;

interface UserInterface
{
    public function getId(): int;

    public function getPassword(): string;

    public function getName(): string;

    public function getEmail(): string;

    public function setEmail(string $email): self;

    public function setName(string $name): self;

    public function getVerifiedAt(): ?\DateTime;

    public function setVerifiedAt(?\DateTime $verifiedAt): self;

    public function isTwoFactorEnabled(): bool;

    public function setTwoFactorEnabled(bool $twoFactorEnabled): self;

}