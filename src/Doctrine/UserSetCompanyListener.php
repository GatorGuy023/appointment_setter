<?php


namespace App\Doctrine;


use App\Entity\Company;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Security;

class UserSetCompanyListener
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function prePersist(User $user)
    {
        /** @var User $loggedInUser */
        $loggedInUser = $this->security->getUser();

        if ($user->getCompany()) {
            return;
        }

        if ($this->security->isGranted(User::ROLE_USER)) {
            $user->setCompany($loggedInUser->getCompany());
        }
    }
}