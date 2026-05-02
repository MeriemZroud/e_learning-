<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AdminFaceVerificationSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_ROUTES = [
        'app_admin_face_verify',
        'app_admin_face_check',
        'app_admin_profile',
    ];

    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route', '');
        $pathInfo = $request->getPathInfo();

        if ($route === '' || str_starts_with($route, '_')) {
            return;
        }

        if (!str_starts_with($pathInfo, '/admin')) {
            return;
        }

        if (in_array($route, self::ALLOWED_ROUTES, true)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles, true)) {
            return;
        }

        $session = $request->getSession();
        $isFaceVerified = (bool) $session->get('admin_face_verified', false);
        if ($isFaceVerified) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_admin_face_verify')));
    }
}
