<?php


namespace App\Tests;


use App\Entity\AppointmentType;
use App\Entity\Company;
use App\Entity\User;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AppointTypeTest extends ApiTestBaseCase
{
    /**
     * @return AppointmentType[]
     */
    private function makeAppointmentTypes(): array
    {
        $durations = [15, 30, 60];
        $appointmentTypes = [];
        $em = $this->getEntityManager();
        $companies = $em->getRepository(Company::class)->findAll();
        if (count($companies) > 0) {
            for ($i = 0; $i < count($companies); $i++) {
                for ($j = 0; $j < count($durations); $j++) {
                    $appointmentType = new AppointmentType();
                    $appointmentType->setName('Name' . $i . $j)
                        ->setDuration($durations[$j])
                        ->setCompany($companies[$i]);

                    $em->persist($appointmentType);
                    $appointmentTypes[] = $appointmentType;
                }
            }

            $em->flush();
        }

        return $appointmentTypes;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testAppointmentTypeCollectionGet()
    {
        $users = $this->makeUsers();
        $appointmentTypes = $this->makeAppointmentTypes();

        $this->client->request('GET', '/api/appointment_types');
        $this->assertResponseStatusCodeSame(401);

        $this->login($users[User::ROLE_COMPANY_ADMIN.'_A'], self::PASSWORD);
        $this->client->request('GET', '/api/appointment_types');
        $this->assertResponseStatusCodeSame(403);
        $this->logout();

        $this->login($users[User::ROLE_USER.'_A'], self::PASSWORD);
        $this->client->request('GET', '/api/appointment_types');
        $this->assertResponseStatusCodeSame(403);
        $this->logout();

        $this->login($users[User::ROLE_ADMIN], self::PASSWORD);
        $this->client->request('GET', '/api/appointment_types');
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['hydra:totalItems' => count($appointmentTypes)]);
        $this->logout();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testAppointmentTypeCollectionPost()
    {
        /** @var User[] $users */
        $users = $this->makeUsers();
        $expectedName = 'TestName';
        $expectedDuration = 15;
        $expectedCompanyCode = $users[User::ROLE_COMPANY_ADMIN.'_A']->getCompany()->getCode();

        $createJson = [
            'name' => $expectedName,
            'duration' => $expectedDuration,
            'company' => '/api/companies/'.$expectedCompanyCode
        ];

        $this->client->request(
            'POST',
            '/api/appointment_types',
            [
                'json' => $createJson
            ]
        );
        $this->assertResponseStatusCodeSame(401);

        $this->login($users[User::ROLE_USER.'_A'], self::PASSWORD);
        $this->client->request(
            'POST',
            '/api/appointment_types',
            [
                'json' => $createJson
            ]
        );
        $this->assertResponseStatusCodeSame(403);
        $this->logout();

        $this->login($users[User::ROLE_COMPANY_ADMIN.'_B'], self::PASSWORD);
        $this->client->request(
            'POST',
            '/api/appointment_types',
            [
                'json' => $createJson
            ]
        );
        $this->assertResponseStatusCodeSame(403);
        $this->logout();

        $this->login($users[User::ROLE_COMPANY_ADMIN.'_A'], self::PASSWORD);
        $this->client->request(
            'POST',
            '/api/appointment_types',
            [
                'json' => $createJson
            ]
        );
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(
            [
                'name' => $expectedName,
                'duration' => $expectedDuration
            ]
        );
        $this->logout();

        $this->login($users[User::ROLE_ADMIN], self::PASSWORD);
        $expectedCompanyCode = $users[User::ROLE_COMPANY_ADMIN.'_B']->getCompany()->getCode();
        $createJson['company'] = '/api/companies/'.$expectedCompanyCode;
        $response = $this->client->request(
            'POST',
            '/api/appointment_types',
            [
                'json' => $createJson
            ]
        );
        $json = $response->toArray(false);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => $expectedName,
            'duration' => $expectedDuration,
            'company' => '/api/companies/'.$expectedCompanyCode
        ]);
        $this->assertArrayHasKey('id', $json);

        unset($createJson['company']);
        $expectedCompanyCode = $users[User::ROLE_ADMIN]->getCompany()->getCode();
        $response = $this->client->request(
            'POST',
            '/api/appointment_types',
            [
                'json' => $createJson
            ]
        );
        $json = $response->toArray(false);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => $expectedName,
            'duration' => $expectedDuration,
            'company' => '/api/companies/'.$expectedCompanyCode
        ]);
        $this->assertArrayHasKey('id', $json);
        $this->logout();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testAppointmentTypeEditItemPut()
    {
        /** @var User[] $users */
        $users = $this->makeUsers();
        $appointmentTypes = $this->makeAppointmentTypes();
        $companyAdminA = $users[User::ROLE_COMPANY_ADMIN.'_A'];
        $companyAdminB = $users[User::ROLE_COMPANY_ADMIN.'_B'];
        $em = $this->getEntityManager();
        $companyAAppointmentTypes = $em
            ->getRepository(AppointmentType::class)
            ->findBy(['company' => $companyAdminA->getCompany()]);

        $companyBAppointmentTypes = $em
            ->getRepository(AppointmentType::class)
            ->findBy(['company' => $companyAdminB->getCompany()]);

        $editJsonBasic = [
            'name' => 'newName',
            'duration' => 10,
            'company' => '/api/companies/'.$companyBAppointmentTypes[0]->getCompany()->getCode()
        ];

        $this->assertGreaterThan( 0, count($companyAAppointmentTypes));
        $this->assertGreaterThan(0, count($companyBAppointmentTypes));

        $this->client->request(
            'PUT',
            '/api/appointment_types/'.$companyAAppointmentTypes[0]->getId(),
            [
                'json' => $editJsonBasic
            ]
        );
        $this->assertResponseStatusCodeSame(401);

        $this->login($users[User::ROLE_USER.'_A'], self::PASSWORD);
        $this->client->request(
            'PUT',
            '/api/appointment_types/'.$companyAAppointmentTypes[0]->getId(),
            [
                'json' => $editJsonBasic
            ]
        );
        $this->assertResponseStatusCodeSame(403);
        $this->logout();

        $this->login($companyAdminB, self::PASSWORD);
        $this->client->request(
            'PUT',
            '/api/appointment_types/'.$companyAAppointmentTypes[0]->getId(),
            [
                'json' => $editJsonBasic
            ]
        );
        $this->assertResponseStatusCodeSame(403);
        $this->logout();

        $this->login($companyAdminA, self::PASSWORD);
        $this->client->request(
            'PUT',
            '/api/appointment_types/'.$companyAAppointmentTypes[0]->getId(),
            [
                'json' => $editJsonBasic
            ]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'id' => $companyAAppointmentTypes[0]->getId(),
            'name' => $editJsonBasic['name'],
            'duration' => $editJsonBasic['duration'],
            'company' => '/api/companies/'.$companyAdminA->getCompany()->getCode()
        ]);
        $this->logout();

        $this->login($users[User::ROLE_ADMIN], self::PASSWORD);
        $this->client->request(
            'PUT',
            '/api/appointment_types/'.$companyAAppointmentTypes[1]->getId(),
            [
                'json' => $editJsonBasic
            ]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(
            [
                'id' => $companyAAppointmentTypes[1]->getId(),
                'name' => $editJsonBasic['name'],
                'duration' => $editJsonBasic['duration'],
                'company' => '/api/companies/'.$companyAdminB->getCompany()->getCode()
            ]
        );
        $this->logout();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testAppointmentTypeViewItemGet()
    {
        $this->makeUsers();
        $appointmentTypes = $this->makeAppointmentTypes();

        $this->client->request(
            'GET',
            '/api/appointment_types/'.($appointmentTypes[count($appointmentTypes)-1]->getId() + 1)
        );
        $this->assertResponseStatusCodeSame(404);

        $this->client->request(
            'GET',
            '/api/appointment_types/'.$appointmentTypes[0]->getId()
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'id' => $appointmentTypes[0]->getId(),
            'name' => $appointmentTypes[0]->getName(),
            'company' => '/api/companies/'.$appointmentTypes[0]->getCompany()->getCode()
        ]);
    }

    public function testAppointmentTypeDeleteItemDelete()
    {
        /** @var User[] $users */
        $users = $this->makeUsers();
        $this->makeAppointmentTypes();
        $em = $this->getEntityManager();
        $companyAdminA = $users[User::ROLE_COMPANY_ADMIN.'_A'];
        $companyAdminB = $users[User::ROLE_COMPANY_ADMIN.'_B'];
        $appointmentTypeRepository = $em->getRepository(AppointmentType::class);
        $appointmentTypeCompanyA = $appointmentTypeRepository->findBy(['company' => $companyAdminA->getCompany()]);
        $appointmentTypeCompanyB = $appointmentTypeRepository->findBy(['company' => $companyAdminB->getCompany()]);

        $this->client->request(
            'DELETE',
            '/api/appointment_types/'.$appointmentTypeCompanyA[0]->getId()
        );
        $this->assertResponseStatusCodeSame(401);

        $this->login($users[User::ROLE_USER.'_A'], self::PASSWORD);
        $this->client->request(
            'DELETE',
            '/api/appointment_types/'.$appointmentTypeCompanyA[0]->getId()
        );
        $this->assertResponseStatusCodeSame(403);
        $this->logout();

        $this->login($companyAdminB, self::PASSWORD);
        $this->client->request(
            'DELETE',
            '/api/appointment_types/'.$appointmentTypeCompanyA[0]->getId()
        );
        $this->assertResponseStatusCodeSame(403);
        $this->logout();

        $this->login($companyAdminA, self::PASSWORD);
        $this->client->request(
            'DELETE',
            '/api/appointment_types/'.$appointmentTypeCompanyA[0]->getId()
        );
        $this->assertResponseStatusCodeSame(204);

        $this->client->request(
            'DELETE',
            '/api/appointment_types/'.$appointmentTypeCompanyA[0]->getId()
        );
        $this->assertResponseStatusCodeSame(404);
        $this->logout();

        $this->login($users[User::ROLE_ADMIN], self::PASSWORD);
        $this->client->request(
            'DELETE',
            '/api/appointment_types/'.$appointmentTypeCompanyB[0]->getId()
        );
        $this->assertResponseStatusCodeSame(204);

        $this->client->request(
            'DELETE',
            '/api/appointment_types/'.$appointmentTypeCompanyB[0]->getId()
        );
        $this->assertResponseStatusCodeSame(404);
        $this->logout();
    }
}