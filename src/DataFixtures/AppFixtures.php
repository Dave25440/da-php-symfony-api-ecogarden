<?php

namespace App\DataFixtures;

use App\Entity\Tip;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

        for ($count = 1; $count <= 20; $count++) { 
            $tip = new Tip();
            $tip->setContent('Conseil ' . $count . ' : Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.');

            $num = rand(1, 3);
            $keys = array_rand($months, $num);

            if ($num === 1) {
                $randMonths = [$months[$keys]];
            } else {
                $randMonths = array_map(fn($key) => $months[$key], $keys);
            }

            $tip->setMonths($randMonths);

            $manager->persist($tip);
        }

        $user = new User();
        $user->setEmail('user@ecogarden.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->hasher->hashPassword($user, 'password'));
        $user->setCity('Marseille');
        $manager->persist($user);

        $admin = new User();
        $admin->setEmail('admin@ecogarden.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'password'));
        $admin->setCity('Paris');
        $manager->persist($admin);

        $manager->flush();
    }
}
