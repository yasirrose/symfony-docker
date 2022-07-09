<?php
namespace App\Controller;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class RegistrationController extends AbstractController
{

    public function createUser(UserPasswordHasherInterface $passwordHasher, ManagerRegistry $doctrine, Request $request): Response
    { 
        $payload = json_decode($request->getContent(), true);
        $email = $payload['email'];
        $password = $payload['password'];
        $roles = $payload['roles'];
        $user = new User();
        $user->setEmail($email);

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $password
        );

        $user->setPassword($hashedPassword);
        $user->setRoles($roles);

        // and save user in db
        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();

        return $this->json([
            'message'  => 'user created successfully',
        ]);
    }
}