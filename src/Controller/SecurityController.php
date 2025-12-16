<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(
        Request $request,
        AuthenticationUtils $authUtils,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $error = $authUtils->getLastAuthenticationError();
        $lastUsername = $authUtils->getLastUsername();

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('error', 'Email not found.');
            } elseif (!$user->isVerified()) {
                $this->addFlash('error', 'You must verify your email before logging in.');
            } elseif (!$hasher->isPasswordValid($user, $password)) {
                $this->addFlash('error', 'Invalid password.');
            } else {
                // Connecter l’utilisateur manuellement
                $this->container->get('security.token_storage')->setToken(
                    new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
                        $user,
                        $password,
                        (array)'main',
                        $user->getRoles()
                    )
                );
                $request->getSession()->set('_security_main', serialize($this->container->get('security.token_storage')->getToken()));

                return $this->redirectToRoute('app_property_index');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }


    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \Exception('Will be intercepted by firewall');
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response
    {
        if ($this->getUser()) return $this->redirectToRoute('app_property_index');

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $firstName = $request->request->get('first_name');
            $lastName = $request->request->get('last_name');
            $phone = $request->request->get('phone');
            $faceUuid = $request->request->get('face_uuid');


            if (!$email || !$password || !$firstName || !$lastName) {
                $this->addFlash('error', 'All required fields must be filled.');
                return $this->redirectToRoute('app_register');
            }

            if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
                $this->addFlash('error', 'Email already in use.');
                return $this->redirectToRoute('app_register');
            }

            $user = new User();
            $user->setEmail($email);
            $user->setPassword($hasher->hashPassword($user, $password));
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setPhone($phone);
            $user->setVerified(false);
            $user->setFaceId($faceUuid);
            $user->setRoles(['ROLE_USER']);

            // Générer token de vérification
            $token = bin2hex(random_bytes(32));
            $user->setVerificationToken($token);

            $em->persist($user);
            $em->flush();

            // Envoyer email de vérification
            $verificationLink = $this->generateUrl('app_verify_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
            $emailMessage = (new Email())
                ->from('no-reply@yourapp.tn')
                ->to($email)
                ->subject('Verify your email')
                ->html("<p>Hello $firstName,</p><p>Click the link below to verify your account:</p><a href='$verificationLink'>$verificationLink</a>");

            $mailer->send($emailMessage);

            $this->addFlash('success', 'Account created! Please check your email to verify your account.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig');
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(string $token, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Invalid verification token.');
            return $this->redirectToRoute('app_login');
        }

        $user->setVerified(true);
        $user->setVerificationToken(null);
        $em->flush();

        $this->addFlash('success', 'Your email is verified! You can now log in.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('error', 'Email not found.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $token = bin2hex(random_bytes(32));
            $user->setResetToken($token);
            $em->flush();

            $resetLink = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            $emailMessage = (new Email())
                ->from('renthouse@houses.tn')
                ->to($email)
                ->subject('Password Reset')
                ->html("<p>Hello,</p><p>Click the link below to reset your password:</p><a href='$resetLink'>$resetLink</a>");

            $mailer->send($emailMessage);

            $this->addFlash('success', 'Password reset email sent.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(string $token, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            if (!$password) {
                $this->addFlash('error', 'Password required.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            $user->setPassword($hasher->hashPassword($user, $password));
            $user->setResetToken(null);
            $em->flush();

            $this->addFlash('success', 'Password updated! You can log in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', ['token' => $token]);
    }

    #[Route('/face-login', name: 'app_face_login', methods: ['POST'])]
    public function faceLogin(
        Request $request,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage   // ← inject this
    ): JsonResponse {
        try {
            $content = $request->getContent();
            if (empty($content)) {
                return $this->json(['success' => false, 'message' => 'Empty request body'], 400);
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(['success' => false, 'message' => 'Invalid JSON'], 400);
            }

            $faceUuid = $data['face_uuid'] ?? null;
            if (!$faceUuid || !is_string($faceUuid)) {
                return $this->json(['success' => false, 'message' => 'Missing or invalid face_uuid'], 400);
            }

            $user = $em->getRepository(User::class)->findOneBy(['faceId' => $faceUuid]);
            if (!$user) {
                return $this->json(['success' => false, 'message' => 'Face ID not found'], 404);
            }

            if (!$user->isVerified()) {
                return $this->json(['success' => false, 'message' => 'Please verify your email first'], 403);
            }

            // CORRECT WAY in Symfony 6+
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $tokenStorage->setToken($token);

            // Session handling (still needed for persistent login)
            $session = $request->getSession();
            $session->set('_security_main', serialize($token));
            $session->migrate();  // important to regenerate session ID
            $session->save();

            return $this->json([
                'success' => true,
                'message' => 'Face login successful!',
                'redirect' => $this->generateUrl('app_property_index')
            ]);

        } catch (\Exception $e) {
            // Make sure you NEVER return HTML here
            return $this->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

}
