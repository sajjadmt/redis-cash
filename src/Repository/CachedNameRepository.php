<?php

namespace App\Repository;

use App\Interface\CacheRepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedNameRepository implements CacheRepositoryInterface
{

    public function __construct(private CacheItemPoolInterface $cacheItemPool)
    {
    }

    public function getOrSet(string $key, string $value, int $ttl = 60): string
    {
        return $this->cacheItemPool->get($key, function (ItemInterface $item) use ($value, $ttl) {
            $item->expiresAfter($ttl);
            return $value;
        });
    }

}