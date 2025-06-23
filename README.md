# Progressive JSON Streaming for PHP APIs

**TL;DR:** Progressive JSON Streaming sends data incrementally to show users page structure instantly while slow API calls complete in the background.

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue?style=flat-square&logo=php)](https://www.php.net/)[![Tests](https://img.shields.io/github/actions/workflow/status/egyjs/progressive-json-php/php-tests.yml?branch=master&style=flat-square&logo=github&label=Tests)](https://github.com/egyjs/progressive-json-php/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/egyjs/progressive-json-php?style=flat-square&logo=codecov)](https://codecov.io/gh/egyjs/progressive-json-php)
[![Latest Version](https://img.shields.io/packagist/v/egyjs/progressive-json-php?style=flat-square&logo=packagist)](https://packagist.org/packages/egyjs/progressive-json-php)
[![License](https://img.shields.io/github/license/egyjs/progressive-json-php?style=flat-square)](https://github.com/egyjs/progressive-json-php/blob/master/LICENSE)
[![Sponsor on GitHub](https://img.shields.io/badge/Sponsor-❤_GitHub-ff69b4?style=flat-square&logo=github)](https://github.com/sponsors/egyjs)

![Progressive JSON Streamer Demo](/demo-of-progressive-json-streaming.gif)

> **Stream JSON responses progressively to improve user experience.** Send page structure instantly, then fill in slow data as it becomes available. Perfect for dashboards, homepages, and APIs mixing fast cached data with slow database queries.

Perfect for dashboards, homepages, and any API where some data loads fast (cache) and some loads slow (database/external APIs).

## The Problem

```php
// Traditional API: User waits 2000ms to see anything
{
  "user": "...",          // Ready in 50ms 
  "posts": "...",         // Ready in 200ms
  "analytics": "..."      // Takes 2000ms ← Everything waits for this
}
```

## The Solution
```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

// Progressive API: User sees structure immediately, data fills in as ready
$streamer = new ProgressiveJsonStreamer();

$streamer->data([
    'user' => '{$}',        // Placeholder
    'posts' => '{$}',       // Placeholder  
    'analytics' => '{$}'    // Placeholder
]);

$streamer->addPlaceholders([
    'user' => fn() => $cache->get("user_$id"),           // 50ms
    'posts' => fn() => Post::where('user_id', $id)->get(), // 200ms
    'analytics' => fn() => $this->getAnalytics($id)      // 2000ms
]);

return $streamer->asResponse();
```

**Result:** User sees page structure in 50ms, then data appears as it loads.

## Installation

```bash
composer require egyjs/progressive-json-php
```

## Quick Start

### 1. Basic Usage

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer = new ProgressiveJsonStreamer();

// Define structure with placeholders
$streamer->data([
    'profile' => '{$}',
    'posts' => '{$}',
    'notifications' => '{$}'
]);

// Define how to resolve each placeholder
$streamer->addPlaceholders([
    'profile' => fn() => User::find($userId),
    'posts' => fn() => Post::where('user_id', $userId)->get(),
    'notifications' => fn() => $this->getNotifications($userId)
]);

// Stream the response
$streamer->send(); // For pure PHP
// OR
return $streamer->asResponse(); // For Laravel/Symfony
```

### 2. What the Client Receives

```json
// ⚡ Immediate response (structure shows instantly):
{
    "profile": "$profile",
    "posts": "$posts", 
    "notifications": "$notifications"
}

// 🔄 Then data streams in as it's ready:
// The client receives the above in chunks, each starting with /* $key */ followed by the actual data

/* $profile */ 
{"id": 1, "name": "John"}


/* $posts */
[{"id": 1, "title": "Hello World"}]

/* $notifications */
[{"type": "message", "text": "New comment"}]
```

### 3. Frontend Integration

```javascript
async function loadData() {
    const response = await fetch('/api/dashboard');
    const reader = response.body.getReader();
    
    // Parse initial structure
    const initialChunk = await reader.read();
    const structure = JSON.parse(new TextDecoder().decode(initialChunk.value));
    
    // Show loading UI immediately
    updateUI(structure);
    
    // Parse progressive updates
    while (true) {
        const { done, value } = await reader.read();
        if (done) break;
        
        const chunk = new TextDecoder().decode(value);
        if (chunk.includes('/* $')) {
            const [, key, data] = chunk.match(/\/\* \$(\w+) \*\/\n(.+)/s) || [];
            if (key && data) {
                updateSection(key, JSON.parse(data));
            }
        }
    }
}
```

## When to Use

**✅ Good for:**
- Dashboard APIs with multiple data sources
- Homepage APIs mixing cached and database data  
- Any API where some data is fast, some is slow
- Mobile apps (reduces HTTP requests)

**❌ Skip if:**
- All your data loads fast (<100ms)
- Using WebSockets/Server-Sent Events
- Simple APIs with single data source

## Framework Integration

### Laravel

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $streamer = new ProgressiveJsonStreamer();
        
        $streamer->data([
            'user' => '{$}',
            'orders' => '{$}',
            'analytics' => '{$}'
        ]);
        
        $streamer->addPlaceholders([
            'user' => fn() => auth()->user(),
            'orders' => fn() => Order::recent()->get(),
            'analytics' => fn() => $this->analytics->getUserStats()
        ]);
        
        return $streamer->asResponse();
    }
}
```

### Symfony

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

class ApiController extends AbstractController
{
    public function dashboard(): Response
    {
        $streamer = new ProgressiveJsonStreamer();
        
        $streamer->data(['user' => '{$}', 'stats' => '{$}']);
        $streamer->addPlaceholders([
            'user' => fn() => $this->getUser(),
            'stats' => fn() => $this->getStats()
        ]);
        
        return $streamer->asResponse();
    }
```

## API Reference

### Core Methods

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer = new ProgressiveJsonStreamer();

// Set structure with placeholders
$streamer->data(['key' => '{$}']);

// Add single placeholder resolver
$streamer->addPlaceholder('key', fn() => 'value');

// Add multiple placeholder resolvers
$streamer->addPlaceholders([
    'user' => fn() => User::find(1),
    'posts' => fn() => Post::latest()->get()
]);

// Stream response
$streamer->send();                    // Pure PHP
// OR
return $streamer->asResponse();       // Symfony/Laravel
```

#### Configuration Methods


##### `setMaxDepth(int $depth): self`
Set maximum nesting depth for structure walking (default: 50).

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer->setMaxDepth(100);
```

#### Output Methods

##### `stream(): Generator`
Returns a Generator that yields JSON chunks.

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

foreach ($streamer->stream() as $chunk) {
    echo $chunk;
}
```

##### `send(): void`
Streams the response directly to output buffer (for pure PHP).

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer->send(); // Sets headers and streams directly
```

##### `asResponse(): StreamedResponse`
Returns a Symfony `StreamedResponse` for framework integration.

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

return $streamer->asResponse();
```

#### Utility Methods

##### `getPlaceholderKeys(): array`
Get all registered placeholder keys.

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$keys = $streamer->getPlaceholderKeys();
// Returns: ['user.profile', 'user.posts', 'meta.timestamp']
```

##### `hasPlaceholder(string $key): bool`
Check if a placeholder exists.

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

if ($streamer->hasPlaceholder('user.profile')) {
    // Placeholder exists
}
```

##### `removePlaceholder(string $key): self`
Remove a specific placeholder.

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer->removePlaceholder('user.profile');
```

##### `clearPlaceholders(): self`
Remove all placeholders.

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer->clearPlaceholders();
```

##### `getStructure(): array`
Get the current structure template.

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$structure = $streamer->getStructure();
```

---

## 📋 **Common Use Cases**

### **Admin Dashboard**
```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer->data([
    'user' => '{$}',
    'stats' => ['pageviews' => '{$}', 'revenue' => '{$}', 'conversions' => '{$}'],
    'recent_orders' => '{$}',
    'notifications' => '{$}'
]);

$streamer->addPlaceholders([
    'user' => fn() => Cache::get("user_{$userId}"),              // Fast: cached
    'stats.pageviews' => fn() => $this->getPageviews($userId),   // Medium: simple query
    'recent_orders' => fn() => $this->getRecentOrders($userId),  // Medium: simple query
    'stats.revenue' => fn() => $this->calculateRevenue($userId), // Slow: calculations
    'stats.conversions' => fn() => $this->getConversions($userId), // Slow: analytics
    'notifications' => fn() => $this->getNotifications($userId)  // Very slow: external API
]); // See Step 1 above for structure and resolver patterns
```

### **E-commerce Product Page**

```php
<?php

use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer = new ProgressiveJsonStreamer();

$streamer->data([
    'product' => '{$}',              // Core product info
    'inventory' => '{$}',            // Stock levels
    'pricing' => '{$}',              // Dynamic pricing
    'reviews' => '{$}',              // Customer reviews
    'recommendations' => '{$}',      // ML recommendations
    'related_products' => '{$}'      // Related items
]);

$streamer->addPlaceholders([...]); // Similar pattern: fast cached data → complex queries → ML/external APIs
```

### **Social Media Feed**
```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer->data([
    'user_profile' => '{$}',         // User info
    'main_feed' => '{$}',            // Latest posts
    'trending' => '{$}',             // Trending topics
    'ads' => '{$}',                  // Targeted ads
    'people_suggestions' => '{$}'    // Friend suggestions
]);

$streamer->addPlaceholders([...]); // Pattern: profile cache → posts query → ML recommendations
```

---

## 🎯 **Advanced Features**

### **Error Handling**

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer->addPlaceholder('risky_data', function() {
    // Validate permissions
    if (!$this->user->canAccess('sensitive_data')) {
        throw new UnauthorizedException('Access denied');
    }
    
    try {
        return $this->expensiveOperation();
    } catch (Exception $e) {
        throw new ProcessingException('Failed: ' . $e->getMessage());
    }
});
```

Errors are automatically serialized to JSON:
```json
/* $data */
{
    "error": true,
    "key": "data",
    "message": "Failed: Connection timeout",
    "type": "ProcessingException"
}
```

## Advanced Usage

### Performance Optimization

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

// Order resolvers by speed (fast → slow)
$streamer->addPlaceholders([
    'cached' => fn() => Cache::get('data'),          // ~1ms
    'simple' => fn() => DB::table('users')->count(), // ~10ms  
    'complex' => fn() => $this->analytics(),         // ~1000ms
    'external' => fn() => $this->apiCall()           // ~2000ms
]);
```

### Security

```php
$streamer->addPlaceholder('sensitive', function() {
    // Validate permissions
    if (!$this->user->hasRole('admin')) {
        throw new UnauthorizedException();
    }
    
    // Rate limiting
    $key = "rate_limit:{$this->user->id}";
    if (Cache::get($key, 0) > 100) {
        throw new TooManyRequestsException();
    }
    Cache::increment($key, 1, 3600);
    
    return $this->getSensitiveData();
});
```

### HTTP Headers

The library automatically sets streaming-optimized headers:

```http
Content-Type: application/x-json-stream
Cache-Control: no-cache, no-store, must-revalidate
Connection: keep-alive
X-Accel-Buffering: no
X-Content-Type-Options: nosniff
```

## Common Use Cases

### Dashboard API
```php
$streamer->data([
    'user' => '{$}',
    'metrics' => '{$}',
    'alerts' => '{$}'
]);

$streamer->addPlaceholders([
    'user' => fn() => Cache::get("user_$id"),      // Fast
    'metrics' => fn() => $this->getMetrics($id),   // Medium
    'alerts' => fn() => $this->getAlerts($id)      // Slow
]);
```

### E-commerce Product Page
```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer->data([
    'product' => '{$}',
    'inventory' => '{$}',
    'reviews' => '{$}',
    'recommendations' => '{$}'
]);

$streamer->addPlaceholders([
    'product' => fn() => Product::find($id),                    // Fast
    'inventory' => fn() => $this->inventory->getStock($id),     // Medium
    'reviews' => fn() => Review::where('product_id', $id)->get(), // Medium
    'recommendations' => fn() => $this->ml->recommend($id)       // Slow
]);
```

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Stream cuts off early | Call `ob_end_clean()` before streaming |
| Memory errors | Use pagination in resolvers |
| Timeout errors | Increase `max_execution_time` |
| CORS issues | Set CORS headers before streaming |
| Parsing fails | Validate JSON in resolvers |

### Debug Mode

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer->addPlaceholder('debug', fn() => [
    'memory' => memory_get_usage(true),
    'time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
]);
```

---

## 📚 **Resources & Inspiration**

- **Concept Origin:** [Dan Abramov's Progressive JSON](https://overreacted.io/progressive-json/)
- **React Server Components:** Uses the same streaming pattern
- **Similar Concepts:** Progressive JPEG loading, HTTP/2 Server Push
- **Use Cases:** Netflix UI, Facebook feeds, Google search results

---

## 🧪 Testing

This library comes with comprehensive PHPUnit tests to ensure reliability and maintainability.

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage report
composer test:coverage

# Run tests with readable output
composer test:watch

# Direct PHPUnit commands
vendor/bin/phpunit
vendor/bin/phpunit --testdox
vendor/bin/phpunit --coverage-text
```

### Test Coverage

The test suite includes:
- ✅ Basic functionality tests
- ✅ Error handling and edge cases
- ✅ Nested structure handling
- ✅ Stream generation and output
- ✅ Symfony integration tests
- ✅ Configuration and validation tests
- ✅ **Laravel installation tests** (v9.x - v12.x)

Coverage reports are generated in `build/coverage-html/` when running with coverage.

### Laravel Installation Tests

We provide comprehensive installation tests for Laravel versions 9.x through 12.x:

```bash
# Run Laravel installation tests locally
./scripts/test-laravel-installation.sh

# Windows users
scripts\test-laravel-installation.bat
```

These tests verify:
- Package installation via Composer
- Autoloading and integration with Laravel
- Service provider registration
- Route context functionality
- Performance and clean removal

See [`docs/LARAVEL_INSTALLATION_TESTS.md`](docs/LARAVEL_INSTALLATION_TESTS.md) for detailed information.

### Continuous Integration

GitHub Actions automatically runs tests on:
- PHP 8.1, 8.2, 8.3, and 8.4
- Laravel 9.x, 10.x, 11.x, and 12.x combinations
- Push and Pull Request events
- Multiple operating systems
- Weekly scheduled runs

---

## 🤝 Contributing

We welcome contributions from everyone! Please read our [Contributing Guide](CONTRIBUTING.md) for detailed information on how to get started.

**Quick Start:**
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/name`
3. Commit changes: `git commit -m 'Add feature'`
4. Push to branch: `git push origin feature/name`
5. Open a Pull Request

**Important:**
- Read our [Code of Conduct](CODE_OF_CONDUCT.md) 
- Follow our [Contributing Guidelines](CONTRIBUTING.md)
- Include tests for new features
- Update documentation as needed

For detailed setup instructions, coding standards, and development workflow, see [CONTRIBUTING.md](CONTRIBUTING.md).

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

## Author

**AbdulRahman El-zahaby (@egyjs)**  
📧 el3zahaby@gmail.com  
🐙 GitHub: [@egyjs](https://github.com/egyjs)

---

## 🙏 Acknowledgments

- Symfony HttpFoundation for streaming response utilities
- The PHP community for feedback and contributions

---

*Made with ❤️ by [egyjs](https://github.com/egyjs)  for the PHP community*
