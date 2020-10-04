<?php


namespace App\Tests;

use App\Entity\Company;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UserTest extends ApiTestBaseCase
{
    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testCreateUser()
    {
        /** @var User[] $users */
        $users = $this->makeUsers();

        $newUserJson = [
            'username' => 'testuser1',
            'email' => 'testuser1@example.com',
            'password' => self::PASSWORD,
            'fname' => 'John',
            'lname' => 'Doe',
            'company' => [
                'name' => 'New Company A'
            ]
        ];

        $this->client->request('POST', '/api/users', [
            'json' => $newUserJson
        ]);

        $this->assertResponseStatusCodeSame(
            201,
            "Expected a 201 status code on user create."
        );
        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            '@type' => 'User',
            'username' => 'testuser1',
            'email' => 'testuser1@example.com',
            'fullName' => 'John Doe',
            'companyAdmin' => true,
            'admin' => false,
            'superAdmin' => false,
        ]);

        $em = $this->getEntityManager();
        /** @var User $newUser */
        $newUser = $em->getRepository(User::class)->findOneBy(['username' => $newUserJson['username']]);
        $this->assertTrue(
            $newUser->isCompanyAdmin(),
            'Failed to assert that anonymous user created is a company admin'
        );

        $newUserJson['company'] = '/api/companies/'.$newUser->getCompany()->getCode();
        $newUserJson['username'] = 'testUser2';
        $newUserJson['email'] = 'testUser2@example.com';
        $this->client->request('POST', '/api/users', [
            'json' => $newUserJson
        ]);

        $this->assertResponseStatusCodeSame(
            400,
            "Expected a 400 status code because company was not new."
        );

        $this->login($newUser, self::PASSWORD);
        $this->logout();

        //try to create a new user anonymously with the same company name should fail
        $this->client->request('POST', '/api/users', [
            'json' => [
                'username' => 'testuser2',
                'email' => 'testuser2@example.com',
                'password' => self::PASSWORD,
                'fname' => 'Jane',
                'lname' => 'Doe',
                'company' => [
                    'name' => Company::APP_COMPANY_NAME
                ]
            ]
        ]);

        $this->assertResponseStatusCodeSame(
            400,
            'Failed to assert that new user not created on anonymous user create and an existing company name supplied'
        );

        $this->login($users[User::ROLE_USER.'_A'], self::PASSWORD);
        $this->client->request('POST', '/api/users', [
            'json' => [
                'username' => 'testuser3',
                'email' => 'testuser3@example.com',
                'password' => self::PASSWORD,
                'fname' => 'John',
                'lname' => 'Doe',
                'company' => '/api/companies/'.$users[User::ROLE_USER.'_B']->getCompany()->getCode()
            ]
        ]);

        $this->assertResponseStatusCodeSame(
            400,
            'Basic users should not be able to create new users not in their company'
        );

        $this->logout();
        $this->login($users[User::ROLE_COMPANY_ADMIN.'_A'], self::PASSWORD);
        $this->client->request('POST', '/api/users', [
            'json' => [
                'username' => 'testuser3',
                'email' => 'testuser3@example.com',
                'password' => self::PASSWORD,
                'fname' => 'John',
                'lname' => 'Doe',
                'company' => '/api/companies/'.$users[User::ROLE_USER.'_B']->getCompany()->getCode()
            ]
        ]);

        $this->assertResponseStatusCodeSame(
            400,
            'Company admins shouldn\'t be able to add users with companies other than their own'
        );

        $this->client->request('POST', '/api/users', [
            'json' => [
                'username' => 'testuser3',
                'email' => 'testuser3@example.com',
                'password' => self::PASSWORD,
                'fname' => 'John',
                'lname' => 'Doe',
                'company' => '/api/companies/'.$users[User::ROLE_USER.'_A']->getCompany()->getCode()
            ]
        ]);
        $this->assertResponseStatusCodeSame(
            201,
            'Company admin should be able to add user for their company'
        );

        /** @var User $newUser4 */
        $newUser4 = $em->getRepository(User::class)->findOneBy(['username' => 'testuser3']);

        $this->assertTrue(
            in_array(User::ROLE_USER, $newUser4->getRoles()),
            'User created by basic user should be basic user'
        );

        $this->client->request('POST', '/api/users', [
            'json' => [
                'username' => 'testuser4',
                'email' => 'testuser4@example.com',
                'password' => self::PASSWORD,
                'fname' => 'John',
                'lname' => 'Doe'
            ]
        ]);
        $this->assertResponseStatusCodeSame(
            201,
            'Company admin should be able to add user for their company without sending their company'
        );

        /** @var User $newUser2 */
        $newUser2 = $em->getRepository(User::class)->findOneBy(['username' => 'testuser4']);

        $this->assertEquals(
            $users[User::ROLE_COMPANY_ADMIN.'_A']->getCompany()->getCode(),
            $newUser2->getCompany()->getCode(),
            'Companies should match for testuser4 and \'ROLE_COMPANY_ADMIN_A\''
        );

        $this->assertTrue(
            in_array(User::ROLE_USER, $newUser2->getRoles()),
            'Newly created users should be basic users'
        );

        $this->logout();
        $this->login($users[User::ROLE_ADMIN], self::PASSWORD);
        $this->client->request('POST', '/api/users', [
            'json' => [
                'username' => 'testuser5',
                'email' => 'testuser5@example.com',
                'password' => self::PASSWORD,
                'fname' => 'John',
                'lname' => 'Doe',
                'company' => '/api/companies/'.$users[User::ROLE_USER.'_A']->getCompany()->getCode()
            ]
        ]);
        $this->assertResponseStatusCodeSame(
            201,
            'Regular admins should be able to create users with any company.'
        );

        /** @var User $newUser3 */
        $newUser3 = $em->getRepository(User::class)->findOneBy(['username' => 'testuser5']);
        $this->assertNotEquals(
            $users[User::ROLE_ADMIN]->getCompany()->getCode(),
            $newUser3->getCompany()->getCode(),
            'Companies should match for testuser5 and \'ROLE_ADMIN\''
        );

        $this->client->request('POST', '/api/users', [
            'json' => [
                'username' => 'testuser6',
                'email' => 'testuser6@example.com',
                'password' => self::PASSWORD,
                'fname' => 'John',
                'lname' => 'Doe',
                'company' => [
                    'name' => 'New Company BB'
                ]
            ]
        ]);

        $this->assertResponseStatusCodeSame(
            201,
            'Regular admins should be able to create users with new company.'
        );

        /** @var User $newUser5 */
        $newUser5 = $em->getRepository(User::class)->findOneBy(['username' => 'testuser6']);
        $this->assertTrue(
            in_array(User::ROLE_COMPANY_ADMIN, $newUser5->getRoles()),
            'Newly created user with new company should be company admin.'
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetCollectionOfUsers()
    {
        /** @var User[] $users */
        $users = $this->makeUsers();

        $this->login($users[User::ROLE_SUPER_ADMIN], self::PASSWORD);

        $this->client->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(
            200,
            'Failed to assert that super admin returned with 200 status code'
        );
        $this->assertJsonContains([
            'hydra:totalItems' => count($users)
        ],
        true,
        'Failed to assert that hydra:totalItems contains 6 items for super admin'
        );

        $this->logout();

        $this->login($users[User::ROLE_ADMIN], self::PASSWORD);

        $this->client->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(
            200,
        'Failed to assert that admin returned with 200 status code'
        );
        $this->assertJsonContains([
            'hydra:totalItems' => count($users)
        ],
            true,
            'Failed to assert that hydra:totalItems contains 6 items for admin'
        );

        $this->logout();

        $this->login($users[User::ROLE_COMPANY_ADMIN.'_A'], 'Pa$$w0rd');

        $this->client->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(
            403,
            'Failed to assert that 403 was returned for company admin'
        );

        $this->logout();

        $this->login($users[User::ROLE_USER.'_A'], self::PASSWORD);

        $this->client->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(
            403,
        'Failed to assert that 403 was returned for basic user'
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetUserItem()
    {
        /** @var User[] $users */
        $users = $this->makeUsers();

        $this->client->request('GET', '/api/users/'.$users[User::ROLE_USER.'_A']->getCode());
        $this->assertResponseStatusCodeSame(
            401,
            'Failed to assert that status code 401 was returned on Anonymous call'
        );

        $this->login($users[User::ROLE_USER.'_B'], self::PASSWORD);
        $this->client->request('GET', '/api/users/'.$users[User::ROLE_USER.'_A']->getCode());
        $this->assertResponseStatusCodeSame(
            403,
        'Failed to assert that 403 was returned for a basic user trying to access another user not in their company'
        );

        $this->client->request('GET', '/api/users/'.$users[User::ROLE_COMPANY_ADMIN.'_B']->getCode());
        $this->assertResponseStatusCodeSame(
            403,
            'Failed to assert that 403 was returned for a basic user trying to access another user in their company'
        );

        $this->client->request('GET', '/api/users/'.$users[User::ROLE_USER.'_B']->getCode());
        $this->assertResponseStatusCodeSame(
            200,
            'Failed to assert that 200 was returned for basic user trying to access their own information.'
        );
        $this->assertJsonContains([
            'username' => $users[User::ROLE_USER.'_B']->getUsername(),
            'email' => $users[User::ROLE_USER.'_B']->getEmail(),
            'fullName' => $users[User::ROLE_USER.'_B']->getFullName(),
            'companyAdmin' => false,
            'admin' => false,
            'superAdmin' => false,
        ],
            true,
            'Failed to assert that the requested user was returned'
        );

        $this->logout();

        $this->login($users[User::ROLE_COMPANY_ADMIN.'_A'], self::PASSWORD);

        $this->client->request('GET', '/api/users/'.$users[User::ROLE_USER.'_B']->getCode());
        $this->assertResponseStatusCodeSame(
            403,
            'Failed to assert that 403 was returned when company admin tried to access user outside company'
        );

        $this->client->request('GET', '/api/users/'.$users[User::ROLE_USER.'_A']->getCode());
        $this->assertResponseStatusCodeSame(
            200,
            'Failed to assert that 200 was returned when accessing user within company'
        );
        $this->assertJsonContains([
            'username' => $users[User::ROLE_USER.'_A']->getUsername(),
            'email' => $users[User::ROLE_USER.'_A']->getEmail(),
            'fullName' => $users[User::ROLE_USER.'_A']->getFullName(),
            'companyAdmin' => false,
            'admin' => false,
            'superAdmin' => false
        ],
            true,
            'Failed to assert that the correct user was returned'
        );

        $this->logout();

        $this->login($users[User::ROLE_ADMIN], self::PASSWORD);

        $this->client->request('GET', '/api/users/'.$users[User::ROLE_COMPANY_ADMIN.'_A']->getCode());
        $this->assertResponseStatusCodeSame(
            200,
            'Failed to assert that 200 status code was returned when admin accessed a company admin user'
        );
        $this->assertJsonContains([
            'username' => $users[User::ROLE_COMPANY_ADMIN.'_A']->getUsername(),
            'email' => $users[User::ROLE_COMPANY_ADMIN.'_A']->getEmail(),
            'fullName' => $users[User::ROLE_COMPANY_ADMIN.'_A']->getFullName(),
            'companyAdmin' => true,
            'admin' => false,
            'superAdmin' => false,
        ],
            true,
            'Failed to assert that the correct user was returned'
        );

        $this->logout();

        $this->login($users[User::ROLE_SUPER_ADMIN], self::PASSWORD);

        $this->client->request('GET', '/api/users/'.$users[User::ROLE_USER.'_B']->getCode());
        $this->assertResponseStatusCodeSame(
            200,
            'Failed to assert that 200 was returned while trying to access another user not an admin while super admin'
        );
        $this->assertJsonContains([
            'username' => $users[User::ROLE_USER.'_B']->getUsername(),
            'email' => $users[User::ROLE_USER.'_B']->getEmail(),
            'fullName' => $users[User::ROLE_USER.'_B']->getFullName(),
            'companyAdmin' => false,
            'admin' => false,
            'superAdmin' => false
        ],
            true,
            'Failed to assert that the correct user was returned'
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testPutUser()
    {
        /** @var User[] $users */
        $users = $this->makeUsers();

        $newValues = [
            'fname' => 'Fifi',
            'lname' => 'LaRue',
            'isSuperAdmin' => true
        ];

        $this->login($users[User::ROLE_SUPER_ADMIN], self::PASSWORD);
        $this->client->request(
            'PUT',
            '/api/users/'.$users[User::ROLE_USER.'_A']->getCode(),
            [
                'json' => $newValues
            ]
        );

        $expectedValues = [
            'username' => $users[User::ROLE_USER.'_A']->getUsername(),
            'email' => $users[User::ROLE_USER.'_A']->getEmail(),
            'fullName' => $newValues['fname'] . ' ' . $newValues['lname'],
            'companyAdmin' => true,
            'admin' => true,
            'superAdmin' => true
        ];

        $this->assertResponseStatusCodeSame(200, 'Should be able to change as super admin');
        $this->assertJsonContains(
            $expectedValues,
            'Failed to assert the expected values from PUT request as super admin'
        );

        $this->logout();
        $this->login($users[User::ROLE_ADMIN], self::PASSWORD);

        $newValues2 = [
            'fname' => 'Fifi2',
            'lname' => 'LaRue2',
            'isSuperAdmin' => true
        ];

        $this->client->request(
            'PUT',
            '/api/users/'.$users[User::ROLE_USER.'_A']->getCode(),
            [
                'json' => $newValues2
            ]
        );
        $this->assertResponseStatusCodeSame(
            403,
            'Should fail because trying to change a super admin and logged in user is an admin'
        );

        $em = $this->getEntityManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->findOneBy(['username'=>$expectedValues['username']]);
        $user->makeBasicUser();
        $em->flush();

        $this->client->request(
            'PUT',
            '/api/users/'.$users[User::ROLE_USER.'_A']->getCode(),
            [
                'json' => $newValues2
            ]
        );

        $expectedValues = [
            'username' => $users[User::ROLE_USER.'_A']->getUsername(),
            'email' => $users[User::ROLE_USER.'_A']->getEmail(),
            'fullName' => $newValues2['fname'] . ' ' . $newValues2['lname'],
            'companyAdmin' => false,
            'admin' => false,
            'superAdmin' => false
        ];

        $this->assertResponseStatusCodeSame(
            200,
            'Should work to return 200 for admin trying to change basic user'
        );
        $this->assertJsonContains(
            $expectedValues,
            'Failed to assert the expected values from PUT request as admin'
        );

        unset($newValues2['isSuperAdmin']);
        $newValues2['isAdmin'] = true;
        $expectedValues['companyAdmin'] = true;
        $expectedValues['admin'] = true;
        $expectedValues['superAdmin'] = false;

        $this->client->request(
            'PUT',
            '/api/users/'.$users[User::ROLE_USER.'_A']->getCode(),
            [
                'json' => $newValues2
            ]
        );
        $this->assertResponseStatusCodeSame(
            200,
            'Failed to assert that 200 was returned from PUT request'
        );
        $this->assertJsonContains(
            $expectedValues,
            'Failed to assert that the expected values was returned while PUT of admin to basic'
        );

        $this->logout();
        $this->login($users[User::ROLE_COMPANY_ADMIN.'_A'], self::PASSWORD);
        $newValues3 = [
            'fname' => 'Fifi3',
            'lname' => 'LaRue3',
            'isCompanyAdmin' => true
        ];

        $this->client->request(
            'PUT',
            '/api/users/'.$users[User::ROLE_USER.'_A']->getCode(),
            [
                'json' => $newValues3
            ]
        );
        $this->assertResponseStatusCodeSame(403, 'Should be forbidden from altering a user with a higher role');

        $em = $this->getEntityManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->findOneBy(['username'=>$expectedValues['username']]);
        $user->makeBasicUser();
        $em->flush();

        $expectedValues = [
            'username' => $users[User::ROLE_USER.'_A']->getUsername(),
            'email' => $users[User::ROLE_USER.'_A']->getEmail(),
            'fullName' => $newValues3['fname'] . ' ' . $newValues3['lname'],
            'companyAdmin' => true,
            'admin' => false,
            'superAdmin' => false
        ];

        $this->client->request(
            'PUT',
            '/api/users/'.$user->getCode(),
            [
                'json' => $newValues3
            ]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(
            $expectedValues
        );

        $this->logout();

        $em = $this->getEntityManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->findOneBy(['username'=>$expectedValues['username']]);
        $user->makeBasicUser();
        $em->flush();

        $this->login($user, self::PASSWORD);
        $newValues4 = [
            'fname' => 'Fifi4',
            'lname' => 'LaRue4'
        ];

        $expectedValues = [
            'username' => $users[User::ROLE_USER.'_A']->getUsername(),
            'email' => $users[User::ROLE_USER.'_A']->getEmail(),
            'fullName' => $newValues4['fname'] . ' ' . $newValues4['lname'],
            'companyAdmin' => false,
            'admin' => false,
            'superAdmin' => false
        ];

        $this->client->request(
            'PUT',
            '/api/users/'.$users[User::ROLE_COMPANY_ADMIN.'_A']->getCode(),
            [
                'json' => $newValues4
            ]
        );
        $this->assertResponseStatusCodeSame(403);

        $this->client->request(
            'PUT',
            '/api/users/'.$user->getCode(),
            [
                'json' => $newValues4
            ]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(
            $expectedValues
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testDeleteUser()
    {
        /** @var User[] $users */
        $users = $this->makeUsers();
        /** @var Company[] $companies */
        $companies = $this->refreshCompanies($users);

        $deletable = $this->makeDeletableUser(
            $companies['APP'],
            [User::ROLE_SUPER_ADMIN]
        );

        $this->client->request(
            'DELETE',
            '/api/users/'.$deletable->getCode()
        );
        $this->assertResponseStatusCodeSame(401);

        $this->login($users[User::ROLE_SUPER_ADMIN], self::PASSWORD);

        $this->client->request(
            'DELETE',
            '/api/users/'.$deletable->getCode()
        );
        $this->assertResponseStatusCodeSame(204);

        $this->client->request(
            'DELETE',
            '/api/users/'.$deletable->getCode()
        );
        $this->assertResponseStatusCodeSame(404);

        $this->logout();
        $this->login($users[User::ROLE_ADMIN], self::PASSWORD);
        $companies = $this->refreshCompanies($users);
        $deletable = $this->makeDeletableUser(
            $companies['APP'],
            [User::ROLE_SUPER_ADMIN]
        );

        $this->client->request(
            'DELETE',
            '/api/users/'.$deletable->getCode()
        );
        $this->assertResponseStatusCodeSame(403);

        $em = $this->getEntityManager();
        $deletable = $em->getRepository(User::class)->findOneBy(['username'=>$deletable->getUsername()]);
        $deletable->makeAdmin();
        $em->flush();

        $this->client->request(
            'DELETE',
            '/api/users/'.$deletable->getCode()
        );
        $this->assertResponseStatusCodeSame(204);

        $this->logout();
        $this->login($users[User::ROLE_COMPANY_ADMIN.'_A'], self::PASSWORD);
        $companies = $this->refreshCompanies($users);
        $deletable = $this->makeDeletableUser(
            $companies['B'],
            [User::ROLE_ADMIN]
        );

        $this->client->request(
            'DELETE',
            '/api/users/'.$deletable->getCode()
        );
        $this->assertResponseStatusCodeSame(403);

        $em = $this->getEntityManager();
        $deletable = $em->getRepository(User::class)->findOneBy(['username'=>$deletable->getUsername()]);
        $deletable->makeCompanyAdmin();
        $em->flush();

        $this->client->request(
            'DELETE',
            '/api/users/'.$deletable->getCode()
        );
        $this->assertResponseStatusCodeSame(403);

        $companies = $this->refreshCompanies($users);
        $em = $this->getEntityManager();
        $deletable = $em->getRepository(User::class)->findOneBy(['username'=>$deletable->getUsername()]);
        $deletable->setCompany($companies['A']);
        $em->flush();

        $this->client->request(
            'DELETE',
            '/api/users/'.$deletable->getCode()
        );
        $this->assertResponseStatusCodeSame(204);

        $this->logout();
        //A regular user can't delete users at all including their own
        $this->login($users[User::ROLE_USER.'_A'], self::PASSWORD);

        $companies = $this->refreshCompanies($users);
        $deletable = $this->makeDeletableUser(
            $companies['A'],
            [User::ROLE_USER]
        );

        $this->client->request(
            'DELETE',
            '/api/users/'.$deletable->getCode()
        );
        $this->assertResponseStatusCodeSame(403);

        $this->client->request(
            'DELETE',
            '/api/users/'.$users[User::ROLE_USER.'_A']->getCode()
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCompanyUsersSubresource()
    {
        /** @var User[] $users */
        $users = $this->makeUsers();
        /** @var Company[] $companies */
        $companies = $this->refreshCompanies($users);

        $this->client->request(
            'GET',
            '/api/companies/'.$companies['A']->getCode().'/users'
        );
        $this->assertResponseStatusCodeSame(401);

        $this->login($users[User::ROLE_ADMIN], self::PASSWORD);

        $response = $this->client->request(
            'GET',
            '/api/companies/'.$companies['A']->getCode().'/users'
        );
        $this->assertResponseStatusCodeSame(200);

        $this->logout();
        $this->login($users[User::ROLE_COMPANY_ADMIN.'_A'], self::PASSWORD);

        $this->client->request(
            'GET',
            '/api/companies/'.$companies['B']->getCode().'/users'
        );
        $this->assertResponseStatusCodeSame(403);

        $this->client->request(
            'GET',
            '/api/companies/'.$companies['A']->getCode().'/users'
        );
        $this->assertResponseStatusCodeSame(200);
    }

    private function makeDeletableUser(Company $company, array $roles): User
    {
        $em = $this->getEntityManager();
        $newUser = new User();
        $newUser->setCompany($company)
            ->setRoles($roles)
            ->setUsername('delete1')
            ->setEmail('delete1@example.com')
            ->setFname('Joe')
            ->setLname('Blow')
            ->setPassword(self::$container->get(UserPasswordEncoderInterface::class)->encodePassword($newUser, self::PASSWORD));

        $em->persist($newUser);
        $em->flush();

        return $newUser;
    }

    /**
     * @param User[] $users
     * @return array
     */
    public function refreshCompanies(array $users): array
    {
        $em = $this->getEntityManager();
        $companyRepository = $em->getRepository(Company::class);
        $appCompany = $companyRepository->findOneBy(['name' => Company::APP_COMPANY_NAME]);
        $companyA = $companyRepository->findOneBy(['code' => $users[User::ROLE_USER . '_A']->getCompany()->getCode()]);
        $companyB = $companyRepository->findOneBy(['code' => $users[User::ROLE_USER . '_B']->getCompany()->getCode()]);
        return [
            'APP' => $appCompany,
            'A' => $companyA,
            'B' => $companyB
        ];
    }
}