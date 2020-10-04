<?php

namespace App\Security\Voter;

use App\Entity\Company;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    protected function supports($attribute, $subject)
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array(
            $attribute,
            [
                'CAN_PUT_USER',
                'CAN_DELETE_USER',
                'CAN_GET_USER_ITEM'
            ]) &&
            $subject instanceof User;
    }

    /**
     * @param mixed $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof User) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'CAN_PUT_USER':
                // logic to determine if the user can EDIT
                // return true or false
                if ($user->isSuperAdmin()) {
                    return true;
                } elseif ($user->isAdmin() && !$subject->isSuperAdmin()) {
                    return true;
                } elseif (
                    $user->isCompanyAdmin() &&
                    $user->getCompany()->getCode() === $subject->getCompany()->getCode() &&
                    !$subject->isAdmin() &&
                    !$subject->isSuperAdmin()
                ) {
                    return true;
                } elseif ($user->getCode() === $subject->getCode()) {
                    return true;
                }
                break;
            case 'CAN_DELETE_USER':
                if ($user->isSuperAdmin()) {
                    return true;
                } elseif ($user->isAdmin() && !$subject->isSuperAdmin()) {
                    return true;
                } elseif (
                    $user->isCompanyAdmin() &&
                    $user->getCompany()->getCode() === $subject->getCompany()->getCode() &&
                    !$subject->isAdmin() &&
                    !$subject->isSuperAdmin()
                ) {
                    return true;
                }
                break;
            case 'CAN_GET_USER_ITEM':
                if ($user->isAdmin()) {
                    return true;
                } elseif (
                    $user->isCompanyAdmin() &&
                    $user->getCompany()->getCode() === $subject->getCompany()->getCode()
                ) {
                    return true;
                } elseif ($user->getCode() === $subject->getCode()) {
                    return true;
                }
                break;
        }

        return false;
    }
}
