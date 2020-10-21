<?php

namespace App\Tests;


use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\AppointmentType;
use App\Entity\Company;
use App\Entity\User;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ApiTestBaseCase extends ApiTestCase
{
    const PASSWORD = 'Pa$$w0rd';

    protected $client;

    /**
     * @throws DBALException
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
        $this->truncateTables([
            Company::class,
            User::class,
            AppointmentType::class
        ]);
    }

    /**
     * @param array $entities
     * @throws DBALException
     */
    protected function truncateTables(array $entities): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $databasePlatform = $connection->getDatabasePlatform();
        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
        }
        foreach ($entities as $entity) {
            $query = $databasePlatform->getTruncateTableSQL(
                $this->getEntityManager()->getClassMetadata($entity)->getTableName()
            );
            $connection->executeUpdate($query);
        }
        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    protected function getEntityManager()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$container->get(EntityManagerInterface::class);
        return $em;
    }

    /**
     * @param string $name
     * @return Company
     */
    protected function createCompany(string $name)
    {
        $company = new Company();
        $company->setName($name);

        $em = $this->getEntityManager();
        $em->persist($company);
        $em->flush();

        return $company;
    }

    /**
     * @param string $username
     * @param string $email
     * @param string $fname
     * @param string $lname
     * @param string $password
     * @param string $companyName
     * @return User
     */
    protected function createUser(
        string $username,
        string $email,
        string $fname,
        string $lname,
        string $password,
        string $companyName
    )
    {
        /** @var UserPasswordEncoderInterface $passwordEncoder */
        $passwordEncoder = self::$container->get(UserPasswordEncoderInterface::class);
        $em = $this->getEntityManager();
        /** @var Company | null $company */
        $company = $em->getRepository(Company::class)->findOneBy(['name' => $companyName]);
        if (!$company) {
            $company = $this->createCompany($companyName);
        }
        $user = new User();
        $user->setUsername($username)
            ->setEmail($email)
            ->setFname($fname)
            ->setLname($lname)
            ->setCompany($company)
            ->setPassword($passwordEncoder->encodePassword($user, $password));

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * @param User $user
     * @param string $password
     * @throws TransportExceptionInterface
     */
    protected function login(User $user, string $password)
    {
        $this->client->request('POST', '/login', [
            'json' => [
                'username' => $user->getUsername(),
                'password' => $password
            ]
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    /**
     * @param string $username
     * @param string $email
     * @param string $fname
     * @param string $lname
     * @param string $password
     * @param string $companyName
     * @return User
     * @throws TransportExceptionInterface
     */
    protected function createUserAndLogin(
        string $username,
        string $email,
        string $fname,
        string $lname,
        string $password,
        string $companyName
    ): User
    {
        $user = $this->createUser(
            $username,
            $email,
            $fname,
            $lname,
            $password,
            $companyName
        );

        $this->login($user, $password);
        return $user;
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function logout()
    {
        $this->client->request('GET', '/logout');
        $this->assertResponseStatusCodeSame(204);
    }

    protected function makeUsers()
    {
        $superAdmin = $this->createUser(
            'newSuperAdmin',
            'newSuperAdmin@example.com',
            'Richard',
            'Perez',
            self::PASSWORD,
            Company::APP_COMPANY_NAME
        );

        $companyAdminUser = $this->createUser(
            'companyAdminUser',
            'companyAdminUser@example.com',
            'John',
            'Doe',
            self::PASSWORD,
            'Fifi\'s Pets'
        );

        $companyBAdminUser = $this->createUser(
            'companyBAdminUser',
            'companyBAdminUser@example.com',
            'Gretchen',
            'Ratched',
            self::PASSWORD,
            'Company B'
        );

        /** @var UserPasswordEncoderInterface $passwordEncoder */
        $passwordEncoder = self::$container->get(UserPasswordEncoderInterface::class);

        $admin = new User();
        $admin->setUsername('adminUser')
            ->setEmail('adminUser@example.com')
            ->setPassword($passwordEncoder->encodePassword($admin, self::PASSWORD))
            ->setFname('Luis')
            ->setLname('Cordero Guzman')
            ->setCompany($superAdmin->getCompany())
            ->makeAdmin();

        $basicCompanyUser = new User();
        $basicCompanyUser->setUsername('basicCompanyUser')
            ->setEmail('basicCompanyUser@example.com')
            ->setPassword($passwordEncoder->encodePassword($basicCompanyUser, self::PASSWORD))
            ->setFname('Jane')
            ->setLname('Doe')
            ->setCompany($companyAdminUser->getCompany())
            ->makeBasicUser();

        $basicBCompanyUser = new User();
        $basicBCompanyUser->setUsername('basicBCompanyUser')
            ->setEmail('basicBCompanyUser@example.com')
            ->setPassword($passwordEncoder->encodePassword($basicBCompanyUser, self::PASSWORD))
            ->setFname('Otis')
            ->setLname('Jean')
            ->setCompany($companyBAdminUser->getCompany())
            ->makeBasicUser();

        $companyAdminUser->makeCompanyAdmin();
        $companyBAdminUser->makeCompanyAdmin();
        $superAdmin->makeSuperAdmin();

        $em = $this->getEntityManager();
        $em->persist($basicCompanyUser);
        $em->persist($basicBCompanyUser);
        $em->persist($admin);
        $em->flush();

        return [
            User::ROLE_SUPER_ADMIN => $superAdmin,
            User::ROLE_ADMIN => $admin,
            User::ROLE_COMPANY_ADMIN . '_A' => $companyAdminUser,
            User::ROLE_COMPANY_ADMIN . '_B' => $companyBAdminUser,
            User::ROLE_USER . '_A' => $basicCompanyUser,
            User::ROLE_USER . '_B' => $basicBCompanyUser
        ];
    }
}