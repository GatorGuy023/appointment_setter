<?php


namespace App\Tests;


use App\Entity\Company;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class LoginLogoutTest extends ApiTestBaseCase
{
    public function testLoginSuccess()
    {
        $user = $this->createUserAndLogin(
            'testUser1',
            'testUser1@example.com',
            'John',
            'Doe',
            'Pa$$w0rd',
            Company::APP_COMPANY_NAME
        );

        $this->assertResponseHasHeader('Location', 'Successful login should have a Location header returned');
        $this->assertResponseHeaderSame(
            'Location',
            '/api/users/'.$user->getCode(),
            'Location header should contain the IRI of the logged in user'
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testLoginFailure()
    {
        $user = $this->createUser(
            'testUser1',
            'testUser1@example.com',
            'John',
            'Doe',
            'Pa$$w0rd',
            Company::APP_COMPANY_NAME
        );

        $this->client->request('POST', '/login', [
            'json' => [
                'username' => $user->getUsername(),
                'password' => 'badpassword'
            ]
        ]);

        $this->assertResponseStatusCodeSame(401);

        $this->client->request('POST', '/login', [
            'json' => []
        ]);

        $this->assertResponseStatusCodeSame(
            400,
            'Login should return 400 on malformed json body'
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testLogout()
    {
        $this->createUserAndLogin(
            'testUser1',
            'testUser1@example.com',
            'John',
            'Doe',
            'Pa$$w0rd',
            Company::APP_COMPANY_NAME
        );

        $this->client->request('GET', '/logout');

        $this->assertResponseStatusCodeSame(204);
    }
}