<?php

namespace Egyjs\ProgressiveJson;

use Generator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use InvalidArgumentException;
use RuntimeException;

/**
 * Progressive JSON Streamer
 *
 * Streams JSON data progressively by first sending a structure with placeholders,
 * then streaming actual data as it becomes available through lazy-evaluated resolvers.
 *
 * This is particularly useful for:
 * - Large datasets that take time to process
 * - API responses with expensive operations
 * - Real-time data streaming scenarios
 *
 * @package Egyjs\ProgressiveJson
 * @author AbdulRahman El-zahaby (egyjs) <el3zahaby@gmail.com>
 */
class ProgressiveJsonStreamer
{
    /**
     * The JSON structure template with placeholders
     *
     * @var array
     */
    protected array $structure = [];

    /**
     * Placeholder resolvers with dot notation keys (callable functions)
     *
     * @var array<string, callable>
     */
    protected array $placeholders = [];

    /**
     * Placeholder marker used in structure
     *
     * @var string
     */
    protected string $placeholderMarker = '{$}';

    /**
     * Maximum nesting depth to prevent infinite recursion
     *
     * @var int
     */
    protected int $maxDepth = 50;

    /**
     * Set the JSON structure template
     *
     * @param array $structure The structure with placeholders marked as '{$}'
     * @return self
     * @throws InvalidArgumentException If structure is not an array
     */
    public function data(array $structure): self
    {
        $this->structure = $structure;
        return $this;
    }

    /**
     * Add a single placeholder resolver using dot notation
     *
     * @param string $key The placeholder key in dot notation (e.g., 'user.profile.posts')
     * @param callable $resolver Function that returns the actual data
     * @return self
     * @throws InvalidArgumentException If resolver is not callable
     */
    public function addPlaceholder(string $key, callable $resolver): self
    {
        if (!is_callable($resolver)) {
            throw new InvalidArgumentException("Resolver for key '{$key}' must be callable");
        }

        $this->placeholders[$key] = $resolver;
        return $this;
    }

    /**
     * Add multiple placeholder resolvers at once
     *
     * @param array<string, callable> $placeholders Array of dot notation key => resolver pairs
     * @return self
     * @throws InvalidArgumentException If any resolver is not callable
     */
    public function addPlaceholders(array $placeholders): self
    {
        foreach ($placeholders as $key => $resolver) {
            $this->addPlaceholder($key, $resolver);
        }
        return $this;
    }

    /**
     * Set custom placeholder marker
     *
     * @param string $marker The marker to use for placeholders (default: '{$}')
     * @return self
     */
    public function setPlaceholderMarker(string $marker): self
    {
        $this->placeholderMarker = $marker;
        return $this;
    }

    /**
     * Set maximum nesting depth for structure walking
     *
     * @param int $depth Maximum depth (default: 50)
     * @return self
     * @throws InvalidArgumentException If depth is less than 1
     */
    public function setMaxDepth(int $depth): self
    {
        if ($depth < 1) {
            throw new InvalidArgumentException('Max depth must be at least 1');
        }

        $this->maxDepth = $depth;
        return $this;
    }

