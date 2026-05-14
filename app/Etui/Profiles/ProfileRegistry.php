<?php

declare(strict_types=1);

namespace App\Etui\Profiles;

use InvalidArgumentException;

final class ProfileRegistry
{
    /** @var array<string, ModProfile> */
    private array $profiles = [];

    public function __construct()
    {
        $this->register(new EtMainProfile());
        $this->register(new EtLegacyProfile());
    }

    public function register(ModProfile $profile): void
    {
        $this->profiles[strtolower($profile->slug())] = $profile;
    }

    public function resolve(string $slug): ModProfile
    {
        $key = strtolower(trim($slug));
        if (! isset($this->profiles[$key])) {
            throw new InvalidArgumentException("Unknown ETUI profile slug: '{$slug}'");
        }
        return $this->profiles[$key];
    }

    /** @return string[] */
    public function slugs(): array
    {
        return array_keys($this->profiles);
    }
}
