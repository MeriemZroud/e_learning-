<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    private const PENDING_REGISTRATION_KEY = 'pending_registration';
    private const PENDING_REGISTRATION_MAIL_FAILED_KEY = 'pending_registration_mail_failed';

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('base.html.twig');
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('auth/auth.html.twig', [
            'active_tab' => 'login',
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'form_data' => [
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'role' => 'student',
            ],
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        RoleRepository $roleRepository,
        UserRepository $userRepository,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): Response {
        $formData = [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'role' => 'student',
        ];
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_token'))) {
                $error = 'Invalid form submission. Please try again.';
            } else {
                $formData['first_name'] = trim((string) $request->request->get('first_name', ''));
                $formData['last_name'] = trim((string) $request->request->get('last_name', ''));
                $formData['email'] = trim((string) $request->request->get('email', ''));
                $password = (string) $request->request->get('password', '');
                $passwordConfirmation = (string) $request->request->get('password_confirmation', '');
                $formData['role'] = $this->normalizeRole((string) $request->request->get('role', 'student'));

                if ($formData['first_name'] === '' || $formData['last_name'] === '' || $formData['email'] === '' || $password === '') {
                    $error = 'Please fill in all required fields.';
                } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                } elseif ($password !== $passwordConfirmation) {
                    $error = 'Passwords do not match.';
                } elseif ($userRepository->findOneBy(['email' => $formData['email']]) instanceof User) {
                    $error = 'This email is already registered.';
                } else {
                    $verificationCode = (string) random_int(100000, 999999);
                    $expiresAt = time() + 900;

                    $hashedPassword = $passwordHasher->hashPassword((new User())->setEmail($formData['email']), $password);

                    $request->getSession()->set(self::PENDING_REGISTRATION_KEY, [
                        'first_name' => $formData['first_name'],
                        'last_name' => $formData['last_name'],
                        'email' => $formData['email'],
                        'password_hash' => $hashedPassword,
                        'role' => $formData['role'],
                        'code' => $verificationCode,
                        'expires_at' => $expiresAt,
                    ]);

                    // Reset previous email state before attempting a fresh send.
                    $request->getSession()->remove(self::PENDING_REGISTRATION_MAIL_FAILED_KEY);

                    try {
                        $message = (new Email())
                            ->from(new Address('meriemzroud7@gmail.com', 'LearnWay'))
                            ->to($formData['email'])
                            ->subject('Welcome to LearnWay - verification code')
                            ->text(sprintf(
                                "Welcome to LearnWay, %s %s.\n\nYour verification code is: %s\nIt expires in 15 minutes.\n\nIf you did not create this account, you can ignore this email.",
                                $formData['first_name'],
                                $formData['last_name'],
                                $verificationCode
                            ))
                            ->html(sprintf(
                                '<p>Welcome to LearnWay, <strong>%s %s</strong>.</p><p>Your verification code is <strong style="font-size:1.2rem;letter-spacing:0.2em;">%s</strong>.</p><p>This code expires in 15 minutes.</p>',
                                htmlspecialchars($formData['first_name'], ENT_QUOTES),
                                htmlspecialchars($formData['last_name'], ENT_QUOTES),
                                $verificationCode
                            ));

                        $mailer->send($message);
                    } catch (\Throwable $throwable) {
                        $request->getSession()->set(self::PENDING_REGISTRATION_MAIL_FAILED_KEY, true);
                        $logger->error('Registration verification email failed.', [
                            'email' => $formData['email'],
                            'exception' => $throwable,
                        ]);
                    }

                    if ($error === null) {
                        if ($request->getSession()->get(self::PENDING_REGISTRATION_MAIL_FAILED_KEY)) {
                            $this->addFlash('warning', 'Your account has been created, but the email could not be delivered. Use the verification code shown on the next page.');
                        } else {
                            $this->addFlash('success', 'Your account has been created. Check your email for the verification code.');
                        }

                        return $this->redirectToRoute('app_verify_code');
                    }
                }
            }
        }

        return $this->render('auth/auth.html.twig', [
            'active_tab' => 'register',
            'last_username' => '',
            'error' => $error,
            'form_data' => $formData,
        ]);
    }

    #[Route('/verify-code', name: 'app_verify_code', methods: ['GET', 'POST'])]
    public function verifyCode(
        Request $request,
        EntityManagerInterface $entityManager,
        RoleRepository $roleRepository,
    ): Response {
        $pending = $request->getSession()->get(self::PENDING_REGISTRATION_KEY);
        if (!is_array($pending) || !isset($pending['code'], $pending['expires_at'])) {
            $this->addFlash('error', 'Please register first to receive a verification code.');

            return $this->redirectToRoute('app_register');
        }

        $mailFailed = (bool) $request->getSession()->get(self::PENDING_REGISTRATION_MAIL_FAILED_KEY, false);

        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('verify_code', (string) $request->request->get('_token'))) {
                $error = 'Invalid verification request. Please try again.';
            } else {
                $submittedCode = trim((string) $request->request->get('verification_code', ''));

                if (time() > (int) $pending['expires_at']) {
                    $error = 'Your verification code has expired. Please register again.';
                    $request->getSession()->remove(self::PENDING_REGISTRATION_KEY);
                    $request->getSession()->remove(self::PENDING_REGISTRATION_MAIL_FAILED_KEY);
                } elseif ($submittedCode !== (string) $pending['code']) {
                    $error = 'Invalid verification code.';
                } else {
                    $user = new User();
                    $user->setFirstName((string) $pending['first_name']);
                    $user->setLastName((string) $pending['last_name']);
                    $user->setEmail((string) $pending['email']);
                    $user->setPasswordHash((string) $pending['password_hash']);
                    $user->setRole($this->getOrCreateRole($pending['role'], $entityManager, $roleRepository));
                    $user->setIsActive(true);
                    $user->setCreatedAt(new \DateTime());

                    $entityManager->persist($user);
                    $entityManager->flush();
                    $request->getSession()->remove(self::PENDING_REGISTRATION_KEY);
                    $request->getSession()->remove(self::PENDING_REGISTRATION_MAIL_FAILED_KEY);

                    $this->addFlash('success', 'Your account has been verified. You can now log in.');

                    return $this->redirectToRoute('app_login');
                }
            }
        }

        return $this->render('auth/verify.html.twig', [
            'error' => $error,
            'email' => $pending['email'],
            'verification_code' => $mailFailed ? (string) $pending['code'] : null,
            'mail_failed' => $mailFailed,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): Response {
        $formEmail = '';
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('forgot_password_request', (string) $request->request->get('_token'))) {
                $error = 'Invalid request. Please try again.';
            } else {
                $formEmail = trim((string) $request->request->get('email', ''));

                if ($formEmail === '' || !filter_var($formEmail, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                } else {
                    $user = $userRepository->findOneBy(['email' => $formEmail]);

                    if ($user instanceof User && $user->isActive()) {
                        $plainToken = bin2hex(random_bytes(32));
                        $tokenHash = hash('sha256', $plainToken);
                        $expiresAt = new \DateTime('+1 hour');

                        $user->setResetPasswordToken($tokenHash);
                        $user->setResetPasswordExpiresAt($expiresAt);
                        $entityManager->flush();

                        $resetLink = $this->generateUrl('app_reset_password', [
                            'token' => $plainToken,
                        ], UrlGeneratorInterface::ABSOLUTE_URL);

                        try {
                            $message = (new Email())
                                ->from(new Address('meriemzroud7@gmail.com', 'LearnWay'))
                                ->to($user->getEmail())
                                ->subject('LearnWay - Password reset link')
                                ->text(sprintf(
                                    "We received a request to reset your LearnWay password.\n\nUse this link to choose a new password:\n%s\n\nThis link expires in 1 hour.",
                                    $resetLink
                                ))
                                ->html(sprintf(
                                    '<p>We received a request to reset your LearnWay password.</p><p><a href="%s">Click here to choose a new password</a></p><p>This link expires in 1 hour.</p>',
                                    htmlspecialchars($resetLink, ENT_QUOTES)
                                ));

                            $mailer->send($message);
                        } catch (\Throwable $throwable) {
                            $logger->error('Forgot password email failed.', [
                                'email' => $formEmail,
                                'exception' => $throwable,
                            ]);
                        }
                    }

                    $this->addFlash('success', 'If an account exists for this email, a reset link has been sent.');

                    return $this->redirectToRoute('app_login');
                }
            }
        }

        return $this->render('auth/forgot_password.html.twig', [
            'error' => $error,
            'email' => $formEmail,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $tokenHash = hash('sha256', trim($token));
        $user = $userRepository->findOneBy(['reset_password_token' => $tokenHash]);

        if (!$user instanceof User || !$user->getResetPasswordExpiresAt() instanceof \DateTimeInterface || $user->getResetPasswordExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'This reset link is invalid or expired. Please request a new one.');

            return $this->redirectToRoute('app_forgot_password');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_password', (string) $request->request->get('_token'))) {
                $error = 'Invalid reset request. Please try again.';
            } else {
                $password = (string) $request->request->get('password', '');
                $passwordConfirmation = (string) $request->request->get('password_confirmation', '');

                if ($password === '' || $passwordConfirmation === '') {
                    $error = 'Please fill in all required fields.';
                } elseif (strlen($password) < 8) {
                    $error = 'Your password must contain at least 8 characters.';
                } elseif ($password !== $passwordConfirmation) {
                    $error = 'Passwords do not match.';
                } else {
                    $user->setPasswordHash($passwordHasher->hashPassword($user, $password));
                    $user->setResetPasswordToken(null);
                    $user->setResetPasswordExpiresAt(null);
                    $entityManager->flush();

                    $this->addFlash('success', 'Your password has been updated. You can now log in.');

                    return $this->redirectToRoute('app_login');
                }
            }
        }

        return $this->render('auth/reset_password.html.twig', [
            'error' => $error,
        ]);
    }

    private function normalizeRole(string $role): string
    {
        $role = strtolower(trim($role));

        return match ($role) {
            'admin' => 'admin',
            'teacher' => 'teacher',
            default => 'student',
        };
    }

    private function getOrCreateRole(string $roleKey, EntityManagerInterface $entityManager, RoleRepository $roleRepository): Role
    {
        $normalized = $this->normalizeRole($roleKey);
        $roleCategory = $normalized;

        $role = $roleRepository->findOneBy(['role_category' => $roleCategory]);
        if (!$role instanceof Role) {
            $role = $roleRepository->findOneBy(['role_category' => strtoupper($normalized)]);
        }

        if ($role instanceof Role) {
            return $role;
        }

        $role = new Role();
        $role->setName(ucfirst($normalized));
        $role->setRoleCategory($roleCategory);
        $role->setDescription(sprintf('%s account', ucfirst($normalized)));

        $entityManager->persist($role);
        $entityManager->flush();

        return $role;
    }
}