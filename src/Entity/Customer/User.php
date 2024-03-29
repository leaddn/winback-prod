<?php

namespace App\Entity\Customer;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
//#[ORM\Table(name:"`user_entity`")]
//class User implements UserInterface, PasswordAuthenticatedUserInterface
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private $username;

    /*
    #[ORM\Column(type: 'string', length: 180, name:"`first_name`")]
    private $firstName;

    #[ORM\Column(type: 'string', length: 180, name:"`last_name`")]
    private $lastName;
    */
    
    #[ORM\Column(type: 'json')]
    private $roles = ["ROLE_USER"];
    

    
    #[ORM\Column(type: 'string', nullable: true)]
    private $password;
    
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private $email;

    #[ORM\Column(type: 'boolean')]
    private $is_verified = false;

    //#[ORM\Column(type: 'boolean', name:"`enabled`")]
    //private $enabled = false;

    
    #[ORM\Column(type:'string', name:"`keycloak_id`")]
    private $keycloakId;
    
    
    public function __toString(): string
    {
        return $this->username;
        //return $this->email;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /*
    public function getFirstname(): ?string
    {
        return $this->firstName;
    }

    public function setFirstname(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastName;
    }

    public function setLastname(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }
    */
    
    public function getUsername(): ?string
    {
        return $this->username;
    }
    
    
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }
    
    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        //return (string) $this->firstName;
        //return $this->username;
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = "ROLE_USER";

        return array_unique($roles);
    }
    
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }
    
    /**
     * @see PasswordAuthenticatedUserInterface
     */
    
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }
    
    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    
    public function getIsVerified(): ?bool
    {
        return $this->is_verified;
    }

    public function setIsVerified(bool $is_verified): self
    {
        $this->is_verified = $is_verified;

        return $this;
    }
    /*
    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }
    */
    public function getKeycloakId(): ?string
    {
        return $this->keycloakId;
    }

    public function setKeycloakId(string $keycloakId): self
    {
        $this->keycloakId = $keycloakId;

        return $this;
    }
    /*
    public function getKeycloakId(): ?string
    {
        return $this->id;
    }

    public function setKeycloakId(string $id): self
    {
        $this->id = $id;

        return $this;
    }
    */
}
