<?php


namespace App\EventSubscriber;


use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationSubscriber implements EventSubscriberInterface
{
    const ONLY_ROUTE = 'api_users_post_collection';
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;
    /**
     * @var CompanyRepository
     */
    private $companyRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        CompanyRepository $companyRepository,
        EntityManagerInterface $entityManager
    )
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->companyRepository = $companyRepository;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['register', EventPriorities::PRE_WRITE]
        ];
    }

    public function register(ViewEvent $event)
    {
        $request = $event->getRequest();
        $method = $request->getMethod();
        $route = $request->get('_route');

        /** @var User $user */
        $user = $event->getControllerResult();

        if (self::ONLY_ROUTE !== $route || !in_array($method, [Request::METHOD_POST]) || !$user instanceof User) {
            return;
        }

        $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));
    }
}