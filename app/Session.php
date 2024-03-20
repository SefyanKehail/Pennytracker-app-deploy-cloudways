<?php

namespace App;

use App\Contracts\SessionInterface;
use App\DTO\SessionParamsDTO;
use App\Exceptions\SessionException;

class Session implements SessionInterface
{
    public function __construct(private readonly SessionParamsDTO $options)
    {
    }

    public function start(): bool
    {
        # Session shouldn't be active / headers shouldn't be sent yet
        if ($this->isActive()) {
            throw new SessionException('a session is already active');
        }

        if (headers_sent($filename, $line)) {
            throw new SessionException("headers were already sent by $filename in: $line");
        }

        session_set_cookie_params([
            'secure'   => $this->options->secure,
            'httponly' => $this->options->httpOnly,
            'samesite' => $this->options->sameSite->value,
        ]);

        if (!empty($this->options->name)) {
            session_name($this->options->name);
        }

        return session_start();
    }

    public function save(): void
    {
        session_write_close();
    }

    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function set(string $key, mixed $value = null): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public function forget(array|string $keys): void
    {
        if (is_array($keys)) {
            array_map(function ($k) {
                unset($_SESSION[$k]);
            }, $keys);
            return;
        }
        unset($_SESSION[$keys]);
    }

    public function regenerate(): bool
    {
        return session_regenerate_id();
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public function flash(string $key, array $messages): void
    {
        $_SESSION['flash'][$key] = $messages;
    }

    public function getFlash(string $key): array
    {

        $messages = $_SESSION['flash'][$key]  ?? [];

        unset($_SESSION['flash'][$key]);

        return $messages;
    }
}