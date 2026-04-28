<?php

namespace App\Service;

use App\Entity\User;
use App\Notification\SimpleEmailNotification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

final class NotifierEmailService
{
    public function __construct(private readonly NotifierInterface $notifier)
    {
    }

    public function send(User $user, string $subject, string $content): void
    {
        $email = trim((string) $user->getEmail());

        if ($email === '') {
            return;
        }

        $this->notifier->send(
            new SimpleEmailNotification('admin@example.com', $subject, $content),
            new Recipient($email)
        );
    }
}