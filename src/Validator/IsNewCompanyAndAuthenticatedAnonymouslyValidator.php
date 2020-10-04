<?php

namespace App\Validator;

use App\Entity\Company;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsNewCompanyAndAuthenticatedAnonymouslyValidator extends ConstraintValidator
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
        /* @var $constraint IsNewCompanyAndAuthenticatedAnonymously */

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof Company) {
            throw new \InvalidArgumentException(
                '@IsNewCompanyAndAuthenticatedAnonymously constraint must be set on a company object'
            );
        }

        if (
            !$this->security->getUser() &&
            $this->security->isGranted('IS_AUTHENTICATED_ANONYMOUSLY') &&
            $value->getId()
        ) {
            //var_dump($value->getId());
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
