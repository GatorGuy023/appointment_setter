<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "get",
 *          "post"={
 *              "denormalization_context"={
 *                  "groups"={"post"}
 *              }
 *          }
 *     },
 *     itemOperations={
 *          "get"={
 *              "normalization_context"={
 *                  "groups"={"get-company-users"}
 *              }
 *          },
 *          "put"={
 *              "denormalization_context"={
 *                  "groups"={"put"}
 *              },
 *              "normalization_context"={
 *                  "groups"={"get"}
 *              }
 *          },
 *          "delete"
 *     }
 * )
 * @ORM\Entity(repositoryClass=CompanyRepository::class)
 * @UniqueEntity("code")
 * @UniqueEntity("name")
 */
class Company
{
    const APP_COMPANY_NAME = 'AppointmentSetter';

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
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(min="2", max="255")
     * @Groups({"get", "post", "put", "get-company-users"})
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="company")
     * @ApiSubresource()
     * @Groups({"get-company-users", "get"})
     */
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->code = Uuid::v4()->toRfc4122();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function setUsers(Collection $users): self
    {
        $this->users = $users;

        return $this;
    }

    public function addUser(User $user): self
    {
        $this->users->add($user);

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }
}
