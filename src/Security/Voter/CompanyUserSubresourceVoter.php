<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CompanyUserSubresourceVoter extends Voter
{
    protected function supports($attribute, $subject)
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, ['CAN_GET_COMPANY_USERS'])
            && is_string($subject);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'CAN_GET_COMPANY_USERS':
                // logic to determine if the user can EDIT
                // return true or false
                if ($user->isAdmin()) {
                    return true;
                } elseif ($user->getCompany()->getCode() === $subject) {
                    return true;
                }
                break;
        }

        return false;
    }
}
