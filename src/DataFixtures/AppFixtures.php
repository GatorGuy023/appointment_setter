<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    const SYSTEM_COMPANY = 'system.company';
    const GENERIC_PASSWORD = 'Pa$$w0rd';
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var FakerFactory
     */
    private $faker;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->faker = FakerFactory::create();
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $this->loadCompanies($manager);
        $this->loadUsers($manager);
    }

    private function loadCompanies(ObjectManager $manager)
    {
        //create the company for our company
        $company = new Company();
        $company->setName(Company::APP_COMPANY_NAME);

        $this->addReference(self::SYSTEM_COMPANY, $company);

        $manager->persist($company);

        for ($i = 0; $i < 3; $i++) {
            $company = new Company();
            $company->setName($this->faker->company);

            $this->addReference("company$i", $company);

            $manager->persist($company);
        }

        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager)
    {
        $user = new User();

        /** @var Company $company */
        $company = $this->getReference(self::SYSTEM_COMPANY);

        $user->setEmail('richard.perez023@gmail.com')
            ->setUsername('rperez')
            ->setCompany($company)
            ->setFname('Richard')
            ->setLname('Perez')
            ->setPassword($this->passwordEncoder->encodePassword($user,self::GENERIC_PASSWORD));

        $manager->persist($user);

        for ($i = 0; $i < 5; $i++) {
            /** @var Company $company */
            $company = $this->getReference('company'.rand(0,2));

            $user = new User();
            $user->setEmail($this->faker->email)
                ->setUsername($this->faker->userName)
                ->setCompany($company)
                ->setFname($this->faker->firstName)
                ->setLname($this->faker->lastName)
                ->setPassword($this->passwordEncoder->encodePassword($user,self::GENERIC_PASSWORD));

            $manager->persist($user);
        }

        $manager->flush();
    }
}
