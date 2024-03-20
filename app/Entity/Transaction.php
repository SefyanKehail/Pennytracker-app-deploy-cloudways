<?php

namespace App\Entity;

use App\Contracts\FilterableByUserInterface;
use App\Entity\Traits\HasTimestamps;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[HasLifecycleCallbacks]
#[Table(name: 'transactions')]
class Transaction implements FilterableByUserInterface
{

    use HasTimestamps;

    #[Id]
    #[Column(options: ['unsigned' => true]), GeneratedValue]
    private int $id;

    #[Column]
    private string $description;

    #[Column]
    private \DateTime $date;

    #[Column(options: ['default' => 0])]
    private bool $reviewed;

    #[Column(type: Types::DECIMAL, precision: 13, scale: 3)]
    private float $amount;

    #[Column(name: 'created_at')]
    private \DateTime $createdAt;

    #[Column(name: 'updated_at')]
    private \DateTime $updatedAt;

    #[ManyToOne(inversedBy: 'transactions')]
    private Category $category;

    #[ManyToOne(inversedBy: 'transactions')]
    private User $user;

    #[OneToMany(mappedBy: 'transaction', targetEntity: Receipt::class, cascade: ['remove'])]
    private Collection $receipts;



    public function __construct()
    {
        $this->receipts = new ArrayCollection();
        $this->reviewed = false;
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getReceipts(): Collection
    {
        return $this->receipts;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function setDate(\DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

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

    public function setCategory(Category $category): self
    {
//        $category->addTransaction($this);

        $this->category = $category;

        return $this;
    }

    public function setUser(User $user): self
    {
//        $user->addTransaction($this);

        $this->user = $user;

        return $this;
    }

    public function addReceipt(Receipt $receipt): self
    {
        $this->receipts->add($receipt);

        return $this;
    }

    public function isReviewed(): bool
    {
        return $this->reviewed;
    }

    public function setReviewed(bool $reviewed): self
    {
        $this->reviewed = $reviewed;
        return $this;
    }
}