<?php

namespace App\Mailing;

use App\Config;
use App\Entity\PasswordReset;
use App\Entity\User;
use App\SignedURLGenerator;
use Slim\Interfaces\RouteParserInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\BodyRendererInterface;

class PasswordResetEmail
{
    public function __construct(
        private readonly MailerInterface       $mailer,
        private readonly Config                $config,
        private readonly BodyRendererInterface $renderer,
        private readonly SignedURLGenerator $urlGenerator
    ) {
    }

    public function send(PasswordReset $passwordReset): void
    {
        $to = $passwordReset->getEmail();

        $expirationDate = new \DateTime('+30 minutes');

        $redirectionLink = $this->urlGenerator->fromRoute(
            'resetPassword',
            ['token' => $passwordReset->getToken(), 'hash' => sha1($to)],
            $expirationDate
        );

        $message = (new TemplatedEmail())
            ->from($this->config->get('mailer.from'))
            ->to($to)
            ->subject('Reset Your Password')
            ->htmlTemplate('mail' . DIRECTORY_SEPARATOR . 'password_reset_email_template.twig')
            ->context([
                'redirectionLink' => $redirectionLink,
                'expirationDate' => $expirationDate->format('m/d/Y g:i a')
            ]);

        $this->renderer->render($message);

        $this->mailer->send($message);
    }

}