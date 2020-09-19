<?php


namespace App\Controller;


use ApiPlatform\Core\Api\IriConverterInterface;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login", methods={"POST"})
     */
    public function login(IriConverterInterface $iriConverter)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->json([
                'error' => 'Invalid Data: make sure your "Content-Type" header is set to "application/json"'
            ], 400);
        }

        /** @var User $user */
        $user = $this->getUser();

        return new Response(
            null,
            204,
            [
                'Location' => $iriConverter->getIriFromItem($user)
            ]);
    }

}