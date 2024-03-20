<?php

namespace App\Mailing;

use App\Config;
use App\Entity\User;
use App\SignedURLGenerator;
use Slim\Interfaces\RouteParserInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\BodyRendererInterface;

class ConfirmationEmail
{
    public function __construct(
        private readonly MailerInterface       $mailer,
        private readonly Config                $config,
        private readonly BodyRendererInterface $renderer,
        private readonly SignedURLGenerator $urlGenerator
    ) {
    }

    public function send(User $user): void
    {
        $to = $user->getEmail();
        $expirationDate = new \DateTime('+10 minutes');

        $activationLink = $this->urlGenerator->fromRoute(
            'activate',
            ['id' => $user->getId(), 'hash' => sha1($to)],
            $expirationDate
        );

        $message = (new TemplatedEmail())
            ->from($this->config->get('mailer.from'))
            ->to($to)
            ->subject('Welcome to PennyTracker')
            ->htmlTemplate('mail' . DIRECTORY_SEPARATOR . 'confirmation_email_template.twig')
            ->context([
                'activationLink' => $activationLink,
                'expirationDate' => $expirationDate->format('m/d/Y g:i a')
            ]);

        $this->renderer->render($message);

        $this->mailer->send($message);
    }

}