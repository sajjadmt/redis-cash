<?php

namespace App\Interface;

interface CacheRepositoryInterface
{

    public function getName(string $cacheName, string $value): ?string;

}