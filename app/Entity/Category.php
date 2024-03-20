<?php

namespace App\Entity;
use App\Contracts\FilterableByUserInterface;
use App\Entity\Traits\HasTimestamps;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'categories')]
#[HasLifecycleCallbacks]
class Category implements FilterableByUserInterface
{
    use HasTimestamps;

    #[Id]
    #[Column(options: ['unsigned' => true]), GeneratedValue]
    private int $id;

    #[Column]
    private string $name;

    #[OneToMany(mappedBy: 'category',targetEntity: Transaction::class, cascade: ['persist'])]
    private Collection $transactions;

    #[ManyToOne(inversedBy: 'categories')]
    private User $user;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getUser(): User
    {
        return $this->user;
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

    public function setUser(User $user): self
    {
        $user->addCategory($this);
        $this->user = $user;
        return $this;
    }

    public function __toString(): string
    {
        return "category class";
    }


}