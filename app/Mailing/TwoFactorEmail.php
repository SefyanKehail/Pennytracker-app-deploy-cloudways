<?php

namespace App\Mailing;

use App\Config;
use App\Entity\User;
use App\Services\User2FACodeService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\BodyRendererInterface;

class TwoFactorEmail
{
    public function __construct(
        private readonly MailerInterface       $mailer,
        private readonly Config                $config,
        private readonly BodyRendererInterface $renderer,
        private readonly User2FACodeService $user2FACodeService
    ) {
    }

    public function send(User $user): void
    {
        $to = $user->getEmail();

        // Generate a code if it doesn't exist ( users that didn't enable 2FA yet )
        // Return the same code to be sent if the lifespan is still valid

        $user2FACode = $this->user2FACodeService->generate($user);

        $message = (new TemplatedEmail())
            ->from($this->config->get('mailer.from'))
            ->to($to)
            ->subject('Confirm Your Identity')
            ->htmlTemplate('mail' . DIRECTORY_SEPARATOR . 'two_factor_email_template.twig')
            ->context([
                'twoFactorCode' => $user2FACode->getCode(),
                'expirationDate' => $user2FACode->getExpirationDate()->format('m/d/Y g:i a')
            ]);

        $this->renderer->render($message);

        $this->mailer->send($message);
    }

}