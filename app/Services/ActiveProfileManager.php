<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Session\Session;
use InvalidArgumentException;

class ActiveProfileManager
{
    private const SESSION_KEY = 'active_profile_id';

    public function __construct(
        private readonly Session $session,
    ) {
    }

    public function getActiveProfileId(): ?int
    {
        $value = $this->session->get(self::SESSION_KEY);

        if (!is_numeric($value)) {
            return null;
        }

        $profileId = (int) $value;

        return $profileId > 0 ? $profileId : null;
    }

    public function hasActiveProfile(): bool
    {
        return $this->getActiveProfileId() !== null;
    }

    public function setActiveProfileId(int $profileId): void
    {
        if ($profileId <= 0) {
            throw new InvalidArgumentException('Profile id must be a positive integer.');
        }

        $this->session->put(self::SESSION_KEY, $profileId);
    }

    public function clearActiveProfile(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }
}

