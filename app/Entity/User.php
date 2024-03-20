<?php

namespace App\Entity;

use App\Contracts\FilterableByUserInterface;
use App\Contracts\UserInterface;
use App\Entity\Traits\HasTimestamps;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[HasLifecycleCallbacks]
#[Table(name: 'users')]
class User implements UserInterface
{
    use HasTimestamps;

    #[Id]
    #[Column(options: ['unsigned' => true]), GeneratedValue]
    private int $id;

    #[Column(unique: true)]
    private string $email;

    #[Column]
    private string $password;

    #[Column]
    private string $name;

    #[OneToMany(mappedBy: 'user', targetEntity: Transaction::class, cascade: ['persist'])]
    private Collection $transactions;

    #[OneToMany(mappedBy: 'user', targetEntity: Category::class, cascade: ['persist'])]
    private Collection $categories;

    #[Column(name: 'verified_at', nullable: true)]
    private ?\DateTime $verifiedAt;

    #[Column(name: 'two_factor_enabled', options: ['default' => false])]
    private bool $twoFactorEnabled;


    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->categories   = new ArrayCollection();
        $this->twoFactorEnabled = false;
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }


    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function addTransaction(Transaction $transaction): self
    {
        $this->transactions->add($transaction);
        return $this;
    }

    public function addCategory(Category $category): self
    {
        $this->categories->add($category);
        return $this;
    }

    // manual check ( in the context of filters )
    public function canManage(FilterableByUserInterface $entity): bool
    {
        return $this->id === $entity->getUser()->getId();
    }

    public function getVerifiedAt(): ?\DateTime
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTime $verifiedAt): self
    {
        $this->verifiedAt = $verifiedAt;
        return $this;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function setTwoFactorEnabled(bool $twoFactorEnabled): self
    {
        $this->twoFactorEnabled = $twoFactorEnabled;
        return $this;
    }

    public function __toString(): string
    {
        return "user class";
    }


}