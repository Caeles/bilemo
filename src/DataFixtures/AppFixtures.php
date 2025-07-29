<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Customer;
use Faker\Generator;
use App\Entity\User;
use App\Entity\Product;
use DateTimeImmutable;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;
    private Generator $faker;
    
    public function __construct(UserPasswordHasherInterface $userPasswordHasher, Generator $faker)
    {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->faker = $faker;
    }
    
    public function load(ObjectManager $manager): void
    {
        $customers = [];
        for($i = 0; $i < 10; $i++){
            $customer = new Customer();
            $customer->setFirstName($this->faker->firstName);
            $customer->setLastName($this->faker->lastName);
            $customer->setEmail($this->faker->email);
            $customer->setPassword($this->faker->password);
            $customer->setCompanyName($this->faker->company);

            $manager->persist($customer);
            $customers[] = $customer;
        }

    
        for($i = 0; $i < 30; $i++){
            $user = new User();
            $user->setFirstName($this->faker->firstName);
            $user->setLastName($this->faker->lastName);
            $user->setEmail($this->faker->email);
            $user->setPassword($this->faker->password);
            $user->setRole($this->faker->randomElement(['ROLE_USER']));
      
            $randomCustomer = $customers[array_rand($customers)];
            $user->setCustomer($randomCustomer);
            
            $manager->persist($user);
        }
        

        $brands = ['Apple', 'Samsung', 'Xiaomi', 'Huawei', 'Google', 'Sony', 'LG'];
        $colors = ['Noir', 'Blanc', 'Bleu', 'Rouge', 'Vert', 'Or', 'Argent'];
        
        for($i = 0; $i < 30; $i++){
            $product = new Product();
            $product->setName($this->faker->words(3, true) . ' Smartphone');
            $product->setBrand($this->faker->randomElement($brands));
            $product->setModel('Model ' . $this->faker->randomLetter() . $this->faker->numberBetween(1, 20));
            $product->setColor($this->faker->randomElement($colors));
            $product->setPrice((string)$this->faker->numberBetween(299, 1299) . '.99');
            $product->setQuantity((string)$this->faker->numberBetween(1, 100));
            $product->setDescription($this->faker->paragraph(3));
            $product->setCreatedAt(new DateTimeImmutable());
            if($this->faker->boolean(70)) {
                $product->setUpdatedAt(new DateTimeImmutable('-' . $this->faker->numberBetween(1, 30) . ' days'));
            }
            $manager->persist($product);
        }
        $userAdmin = new User();
        $userAdmin->setFirstName('Admin');
        $userAdmin->setLastName('Admin');
        $userAdmin->setEmail('admin@admin.com');
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, 'admin'));
        $userAdmin->setRole('ROLE_ADMIN');
        $manager->persist($userAdmin);
        $manager->flush();
    }
}
