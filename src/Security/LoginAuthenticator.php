<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use App\Service\RecaptchaService;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserRepository $userRepository,
        private readonly RecaptchaService $recaptchaService,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST') && $request->attributes->get('_route') === 'app_login';
    }

    public function authenticate(Request $request): Passport
    {
        $recaptchaResponse = (string) $request->request->get('g-recaptcha-response', '');
        if (!$this->recaptchaService->verify($recaptchaResponse, $request->getClientIp())) {
            throw new CustomUserMessageAuthenticationException('reCAPTCHA verification failed. Please try again.');
        }

        $email = trim((string) $request->request->get('email', ''));
        $password = (string) $request->request->get('password', '');
        $csrfToken = (string) $request->request->get('_token', '');

        return new Passport(
            new \Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge($email, function (string $userIdentifier) {
                $user = $this->userRepository->findOneBy(['email' => $userIdentifier]);

                if (!$user instanceof User) {
                    throw new UserNotFoundException(sprintf('User "%s" not found.', $userIdentifier));
                }

                if (!$user->isActive()) {
                    throw new CustomUserMessageAuthenticationException('Your account is not verified yet. Please enter the verification code first.');
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }

        $roleCategory = strtoupper((string) ($user->getRole()?->getRoleCategory() ?? 'STUDENT'));
        if (!str_starts_with($roleCategory, 'ROLE_')) {
            $roleCategory = 'ROLE_' . $roleCategory;
        }

        $route = match ($roleCategory) {
            'ROLE_ADMIN' => 'app_admin_face_verify',
            'ROLE_TEACHER' => 'app_teacher_dashboard',
            default => 'app_student_dashboard',
        };

        if ($roleCategory === 'ROLE_ADMIN') {
            $request->getSession()->set('admin_face_verified', false);
        }

        return new RedirectResponse($this->urlGenerator->generate($route));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }
}