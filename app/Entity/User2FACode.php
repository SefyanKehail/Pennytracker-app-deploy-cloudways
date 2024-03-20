<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'user_2FA_codes')]
class User2FACode
{
    #[Id, Column(options: ['unsigned' => true]),GeneratedValue]
    private int $id;

    #[Column(length: 6)]
    private string $code;

    #[Column(name: 'expiration_date')]
    private \DateTime $expirationDate;

    #[Column(options: ['default' => false])]
    private bool $enabled;

    #[OneToOne(targetEntity: User::class)]
    private User $user;

    public function __construct()
    {
        $this->enabled = false;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function setExpirationDate(\DateTime $expirationDate): self
    {
        $this->expirationDate = $expirationDate;
        return $this;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getExpirationDate(): \DateTime
    {
        return $this->expirationDate;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

}