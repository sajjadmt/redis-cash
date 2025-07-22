<?php

namespace App\Interface;

interface CacheRepositoryInterface
{

    public function getOrSet(string $key, string $value, int $ttl = 60): string;

}