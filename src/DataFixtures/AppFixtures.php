<?php

namespace App\DataFixtures;

use App\Entity\AppointmentType;
use App\Entity\Company;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
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

    private $companyAppointmentTypes = [];

    /**
     * @var FakerFactory
     */
    private $faker;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->faker = FakerFactory::create();
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @param ObjectManager $manager
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        $this->loadCompanies($manager);
        $this->loadUsers($manager);
        $this->loadAppointmentTypes($manager);
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
            $this->companyAppointmentTypes[$company->getCode()] = 0;
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @throws Exception
     */
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
            $company = $this->getRandomCompanyReference();

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

    /**
     * @param ObjectManager $manager
     * @throws Exception
     */
    public function loadAppointmentTypes(ObjectManager $manager)
    {
        for ($i = 0; $i < 9; $i++)
        {
            $company = $this->getRandomCompanyReference();
            $appointmentType = new AppointmentType();
            $appointmentType->setName($this->faker->word)
                ->setDuration($this->faker->randomElement([15,30,60]))
                ->setCompany($company);

            $manager->persist($appointmentType);
            $this->setReference(
                'appointmentType'.
                $company->getCode().
                $this->companyAppointmentTypes[$company->getCode()],
                $appointmentType
            );
            $this->companyAppointmentTypes[$company->getCode()]++;
        }

        $manager->flush();
    }

    /**
     * @return Company
     * @throws Exception
     */
    public function getRandomCompanyReference(): Company
    {
        /** @var Company $company */
        $company = $this->getReference('company'.random_int(0, 2));
        return $company;
    }

    /**
     * @param Company $company
     * @return AppointmentType|null
     * @throws Exception
     */
    public function getRandomAppointmentType(Company $company): ?AppointmentType
    {
        $maxAppointments = $this->companyAppointmentTypes[$company->getCode()] - 1;

        if ($maxAppointments < 0) {
            return null;
        }

        /** @var AppointmentType $appointmentType */
        $appointmentType = $this->getReference('appointmentType'.$company->getCode().random_int(0, $maxAppointments));
        return $appointmentType;
    }
}
