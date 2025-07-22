<?php

namespace App\Repository;

use App\Interface\CacheRepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;

class CachedNameRepository implements CacheRepositoryInterface
{

    public function __construct(private CacheItemPoolInterface $cacheItemPool)
    {
    }

    public function getName(string $cacheName,string $value): ?string
    {
        $item = $this->cacheItemPool->getItem($cacheName);

        if ($item->isHit()) {
            return $item->get();
        }

        $item->set($value);
        $item->expiresAfter(60);
        $this->cacheItemPool->save($item);

        return $value;
    }

}