<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si déjà connecté, rediriger vers les playlists
        if ($this->getUser()) {
            return $this->redirectToRoute('app_playlist_index');
        }

        // Récupérer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        // Dernier email saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        // Si déjà connecté, rediriger
        if ($this->getUser()) {
            return $this->redirectToRoute('app_playlist_index');
        }

        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));
            $nom = trim($request->request->get('nom', ''));
            $password = $request->request->get('password', '');
            $passwordConfirm = $request->request->get('password_confirm', '');

            // Validations
            $errors = [];

            if (empty($email)) {
                $errors[] = 'L\'email est obligatoire.';
            }
            if (empty($password)) {
                $errors[] = 'Le mot de passe est obligatoire.';
            }
            if ($password !== $passwordConfirm) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }
            if (strlen($password) < 4) {
                $errors[] = 'Le mot de passe doit contenir au moins 4 caractères.';
            }

            // Vérifier si l'email existe déjà
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $errors[] = 'Un compte avec cet email existe déjà.';
            }

            if (!empty($errors)) {
                return $this->render('security/register.html.twig', [
                    'errors' => $errors,
                    'last_email' => $email,
                    'last_nom' => $nom,
                ]);
            }

            // Créer le user
            $user = new User();
            $user->setEmail($email);
            $user->setNom($nom ?: null);
            $user->setRoles(['ROLE_USER']);

            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte créé avec succès ! Connectez-vous maintenant.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'errors' => [],
            'last_email' => '',
            'last_nom' => '',
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Ce contrôleur peut rester vide — il sera intercepté par le firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
