<?php

namespace App\Security\Voter;

use App\Entity\AppointmentType;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class AppointmentTypeVoter extends Voter
{
    /**
     * @var Security
     */
    private $security;

    /**
     * AppointmentTypeVoter constructor.
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports($attribute, $subject)
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [
                'CAN_EDIT_APPOINTMENT_TYPE',
                'CAN_DELETE_APPOINTMENT_TYPE',
                'CAN_CREATE_APPOINTMENT_TYPE'
            ])
            && $subject instanceof AppointmentType;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof AppointmentType) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'CAN_DELETE_APPOINTMENT_TYPE':
            case 'CAN_EDIT_APPOINTMENT_TYPE':
                // logic to determine if the user can VIEW
                // return true or false
                if ($user->isAdmin()) {
                    return true;
                } elseif (
                    $user->isCompanyAdmin() &&
                    $user->getCompany()->getCode() === $subject->getCompany()->getCode()
                ) {
                    return true;
                }
                break;
            case 'CAN_CREATE_APPOINTMENT_TYPE':
                if ($user->isAdmin()) {
                    return true;
                } elseif (
                    $user->isCompanyAdmin() &&
                    (
                        $subject->getCompany() === null ||
                        $user->getCompany()->getCode() === $subject->getCompany()->getCode()
                    )
                ) {
                    return true;
                }
        }

        return false;
    }
}
