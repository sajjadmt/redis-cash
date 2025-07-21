<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheController extends AbstractController
{
    #[Route('/cache/test', name: 'cache_test', methods: ['GET'])]
    public function testCache(CacheInterface $cache): JsonResponse
    {
        // Test cache functionality
        $cacheKey = 'test_cache_key';
        
        $cachedData = $cache->get($cacheKey, function (ItemInterface $item) {
            // Set cache expiration time (5 minutes)
            $item->expiresAfter(300);
            
            // Simulate expensive operation
            sleep(2);
            
            return [
                'message' => 'This data was cached!',
                'timestamp' => time(),
                'expensive_data' => range(1, 1000)
            ];
        });
        
        return $this->json([
            'success' => true,
            'data' => $cachedData,
            'cache_info' => 'Data retrieved from Redis cache'
        ]);
    }
    
    #[Route('/cache/set', name: 'cache_set', methods: ['POST'])]
    public function setCache(Request $request, CacheInterface $cache): JsonResponse
    {
        $key = $request->request->get('key', 'default_key');
        $value = $request->request->get('value', 'default_value');
        $ttl = (int) $request->request->get('ttl', 3600); // Default 1 hour
        
        $cache->delete($key); // Clear existing cache
        
        $cachedValue = $cache->get($key, function (ItemInterface $item) use ($value, $ttl) {
            $item->expiresAfter($ttl);
            return [
                'value' => $value,
                'cached_at' => time()
            ];
        });
        
        return $this->json([
            'success' => true,
            'message' => 'Cache set successfully',
            'key' => $key,
            'cached_data' => $cachedValue
        ]);
    }
    
    #[Route('/cache/get/{key}', name: 'cache_get', methods: ['GET'])]
    public function getCache(string $key, CacheInterface $cache): JsonResponse
    {
        try {
            $item = $cache->getItem($key);
            
            if (!$item->isHit()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Cache key not found',
                    'key' => $key
                ], 404);
            }
            
            return $this->json([
                'success' => true,
                'key' => $key,
                'data' => $item->get(),
                'message' => 'Data retrieved from cache'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error retrieving cache: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/cache/delete/{key}', name: 'cache_delete', methods: ['DELETE'])]
    public function deleteCache(string $key, CacheInterface $cache): JsonResponse
    {
        $success = $cache->delete($key);
        
        return $this->json([
            'success' => $success,
            'message' => $success ? 'Cache deleted successfully' : 'Cache key not found',
            'key' => $key
        ]);
    }
    
    #[Route('/cache/stats', name: 'cache_stats', methods: ['GET'])]
    public function getCacheStats(): JsonResponse
    {
        try {
            // Try to connect to Redis to get stats
            $redis = new \Predis\Client([
                'scheme' => 'tcp',
                'host'   => 'redis',
                'port'   => 6379,
            ]);
            
            $info = $redis->info();
            
            return $this->json([
                'success' => true,
                'redis_info' => [
                    'version' => $info['Server']['redis_version'] ?? 'unknown',
                    'connected_clients' => $info['Clients']['connected_clients'] ?? 0,
                    'used_memory' => $info['Memory']['used_memory_human'] ?? 'unknown',
                    'total_keys' => $redis->dbsize(),
                ],
                'message' => 'Redis connection successful'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Redis connection failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
