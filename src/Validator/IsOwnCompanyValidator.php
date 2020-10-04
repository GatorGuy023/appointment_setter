<?php

namespace App\Validator;

use App\Entity\Company;
use App\Entity\User;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsOwnCompanyValidator extends ConstraintValidator
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint IsOwnCompany */
        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof Company) {
            throw new InvalidArgumentException(
                '@isOwnCompany constraint must be set on a company object'
            );
        }

        if ($this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        /** @var User $loggedInUser */
        $loggedInUser = $this->security->getUser();

        if (
            $this->security->isGranted(User::ROLE_USER) &&
            $loggedInUser->getCompany()->getCode() !== $value->getCode()
        ) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
            return;
        }
    }
}
