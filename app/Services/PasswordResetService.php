<?php

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserInterface;
use App\Entity\PasswordReset;
use App\Entity\User2FACode;

class PasswordResetService
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManagerService,
    private readonly HashService $hashService)
    {
    }

    public function generate(string $email): PasswordReset
    {
        $passwordReset = new PasswordReset();

        $passwordReset->setToken(bin2hex(random_bytes(32)));
        $passwordReset->setExpirationDate(new \DateTime('+30 minutes'));
        $passwordReset->setEnabled(true);
        $passwordReset->setEmail($email);

        $this->entityManagerService->sync($passwordReset);

        return $passwordReset;
    }


    public function verify(UserInterface $user, string $code): bool
    {
        /** @var  User2FACode $user2FACode */
        $user2FACode = $this->entityManagerService->getRepository(User2FACode::class)
                                                  ->findOneBy([
                                                      'user'    => $user,
                                                      'code'    => $code,
                                                      'enabled' => true,
                                                  ]);

        if (! $user2FACode) {
            return false;
        }

        $now = new \DateTime();

        if ($user2FACode->getExpirationDate() < $now) {
            return false;
        }

        return true;
    }

    public function deactivatePastTokens(string $email): void
    {
        $this->entityManagerService
            ->getRepository(PasswordReset::class)
            ->createQueryBuilder('pr')
            ->update()
            ->set('pr.enabled', '0')
            ->where('pr.email = :email')
            ->andWhere('pr.enabled = 1')
            ->setParameter('email', $email)
            ->getQuery()
            ->execute();
    }

    public function getByToken(string $token): ?PasswordReset
    {
        return $this->entityManagerService
            ->getRepository(PasswordReset::class)
            ->createQueryBuilder('pr')
            ->select('pr')
            ->where('pr.token = :token')
            ->andWhere('pr.enabled = :enabled')
            ->andWhere('pr.expirationDate > :now')
            ->setParameters(
                [
                    'token'   => $token,
                    'enabled' => true,
                    'now'     => new \DateTime()
                ]
            )
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function changePasswordNoToken(UserInterface $user, string $newPassword): UserInterface
    {
        $user->setPassword($this->hashService->passwordHash($newPassword));

        return $user;
    }

    public function changePasswordWithToken(UserInterface $user, string $newPassword): void
    {
        $this->entityManagerService->wrapInTransaction(function () use($user, $newPassword) {

            $this->deactivatePastTokens($user->getEmail());

            $user = $this->changePasswordNoToken(
                $user,
                $newPassword
            );

            $this->entityManagerService->sync($user);
        });
    }
}