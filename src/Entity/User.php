<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *          "groups"={"user:read"}
 *     },
 *     denormalizationContext={
 *          "groups"={"user:write"}
 *     },
 *     collectionOperations={
 *          "get",
 *          "post"={
 *              "denormalization_context"={
 *                  "groups"={"user:write", "user:write:collection"}
 *              }
 *          }
 *     },
 *     itemOperations={
 *          "get"={
 *              "normalization_context"={
 *                  "groups"={"user:read", "user:read:item"}
 *              }
 *          },
 *          "put",
 *          "delete"
 *     },
 *     subresourceOperations={
 *          "api_companies_users_get_subresource"={
 *              "method"="GET",
 *              "normalization_context"={
 *                  "groups"={"get-company-users"}
 *              }
 *          }
 *     }
 * )
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity("code")
 * @UniqueEntity("username")
 * @UniqueEntity("email")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @ApiProperty(identifier=false)
     */
    private $id;

    /**
     * @ORM\Column(type="guid", unique=true)
     * @ApiProperty(identifier=true)
     * @Groups({"user:read"})
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(min="6", max="255")
     * @Groups({"user:read", "company:read:item", "user:write"})
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Regex(
     *    pattern="/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{};':\x22\\|,.<>\/?]).{7,}/",
     *    message="Password must be at least 8 characters long and contain at least one digit, one uppercase letter, one lowercase letter, and one special character",
     *    groups={"user:write"}
     *   )
     * @Groups({"user:write"})
     */
    private $password;

    /**
     * @ORM\Column(type="array")
     */
    private $roles = ['ROLE_USER'];

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(min="1", max="255")
     * @Groups({"user:write"})
     */
    private $fname;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(min="1", max="255")
     * @Groups({"user:write"})
     */
    private $lname;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     * @Assert\Length(max="255")
     * @Groups({"user:read", "user:write", "company:read:item"})
     */
    private $email;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Company", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"user:read:item", "user:write:collection"})
     * @Assert\NotNull()
     */
    private $company;

    public function __construct()
    {
        $this->code = Uuid::v4()->toRfc4122();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getFname(): ?string
    {
        return $this->fname;
    }

    public function setFname(string $fname): self
    {
        $this->fname = $fname;

        return $this;
    }

    public function getLname(): ?string
    {
        return $this->lname;
    }

    public function setLname(string $lname): self
    {
        $this->lname = $lname;

        return $this;
    }

    /**
     * @Groups({"user:read", "company:read:item"})
     */
    public function getFullName(): string
    {
        return $this->fname . ' ' . $this->lname;
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

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }


    public function getSalt()
    {
        if ($this->email) {
            return $this->email;
        }

        return '';
    }

    public function eraseCredentials()
    {
    }
}
