<?php


namespace App\Doctrine;


use App\Entity\User;

class UserSetRoleListener
{
    public function prePersist(User $user)
    {
        if ($user->getCompany() && !$user->getCompany()->getId()) {
            $user->makeCompanyAdmin();
        }
    }
}