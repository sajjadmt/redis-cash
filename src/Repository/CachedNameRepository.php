<?php

namespace App\Repository;

use Psr\Cache\CacheItemPoolInterface;

class CachedNameRepository
{
    private CacheItemPoolInterface $cacheItemPool;

    public function __construct(CacheItemPoolInterface $cacheItemPool)
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    public function getName(): ?string
    {
        $item = $this->cacheItemPool->getItem('name');
        return $item->isHit() ? $item->get() : null;
    }

    public function saveName(string $name): void
    {
        $item = $this->cacheItemPool->getItem('name');
        $item->set($name);
        $item->expiresAfter(60);
        $this->cacheItemPool->save($item);
    }

}