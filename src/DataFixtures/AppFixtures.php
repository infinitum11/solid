<?php

namespace App\DataFixtures;

use App\Entity\BigFootSighting;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder;
    /** @var ObjectManager */
    private $objectManager;
    /** @var Generator */
    private $faker;

    /** @var User[] */
    private $users = [];
    /** @var BigFootSighting[] */
    private $sightings = [];

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $this->objectManager = $manager;
        $this->faker = Factory::create();

        $this->createUsers();
        $this->createSightings();
        $this->createComments();

        $manager->flush();
    }

    private function createUsers()
    {
        $this->users = $this->createMany(10, function() {
            $user = new User();
            $user->setUsername($this->faker->userName);
            $user->setEmail($user->getUsername().'@example.com');
            $user->setPassword(
                $this->passwordEncoder->encodePassword($user, 'believe')
            );

            return $user;
        });
    }

    private function createSightings()
    {
        $this->sightings = $this->createMany(50, function() {
            $sighting = new BigFootSighting();
            $sighting->setOwner($this->users[array_rand($this->users)]);
            $sighting->setTitle($this->faker->realText(80));
            $sighting->setDescription($this->faker->paragraph);
            $sighting->setConfidenceIndex(rand(1, 10));
            $sighting->setLatitude($this->faker->latitude);
            $sighting->setLongitude($this->faker->longitude);
            $sighting->setCreatedAt($this->faker->dateTimeBetween('-6 months', 'now'));

            return $sighting;
        });
    }

    private function createComments()
    {
        $this->createMany(200, function(int $i) {
            $comment = new Comment();
            if ($i % 3 === 0) {
                $comment->setOwner($this->users[array_rand($this->users)]);
            } else {
                // make every 3rd comment done by a single user
                // this is a super user that comments a ton! They
                // must love Bigfoot!
                $comment->setOwner($this->users[0]);
            }
            $comment->setBigFootSighting($this->sightings[array_rand($this->sightings)]);
            $comment->setContent($this->faker->paragraph);
            $comment->setCreatedAt($this->faker->dateTimeBetween(
                $comment->getBigFootSighting()->getCreatedAt(),
                'now'
            ));

            return $comment;
        });
    }

    private function createMany(int $amount, callable $callback)
    {
        $objects = [];
        for ($i = 0; $i < $amount; $i++) {
            $object = $callback($i);
            $this->objectManager->persist($object);

            $objects[] = $object;
        }
        $this->objectManager->flush();

        return $objects;
    }
}