    /**
     * Generate the progressive JSON stream
     *
     * Yields the initial structure first, then each placeholder's resolved data.
     * The output is not valid JSON but a stream of JSON chunks with comments.
     *
     * @return Generator<string> JSON chunks
     * @throws RuntimeException If structure processing fails
     */
    public function stream(): Generator
    {
        try {
            // First, yield the initial structure with placeholder markers
            $initialStructure = $this->walkStructure($this->structure);
            yield json_encode($initialStructure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            // Then yield each placeholder's resolved data
            foreach ($this->placeholders as $key => $resolver) {
                try {
                    $value = $resolver(); // Lazy evaluation
                    $jsonValue = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    if ($jsonValue === false) {
                        throw new RuntimeException("Failed to encode JSON for placeholder '{$key}': " . json_last_error_msg());
                    }

                    yield "\n/* \${$key} */\n" . $jsonValue;

                } catch (\Throwable $e) {
                    // Yield error information instead of breaking the stream
                    $errorData = [
                        'error' => true,
                        'key' => $key,
                        'message' => $e->getMessage(),
                        'type' => get_class($e)
                    ];

                    yield "\n/* \${$key} */\n" . json_encode($errorData, JSON_PRETTY_PRINT);
                }
            }
        } catch (\Throwable $e) {
            throw new RuntimeException('Failed to generate stream: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Send the stream directly to output buffer
     *
     * Sets appropriate headers and streams data directly to the client.
     * Use this for direct output without Symfony framework.
     *
     * used for pure PHP applications or when you want to handle the output manually.
     * @return void
     * @throws RuntimeException If streaming fails
     */
    public function send(): void
    {
        try {
            $this->setStreamingHeaders();

            // Enable implicit flushing and clean output buffers
            ob_implicit_flush(true);
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            foreach ($this->stream() as $chunk) {
                echo $chunk;

                // Force flush to client
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }

        } catch (\Throwable $e) {
            throw new RuntimeException('Failed to send stream: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a Symfony StreamedResponse
     *
     * This method is useful when you are using Symfony or Laravel
     * @return StreamedResponse
     */
    public function asResponse(): StreamedResponse
    {
        return new StreamedResponse(function () {
            try {
                foreach ($this->stream() as $chunk) {
                    echo $chunk;

                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }
            } catch (\Throwable $e) {
                // Log the error and send error response
                error_log('ProgressiveJsonStreamer error: ' . $e->getMessage());
                echo "\n/* STREAM_ERROR */\n" . json_encode([
                        'error' => true,
                        'message' => 'Stream processing failed'
                    ]);
            }
        }, 200, $this->getStreamingHeaders());
    }

    /**
     * Set HTTP headers for streaming response
     *
     * @return void
     */
    protected function setStreamingHeaders(): void
    {
        foreach ($this->getStreamingHeaders() as $key => $value) {
            header("{$key}: {$value}");
        }
    }

    /**
     * Get HTTP headers appropriate for streaming
     *
     * @return array<string, string> Headers array
     */
    protected function getStreamingHeaders(): array
    {
        return [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-json-stream',
            'X-Accel-Buffering' => 'no', // Nginx: disable buffering
            'X-Content-Type-Options' => 'nosniff', // Security header
        ];
    }

    /**
     * Recursively walk through the structure and replace placeholders with dot notation keys
     *
     * This method handles nested arrays and objects, replacing placeholder
     * markers with dot notation reference keys that can be later resolved.
     *
     * @param mixed $structure The structure to process
     * @param string $path Current dot notation path
     * @param int $depth Current recursion depth
     * @return mixed Processed structure
     * @throws RuntimeException If maximum depth is exceeded
     */
    protected function walkStructure(mixed $structure, string $path = '', int $depth = 0): mixed
    {
        // Prevent infinite recursion
        if ($depth > $this->maxDepth) {
            throw new RuntimeException("Maximum nesting depth ({$this->maxDepth}) exceeded in structure");
        }

        // Handle arrays (both indexed and associative)
        if (is_array($structure)) {
            $result = [];

            foreach ($structure as $key => $value) {
                $currentPath = $path === '' ? (string)$key : $path . '.' . $key;

                if ($value === $this->placeholderMarker) {
                    // Replace placeholder with dot notation reference key
                    $result[$key] = '$' . $currentPath;
                } elseif (is_array($value)) {
                    // Recursively process nested arrays
                    $result[$key] = $this->walkStructure($value, $currentPath, $depth + 1);
                } elseif (is_object($value)) {
                    // Convert objects to arrays and process
                    $result[$key] = $this->walkStructure((array) $value, $currentPath, $depth + 1);
                } else {
                    // Keep primitive values as-is
                    $result[$key] = $value;
                }
            }

            return $result;
        }

        // Handle objects by converting to array first
        if (is_object($structure)) {
            return $this->walkStructure((array) $structure, $path, $depth);
        }

        // Handle string placeholders at any level
        if (is_string($structure) && $structure === $this->placeholderMarker) {
            return '$' . ($path ?: 'placeholder');
        }

        // Return primitive values unchanged
        return $structure;
    }

    /**
     * Get all placeholder keys found in the structure
     *
     * @return array<string> Array of placeholder keys in dot notation
     */
    public function getPlaceholderKeys(): array
    {
        return array_keys($this->placeholders);
    }

    /**
     * Check if a placeholder exists
     *
     * @param string $key The placeholder key in dot notation to check
     * @return bool
     */
    public function hasPlaceholder(string $key): bool
    {
        return isset($this->placeholders[$key]);
    }

    /**
     * Remove a placeholder
     *
     * @param string $key The placeholder key in dot notation to remove
     * @return self
     */
    public function removePlaceholder(string $key): self
    {
        unset($this->placeholders[$key]);
        return $this;
    }

    /**
     * Clear all placeholders
     *
     * @return self
     */
    public function clearPlaceholders(): self
    {
        $this->placeholders = [];
        return $this;
    }

    /**
     * Get the current structure
     *
     * @return array
     */
    public function getStructure(): array
    {
        return $this->structure;
    }
}
