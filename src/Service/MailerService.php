<?php
namespace App\Service;

use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class MailerService
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function send(string $to, string $subject, string $templateTwig, array $context): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('contact@btdconsulting.fr', 'Btd Consulting'))
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($templateTwig)
            ->context($context);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw $e;
        }
    }
}
