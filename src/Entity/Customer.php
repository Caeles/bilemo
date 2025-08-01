<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]

    #[Groups(['getUser', 'getCustomer'])]
    private ?int $id = null;
    
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    #[ORM\Column(length: 255)]
    #[Groups(['getUser', 'getCustomer'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getUser', 'getCustomer'])]
    private ?string $lastName = null;

    #[Groups(['getCustomer', 'getUser'])]
    #[ORM\Column(length: 255)]
    private ?string $Email = null;

    #[ORM\Column(length: 255)]
    private ?string $Password = null;
    

    #[MaxDepth(1)]
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: User::class)]
    
    private Collection $users;
    
    #[Groups(['getCustomer'])]
    #[ORM\Column(length: 255)]
    private ?string $company_name = null;




    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->Email;
    }

    public function setEmail(string $Email): static
    {
        $this->Email = $Email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->Password;
    }

    public function setPassword(string $Password): static
    {
        $this->Password = $Password;

        return $this;
    }
    
    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }
    
    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setCustomer($this);
        }
        
        return $this;
    }
    
    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getCustomer() === $this) {
                $user->setCustomer(null);
            }
        }
        
        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->company_name;
    }

    public function setCompanyName(string $company_name): static
    {
        $this->company_name = $company_name;

        return $this;
    }
}
