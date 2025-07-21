# Redis Cache Setup for Symfony Docker Project

## Overview

This Symfony project has been successfully configured to use Redis as the primary caching backend. Redis provides fast, in-memory data storage that greatly improves application performance.

## Configuration

### 1. Docker Services
The project includes a Redis service defined in `docker-compose.yml`:
```yaml
redis:
    image: redis:7-alpine
    container_name: symfony_redis
    ports:
        - "6379:6379"
    networks:
        - symfony_net
```

### 2. PHP Extensions
The PHP container has been configured with the Redis extension in `docker/php/Dockerfile`:
```dockerfile
# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del -f .build-deps
```

### 3. Symfony Configuration
Redis caching is configured in `config/packages/cache.yaml`:
```yaml
framework:
    cache:
        prefix_seed: symfony_docker_app
        app: cache.adapter.redis
        default_redis_provider: redis://redis:6379
        pools:
            cache.custom:
                adapter: cache.adapter.redis
                provider: redis://redis:6379/1
            cache.user_data:
                adapter: cache.adapter.redis
                provider: redis://redis:6379/2
            cache.api_responses:
                adapter: cache.adapter.redis
                provider: redis://redis:6379/3
```

### 4. Environment Variables
The `.env` file contains Redis configuration:
```env
REDIS_URL=redis://redis:6379
CACHE_REDIS_URL=redis://redis:6379
MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages
```

## Usage Examples

### Basic Cache Operations

#### 1. Using Dependency Injection
```php
use Symfony\Contracts\Cache\CacheInterface;

class YourController extends AbstractController
{
    public function yourAction(CacheInterface $cache)
    {
        $data = $cache->get('cache_key', function (ItemInterface $item) {
            $item->expiresAfter(3600); // Cache for 1 hour
            
            // Your expensive operation here
            return $this->expensiveDataOperation();
        });
        
        return $this->json($data);
    }
}
```

#### 2. Setting Cache with TTL
```php
$cache->get('user_profile_' . $userId, function (ItemInterface $item) {
    $item->expiresAfter(1800); // 30 minutes
    return $this->userRepository->find($userId);
});
```

#### 3. Manual Cache Operations
```php
// Set cache
$item = $cache->getItem('my_key');
$item->set(['data' => 'value']);
$item->expiresAfter(3600);
$cache->save($item);

// Get cache
$item = $cache->getItem('my_key');
if ($item->isHit()) {
    $data = $item->get();
}

// Delete cache
$cache->delete('my_key');
```

### Advanced Usage

#### 1. Cache Pools
Use different Redis databases for different purposes:
```php
// In services.yaml
services:
    cache.user_data:
        parent: cache.adapter.redis
        tags:
            - { name: cache.pool }

// In your controller
public function __construct(
    CacheInterface $userDataCache
) {
    $this->userDataCache = $userDataCache;
}
```

#### 2. Cache Tags (with Symfony Cache Component)
```php
$cache->get('tagged_item', function (ItemInterface $item) {
    $item->tag(['user', 'profile']);
    $item->expiresAfter(3600);
    return $data;
});

// Invalidate all items with tag 'user'
$cache->invalidateTags(['user']);
```

## API Endpoints

The project includes several API endpoints to test and manage Redis cache:

### 1. Test Cache Functionality
```bash
GET /cache/test
```
Tests basic cache functionality with a 2-second delay simulation.

### 2. Set Custom Cache
```bash
POST /cache/set
Content-Type: application/x-www-form-urlencoded

key=my_key&value=my_value&ttl=3600
```

### 3. Get Cache by Key
```bash
GET /cache/get/{key}
```

### 4. Delete Cache by Key
```bash
DELETE /cache/delete/{key}
```

### 5. Get Redis Statistics
```bash
GET /cache/stats
```
Returns Redis server information and connection status.

## Performance Best Practices

### 1. Cache Appropriate Data
- Database query results
- API responses
- Computed/processed data
- Session data
- Template fragments

### 2. Set Appropriate TTL
```php
// Short-lived data (5 minutes)
$item->expiresAfter(300);

// Medium-term data (1 hour)
$item->expiresAfter(3600);

// Long-term data (1 day)
$item->expiresAfter(86400);
```

### 3. Use Meaningful Cache Keys
```php
// Good
$cacheKey = "user_profile_{$userId}";
$cacheKey = "product_list_category_{$categoryId}_page_{$page}";

// Bad
$cacheKey = "data";
$cacheKey = "temp";
```

### 4. Cache Invalidation
```php
// Clear specific cache
$cache->delete('user_profile_' . $userId);

// Clear multiple related caches
$keysToDelete = [
    'user_profile_' . $userId,
    'user_settings_' . $userId,
    'user_permissions_' . $userId
];
foreach ($keysToDelete as $key) {
    $cache->delete($key);
}
```

## Monitoring and Debugging

### 1. Check Redis Connection
```bash
docker-compose exec redis redis-cli ping
# Should return: PONG
```

### 2. Monitor Redis Stats
```bash
docker-compose exec redis redis-cli info
```

### 3. View Cache Keys
```bash
docker-compose exec redis redis-cli keys "*"
```

### 4. Monitor Cache Hit/Miss
Use Symfony's profiler in development mode to see cache statistics.

## Common Cache Patterns

### 1. Repository Caching
```php
class UserRepository extends ServiceEntityRepository
{
    public function findUserWithCache(int $id, CacheInterface $cache): ?User
    {
        return $cache->get("user_$id", function (ItemInterface $item) use ($id) {
            $item->expiresAfter(1800);
            return $this->find($id);
        });
    }
}
```

### 2. API Response Caching
```php
public function apiEndpoint(Request $request, CacheInterface $cache)
{
    $cacheKey = 'api_response_' . md5($request->getQueryString());
    
    return $cache->get($cacheKey, function (ItemInterface $item) {
        $item->expiresAfter(600); // 10 minutes
        
        // Your API logic here
        return $this->generateApiResponse();
    });
}
```

### 3. Fragment Caching in Twig
```twig
{# Cache expensive template part #}
{% cache 'product_list_' ~ category.id for 3600 %}
    {% for product in products %}
        {# Expensive rendering logic #}
    {% endfor %}
{% endcache %}
```

## Troubleshooting

### 1. Cache Not Working
- Check Redis connection: `docker-compose exec redis redis-cli ping`
- Verify Redis extension is loaded: `docker-compose exec php php -m | grep redis`
- Check Symfony cache configuration

### 2. Performance Issues
- Monitor Redis memory usage
- Check cache hit rates
- Optimize cache key structure
- Consider cache warming strategies

### 3. Data Consistency
- Implement proper cache invalidation
- Use appropriate TTL values
- Consider cache versioning for critical data

## Development vs Production

### Development
- Lower TTL values for faster debugging
- Enable cache profiling
- Use Redis GUI tools for inspection

### Production
- Higher TTL values for better performance
- Monitor cache hit rates
- Set up Redis clustering if needed
- Configure Redis persistence if required

This setup provides a robust caching solution that will significantly improve your Symfony application's performance!
