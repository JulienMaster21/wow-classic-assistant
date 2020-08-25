<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Faker\Factory;

/**
 * Class UserFixtures
 * @package App\DataFixtures
 */
class UserFixtures extends Fixture {

    private Generator $faker;
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder) {

        $this->faker = Factory::create();
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * Load data fixtures with the passed EntityManager
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager) {

        $user = new User();

        $user->setUsername($this->faker->userName);

        $user->setEmail($this->faker->email);

        $user->setPassword($this->passwordEncoder->encodePassword(
            $user,
            $this->faker->password
        ));

        $user->setRoles(['ROLE_USER']);

        $manager->flush();
    }
}
