# Progressive JSON Streamer for PHP

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue?style=flat-square&logo=php)](https://www.php.net/)[![Tests](https://img.shields.io/github/actions/workflow/status/egyjs/progressive-json-php/php-tests.yml?branch=master&style=flat-square&logo=github&label=Tests)](https://github.com/egyjs/progressive-json-php/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/egyjs/progressive-json-php?style=flat-square&logo=codecov)](https://codecov.io/gh/egyjs/progressive-json-php)
[![Latest Version](https://img.shields.io/packagist/v/egyjs/progressive-json-php?style=flat-square&logo=packagist)](https://packagist.org/packages/egyjs/progressive-json-php)
[![Downloads](https://img.shields.io/packagist/dt/egyjs/progressive-json-php?style=flat-square&logo=packagist)](https://packagist.org/packages/egyjs/progressive-json-php)
[![License](https://img.shields.io/github/license/egyjs/progressive-json-php?style=flat-square)](https://github.com/egyjs/progressive-json-php/blob/master/LICENSE)
[![Sponsor on GitHub](https://img.shields.io/badge/Sponsor-❤_GitHub-ff69b4?style=flat-square&logo=github)](https://github.com/sponsors/egyjs)

![Progressive JSON Streamer](/demo-of-progressive-json-streaming.gif)

A powerful PHP library for streaming large or dynamic JSON responses progressively, with support for lazy-evaluated placeholders and real-time data delivery. Perfect for APIs with expensive operations, large datasets, or any scenario where you want to send partial JSON results before all data is ready.

---

Progressive JSON allows you to stream a base JSON object immediately, while progressively filling in placeholders as data becomes available (database calls, API responses, background work).

---

## ✨ Features

- **🚀 Progressive JSON streaming**: Send an initial JSON structure, then stream data for placeholders as it becomes available
- **⚡ Lazy evaluation**: Placeholders are resolved only when streamed, supporting expensive or asynchronous operations
- **🔗 Dot notation**: Use dot notation to target nested placeholders in your JSON structure
- **🎛️ Customizable**: Set your own placeholder marker and maximum nesting depth
- **🔧 Framework-friendly**: Works with pure PHP or frameworks like Symfony and Laravel (via `StreamedResponse`)
- **❌ Robust error handling**: Streams error info for failed placeholders without breaking the stream
- **📊 Memory efficient**: Stream large datasets without loading everything into memory at once

---

## 📦 Installation

```bash
composer require egyjs/progressive-json-php
```

---

## 🚀 Quick Start

### Basic Example (Pure PHP)

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer = new ProgressiveJsonStreamer();

// Define structure with placeholders
$streamer->data([
    'message' => '{$}',
    'status' => '200',
    'items' => '{$}',
    'nested' => [
        'nested1' => '{$}',
        'nested2' => '{$}',
        'nested3' => 'some static value'
    ],
]);

// Register resolvers for placeholders
$streamer->addPlaceholders([
    'message' => fn() => 'fast message',
    'items' => fn() => [
        ['id' => 1, 'name' => 'admin'],
        ['id' => 2, 'name' => 'ahmed'],
        ['id' => 3, 'name' => 'Karem']
    ],
    'nested.nested1' => fn() => 'nested value 1',
    'nested.nested2' => fn() => 'nested value 2',
]);

// Stream the response
$streamer->send();
```

### Symfony/Laravel Example

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

class ApiController
{
    public function progressiveData()
    {
        $streamer = new ProgressiveJsonStreamer();
        
        $streamer->data([
            'users' => '{$}',
            'meta' => [
                'total' => '{$}',
                'processed_at' => '{$}'
            ]
        ]);
        
        $streamer->addPlaceholders([
            'users' => fn() => $this->getUsers(), // Expensive DB query
            'meta.total' => fn() => $this->getUserCount(),
            'meta.processed_at' => fn() => date('Y-m-d H:i:s')
        ]);
        
        return $streamer->asResponse(); // Returns Symfony StreamedResponse
    }
}
```

---

## 📤 Output Format

The streamer produces a progressive output with the initial structure followed by resolved data chunks:

### Example Output

```bash
{
    "message": "$message",
    "status": "200",
    "items": "$items",
    "nested": {
        "nested1": "$nested.nested1",
        "nested2": "$nested.nested2",
        "nested3": "some static value"
    }
}
/* $message */
"fast message"
/* $items */
[
    {
        "id": 1,
        "name": "admin"
    },
    {
        "id": 2,
        "name": "ahmed"
    },
    {
        "id": 3,
        "name": "Karem"
    }
]
/* $nested.nested1 */
"nested value 1"
/* $nested.nested2 */
"nested value 2"
```

### How It Works (Simple Explanation) 🤓

Remember how **Progressive JPEGs** load? Instead of showing top-to-bottom, they start fuzzy and get crisp! 🖼️

This library does the same thing but for **JSON data**:

#### 🚀 The Problem with Regular JSON
```php
// Traditional way - everything waits for the slowest part
{
  "fast_data": "...",     // ✅ Ready in 10ms
  "slow_data": "...",     // ⏰ Takes 2 seconds 
  "more_data": "..."      // ✅ Ready in 50ms but BLOCKED!
}
// Client gets NOTHING until all 2+ seconds pass 😢
```

#### ⚡ Progressive JSON Solution
```bash
// 1. Structure shows up IMMEDIATELY (like a preview)
{
  "fast_data": "$fast_data",     // 🔗 Placeholder reference
  "slow_data": "$slow_data",     // 🔗 Will load later
  "more_data": "$more_data"      // 🔗 Can load independently
}

// 2. Data streams in as it's ready (any order!)
/* $fast_data */
"Here's the quick stuff!"

/* $more_data */
"This loaded while slow_data was still thinking..."

/* $slow_data */
"Finally! The slow database query finished!"
```

#### 🎯 Think of it like a Website Loading:
- **Header/Footer** load instantly
- **Main content** loads when database responds  
- **Comments section** loads when API call finishes
- **Ads** load whenever (hopefully never 😄)

Each piece shows up when ready, instead of everything waiting for the slowest part!

**Why This Rocks for Modern Apps:** 🚀
- **⚡ Instant Response**: Users see structure immediately
- **🔄 No Blocking**: Fast data doesn't wait for slow data
- **💪 Resilient**: One failed piece doesn't break everything  
- **📱 Perfect for SPAs**: Update UI progressively as data arrives

---

## 💡 Inspiration & Theory

This library is inspired by [Dan Abramov's Progressive JSON concept](https://overreacted.io/progressive-json/) - the same pattern used in React Server Components! 

### The Core Innovation 🧠

Instead of sending data **depth-first** (waiting for everything):
```php
// BAD: Everything waits for comments to load
{
  "header": "Welcome to my blog",
  "post": {
    "content": "This is my article", 
    "comments": [/* WAIT 3 SECONDS FOR DATABASE */]
  },
  "footer": "Thanks for reading!"  // This waits too! 😢
}
```

We send data **breadth-first** (stream what's ready):
```php
// GOOD: Send structure immediately, fill in pieces
{
  "header": "Welcome to my blog",
  "post": "$post_data",           // 🔗 Will resolve later
  "footer": "Thanks for reading!" // ✅ Shows immediately!
}
/* $post_data */
{
  "content": "This is my article",
  "comments": "$comments"         // 🔗 Still loading...
}
/* $comments */
[
  "Great article!",
  "Thanks for sharing!"
]
```

This is exactly how **React Server Components** work under the hood - but now you can use the same pattern in your PHP APIs! 🚀

---

## 🔧 API Reference

### `ProgressiveJsonStreamer`

#### Core Methods

##### `data(array $structure): self`
Set the JSON structure template with placeholders.

```php
$streamer->data([
    'user' => [
        'profile' => '{$}',
        'posts' => '{$}'
    ]
]);
```

##### `addPlaceholder(string $key, callable $resolver): self`
Add a single resolver for a placeholder using dot notation.

```php
$streamer->addPlaceholder('user.profile', function() {
    return ['name' => 'John', 'email' => 'john@example.com'];
});
```

##### `addPlaceholders(array $placeholders): self`
Add multiple resolvers at once.

```php
$streamer->addPlaceholders([
    'user.profile' => fn() => getUserProfile(),
    'user.posts' => fn() => getUserPosts(),
    'meta.timestamp' => fn() => time()
]);
```

#### Configuration Methods


##### `setMaxDepth(int $depth): self`
Set maximum nesting depth for structure walking (default: 50).

```php
$streamer->setMaxDepth(100);
```

#### Output Methods

##### `stream(): Generator`
Returns a Generator that yields JSON chunks.

```php
foreach ($streamer->stream() as $chunk) {
    echo $chunk;
}
```

##### `send(): void`
Streams the response directly to output buffer (for pure PHP).

```php
$streamer->send(); // Sets headers and streams directly
```

##### `asResponse(): StreamedResponse`
Returns a Symfony `StreamedResponse` for framework integration.

```php
return $streamer->asResponse();
```

#### Utility Methods

##### `getPlaceholderKeys(): array`
Get all registered placeholder keys.

```php
$keys = $streamer->getPlaceholderKeys();
// Returns: ['user.profile', 'user.posts', 'meta.timestamp']
```

##### `hasPlaceholder(string $key): bool`
Check if a placeholder exists.

```php
if ($streamer->hasPlaceholder('user.profile')) {
    // Placeholder exists
}
```

##### `removePlaceholder(string $key): self`
Remove a specific placeholder.

```php
$streamer->removePlaceholder('user.profile');
```

##### `clearPlaceholders(): self`
Remove all placeholders.

```php
$streamer->clearPlaceholders();
```

##### `getStructure(): array`
Get the current structure template.

```php
$structure = $streamer->getStructure();
```

---

## 🎯 Advanced Usage Examples

### Database Query Optimization

```php
<?php
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

$streamer = new ProgressiveJsonStreamer();

$streamer->data([
    'users' => '{$}',
    'categories' => '{$}',
    'statistics' => [
        'total_users' => '{$}',
        'active_users' => '{$}',
        'revenue' => '{$}'
    ]
]);

$streamer->addPlaceholders([
    // Fast queries first
    'statistics.total_users' => fn() => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    
    // Slower queries
    'users' => function() use ($db) {
        sleep(2); // Simulate slow query
        return $db->query("SELECT * FROM users LIMIT 100")->fetchAll();
    },
    
    'categories' => function() use ($db) {
        sleep(1); // Another slow operation
        return $db->query("SELECT * FROM categories")->fetchAll();
    },
    
    // Very expensive calculations
    'statistics.active_users' => function() use ($analytics) {
        return $analytics->calculateActiveUsers(); // Complex calculation
    },
    
    'statistics.revenue' => function() use ($billing) {
        return $billing->calculateMonthlyRevenue(); // API call
    }
]);

$streamer->send();
```

### Error Handling Example

```php
<?php
$streamer = new ProgressiveJsonStreamer();

$streamer->data([
    'working_data' => '{$}',
    'failing_data' => '{$}',
    'more_data' => '{$}'
]);

$streamer->addPlaceholders([
    'working_data' => fn() => ['status' => 'success'],
    
    'failing_data' => function() {
        throw new Exception('Something went wrong!');
    },
    
    'more_data' => fn() => ['continues' => 'after error']
]);

$streamer->send();
```

**Output with Error:**
```json
{
    "working_data": "$working_data",
    "failing_data": "$failing_data",
    "more_data": "$more_data"
}
/* $working_data */
{
    "status": "success"
}
/* $failing_data */
{
    "error": true,
    "key": "failing_data",
    "message": "Something went wrong!",
    "type": "Exception"
}
/* $more_data */
{
    "continues": "after error"
}
```

### Real-time Data Streaming

```php
<?php
$streamer = new ProgressiveJsonStreamer();

$streamer->data([
    'live_metrics' => [
        'cpu_usage' => '{$}',
        'memory_usage' => '{$}',
        'disk_usage' => '{$}'
    ],
    'logs' => '{$}'
]);

$streamer->addPlaceholders([
    'live_metrics.cpu_usage' => fn() => exec('top -bn1 | grep "Cpu(s)"'),
    'live_metrics.memory_usage' => fn() => exec('free -m'),
    'live_metrics.disk_usage' => fn() => exec('df -h'),
    'logs' => function() {
        // Stream last 100 lines of log file
        return array_slice(file('/var/log/app.log'), -100);
    }
]);
```

---

## 🌐 Framework Integration

### Laravel Integration

```php
<?php
// In a Laravel Controller
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

class DataController extends Controller
{
    public function progressiveData(Request $request)
    {
        $streamer = new ProgressiveJsonStreamer();
        
        $streamer->data([
            'users' => '{$}',
            'permissions' => '{$}',
            'audit_log' => '{$}'
        ]);
        
        $streamer->addPlaceholders([
            'users' => fn() => User::with('profile')->get(),
            'permissions' => fn() => Permission::all(),
            'audit_log' => fn() => AuditLog::latest()->limit(50)->get()
        ]);
        
        return $streamer->asResponse();
    }
}
```

### Symfony Integration

```php
<?php
// In a Symfony Controller
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends AbstractController
{
    public function progressiveEndpoint(): Response
    {
        $streamer = new ProgressiveJsonStreamer();
        
        $streamer->data([
            'products' => '{$}',
            'categories' => '{$}'
        ]);
        
        $streamer->addPlaceholders([
            'products' => fn() => $this->productRepository->findAll(),
            'categories' => fn() => $this->categoryRepository->findAll()
        ]);
        
        return $streamer->asResponse();
    }
}
```

---

## 🔒 Security Considerations

1. **Input Validation**: Always validate data before using it in resolvers
2. **Rate Limiting**: Implement rate limiting for expensive operations
3. **Authentication**: Ensure proper authentication before streaming sensitive data
4. **Memory Limits**: Be mindful of memory usage in resolvers

```php
<?php
$streamer->addPlaceholder('sensitive_data', function() {
    // Validate user permissions
    if (!$this->user->hasPermission('view_sensitive_data')) {
        throw new UnauthorizedException('Access denied');
    }
    
    return $this->getSensitiveData();
});
```

---

## 🔧 Configuration Options

### HTTP Headers

The streamer automatically sets appropriate headers for streaming:

- `Cache-Control: no-cache, no-store, must-revalidate`
- `Pragma: no-cache`
- `Expires: 0`
- `Connection: keep-alive`
- `Content-Type: application/x-json-stream`
- `X-Accel-Buffering: no` (Nginx: disable buffering)
- `X-Content-Type-Options: nosniff` (Security header)

### Performance Tuning

```php
<?php
// Increase max depth for deeply nested structures
$streamer->setMaxDepth(200);


// Optimize resolver execution order
$streamer->addPlaceholders([
    'fast_data' => fn() => $cache->get('fast_data'),     // Fast: from cache
    'medium_data' => fn() => $db->query('simple_query'), // Medium: simple query
    'slow_data' => fn() => $api->complexCalculation()    // Slow: complex operation
]);
```

---

## 🐛 Troubleshooting

### Common Issues

1. **Output Buffer Issues**: Make sure to disable output buffering for pure PHP usage
2. **Memory Limits**: For large datasets, consider chunking data in resolvers
3. **Timeout Issues**: Set appropriate timeout limits for long-running resolvers

### Debugging

```php
<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add debug information to resolvers
$streamer->addPlaceholder('debug_info', function() {
    return [
        'timestamp' => time(),
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true)
    ];
});
```

---

## � Testing

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

Coverage reports are generated in `build/coverage-html/` when running with coverage.

### Continuous Integration

GitHub Actions automatically runs tests on:
- PHP 8.0, 8.1, 8.2, 8.3, and 8.4
- Push and Pull Request events
- Multiple operating systems

---

## �🤝 Contributing

We welcome contributions from everyone! Please read our [Contributing Guide](CONTRIBUTING.md) for detailed information on how to get started.

**Quick Start:**
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
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

---

## 👨‍💻 Author

**AbdulRahman El-zahaby (egyjs)**  
📧 el3zahaby@gmail.com  
🐙 GitHub: [@egyjs](https://github.com/egyjs)

---

## 🙏 Acknowledgments

- Symfony HttpFoundation for streaming response utilities
- The PHP community for feedback and contributions

---

*Made with ❤️ by [egyjs](https://github.com/egyjs)  for the PHP community*
