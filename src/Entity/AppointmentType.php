<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\AppointmentTypeRepository;
use App\Validator\IsOwnCompany;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "get"={
 *              "security"="is_granted('ROLE_ADMIN')"
 *          },
 *          "post"={
 *              "security_post_denormalize"="is_granted('CAN_CREATE_APPOINTMENT_TYPE', object)"
 *          }
 *     },
 *     itemOperations={
 *          "get"={
 *              "security"="is_granted('IS_AUTHENTICATED_ANONYMOUSLY')"
 *          },
 *          "put"={
 *              "security"="is_granted('CAN_EDIT_APPOINTMENT_TYPE', object)"
 *          },
 *          "delete"={
 *              "security"="is_granted('CAN_DELETE_APPOINTMENT_TYPE', object)"
 *          }
 *     },
 *     subresourceOperations={
 *          "api_companies_appointment_types_get_subresource"={
 *              "method"="GET",
 *              "normalization_context"={
 *                  "groups"={"appointment_type:read"}
 *              },
 *              "security"="is_granted('IS_AUTHENTICATED_ANONYMOUSLY')"
 *          }
 *     }
 * )
 * @ORM\Entity(repositoryClass=AppointmentTypeRepository::class)
 * @ORM\EntityListeners({
 *          "App\Doctrine\AppointmentTypeSetCompanyListener"
 *     })
 * @UniqueEntity({"name", "company"})
 */
class AppointmentType
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"appointment_type:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(min=3, max=255)
     * @Groups({"appointment_type:read", "appointment_type:write"})
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank()
     * @Assert\GreaterThan(0)
     * @Groups({"appointment_type:read", "appointment_type:write"})
     */
    private $duration;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="appointmentTypes", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     * @IsOwnCompany()
     * @Groups({"appointment_type:collection:write", "appointment_type:read", "admin:write"})
     */
    private $company;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }
}
