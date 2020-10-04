<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\UserRepository;
use App\Validator\IsNewCompanyAndAuthenticatedAnonymously;
use App\Validator\IsOwnCompany;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "get"={
 *              "security"="is_granted('ROLE_ADMIN')"
 *          },
 *          "post"={
 *              "validation_groups"={"Default", "user:write"},
 *              "security"="is_granted('IS_AUTHENTICATED_ANONYMOUSLY')"
 *          }
 *     },
 *     itemOperations={
 *          "get"={
 *              "security"="is_granted('CAN_GET_USER_ITEM', object)"
 *          },
 *          "put"={
 *              "security"="is_granted('CAN_PUT_USER', object)"
 *          },
 *          "delete"={
 *              "security"="is_granted('CAN_DELETE_USER', object)"
 *          }
 *     },
 *     subresourceOperations={
 *          "api_companies_users_get_subresource"={
 *              "method"="GET",
 *              "path"="/api/companies/{id}/users",
 *              "normalization_context"={
 *                  "groups"={"user:read"}
 *              },
 *              "security"="is_granted('CAN_GET_COMPANY_USERS', id)"
 *          }
 *     }
 * )
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\EntityListeners({
 *          "App\Doctrine\UserSetCompanyListener",
 *          "App\Doctrine\UserSetRoleListener"
 *     }
 * )
 * @UniqueEntity("code")
 * @UniqueEntity("username")
 * @UniqueEntity("email")
 */
class User implements UserInterface
{
    const ROLE_USER = 'ROLE_USER';
    const ROLE_COMPANY_ADMIN = 'ROLE_COMPANY_ADMIN';
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

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
     * @Groups({"user:read", "company:item:read", "user:collection:write"})
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @Assert\Regex(
     *    pattern="/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{};':\x22\\|,.<>\/?]).{7,}/",
     *    message="Password must be at least 8 characters long and contain at least one digit, one uppercase letter, one lowercase letter, and one special character",
     *    groups={"user:write"}
     *   )
     * @Groups("user:write")
     * @SerializedName("password")
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="array")
     * @Groups({"admin:read"})
     */
    private $roles = [User::ROLE_USER];

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
     * @Groups({"user:read", "user:collection:write", "company:item:read"})
     */
    private $email;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Company", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"user:item:read", "user:collection:write"})
     * @IsOwnCompany()
     * @IsNewCompanyAndAuthenticatedAnonymously()
     * @Assert\Valid()
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

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

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
     * @Groups({"user:read", "company:item:read"})
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

    /**
     * @Groups({"user:read"})
     * @return bool
     */
    public function isCompanyAdmin(): bool
    {
        return (
            count(
                array_intersect(
                    $this->getRoles(),
                    [User::ROLE_COMPANY_ADMIN, User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN]
                )
            ) !== 0
        );
    }

    /**
     * @Groups({"user:read"})
     * @return bool
     */
    public function isAdmin(): bool
    {
        return (
            count(
                array_intersect(
                    $this->getRoles(),
                    [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN]
                )
            ) !== 0
        );
    }

    /**
     * @Groups({"user:read"})
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return (
            count(
                array_intersect(
                    $this->getRoles(),
                    [User::ROLE_SUPER_ADMIN]
                )
            ) !== 0
        );
    }

    /**
     * @Groups({"company:admin:write", "admin:write", "super:admin:write"})
     * @param bool $isBasicUser
     * @return $this
     */
    public function setIsBasicUser(bool $isBasicUser): self
    {
        if ($isBasicUser) {
            $this->makeBasicUser();
        }

        return $this;
    }

    /**
     * @Groups({"company:admin:write", "admin:write", "super:admin:write"})
     * @param bool $isCompanyAdmin
     * @return $this
     */
    public function setIsCompanyAdmin(bool $isCompanyAdmin): self
    {
        if ($isCompanyAdmin) {
            $this->makeCompanyAdmin();
        }

        return $this;
    }

    /**
     * @Groups({"admin:write", "super:admin:write"})
     * @param bool $isAdmin
     * @return $this
     */
    public function setIsAdmin(bool $isAdmin): self
    {
        if ($isAdmin) {
            $this->makeAdmin();
        }

        return $this;
    }

    /**
     * @Groups({"super:admin:write"})
     * @param bool $isSuperAdmin
     * @return $this
     */
    public function setIsSuperAdmin(bool $isSuperAdmin): self
    {
        if ($isSuperAdmin) {
            $this->makeSuperAdmin();
        }

        return $this;
    }

    public function makeBasicUser(): self
    {
        $this->setRoles([User::ROLE_USER]);

        return $this;
    }

    public function makeCompanyAdmin(): self
    {
        $this->setRoles([User::ROLE_COMPANY_ADMIN]);

        return $this;
    }

    public function makeAdmin(): self
    {
        $this->setRoles([User::ROLE_ADMIN]);

        return $this;
    }

    public function makeSuperAdmin(): self
    {
        $this->setRoles([User::ROLE_SUPER_ADMIN]);

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
