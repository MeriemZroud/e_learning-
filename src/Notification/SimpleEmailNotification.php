<?php

namespace App\Notification;

use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

final class SimpleEmailNotification extends Notification implements EmailNotificationInterface
{
    public function __construct(
        private readonly string $from,
        private readonly string $subject,
        private readonly string $content,
    ) {
        parent::__construct($subject, ['email']);
        $this->content($content);
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): ?EmailMessage
    {
        $email = (new Email())
            ->from($this->from)
            ->to($recipient->getEmail())
            ->subject($this->subject)
            ->text($this->content);

        $message = new EmailMessage($email);

        if ($transport !== null) {
            $message->transport($transport);
        }

        return $message;
    }
}