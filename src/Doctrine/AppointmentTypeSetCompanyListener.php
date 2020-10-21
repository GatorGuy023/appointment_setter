<?php


namespace App\Doctrine;


use App\Entity\AppointmentType;
use App\Entity\User;
use Symfony\Component\Security\Core\Security;

class AppointmentTypeSetCompanyListener
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function prePersist(AppointmentType $appointmentType)
    {
        /** @var User $loggedInUser */
        $loggedInUser = $this->security->getUser();

        if ($appointmentType->getCompany()) {
            return;
        }

        if ($this->security->isGranted(User::ROLE_USER)) {
            $appointmentType->setCompany($loggedInUser->getCompany());
        }
    }
}