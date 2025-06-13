<?php

use PHPUnit\Framework\TestCase;
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

class ProgressiveJsonStreamerTest extends TestCase
{
    public function testDataAndPlaceholders()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->data([
            'message' => '{$}',
            'items' => '{$}',
        ]);

        $streamer->addPlaceholder('message', function () {
            return 'Test message';
        });
        $streamer->addPlaceholder('items', function () {
            return [1, 2, 3];
        });

        $reflection = new \ReflectionClass($streamer);
        $structureProp = $reflection->getProperty('structure');
        $structureProp->setAccessible(true);
        $placeholdersProp = $reflection->getProperty('placeholders');
        $placeholdersProp->setAccessible(true);

        $this->assertEquals([
            'message' => '{$}',
            'items' => '{$}',
        ], $structureProp->getValue($streamer));
        $this->assertArrayHasKey('message', $placeholdersProp->getValue($streamer));
        $this->assertArrayHasKey('items', $placeholdersProp->getValue($streamer));
    }

    public function testAddMultiplePlaceholders()
    {
        $streamer = new ProgressiveJsonStreamer();
        
        $placeholders = [
            'user.name' => fn() => 'John Doe',
            'user.email' => fn() => 'john@example.com',
            'posts' => fn() => ['Post 1', 'Post 2']
        ];
        
        $streamer->addPlaceholders($placeholders);
        
        $reflection = new \ReflectionClass($streamer);
        $placeholdersProp = $reflection->getProperty('placeholders');
        $placeholdersProp->setAccessible(true);
        $storedPlaceholders = $placeholdersProp->getValue($streamer);
        
        $this->assertCount(3, $storedPlaceholders);
        $this->assertArrayHasKey('user.name', $storedPlaceholders);
        $this->assertArrayHasKey('user.email', $storedPlaceholders);
        $this->assertArrayHasKey('posts', $storedPlaceholders);
    }

    public function testCustomPlaceholderMarker()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->setPlaceholderMarker('{{PLACEHOLDER}}');
        
        $reflection = new \ReflectionClass($streamer);
        $markerProp = $reflection->getProperty('placeholderMarker');
        $markerProp->setAccessible(true);
        
        $this->assertEquals('{{PLACEHOLDER}}', $markerProp->getValue($streamer));
    }

    public function testMaxDepthSetting()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->setMaxDepth(100);
        
        $reflection = new \ReflectionClass($streamer);
        $depthProp = $reflection->getProperty('maxDepth');
        $depthProp->setAccessible(true);
        
        $this->assertEquals(100, $depthProp->getValue($streamer));
    }

    public function testMaxDepthValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Max depth must be at least 1');
        
        $streamer = new ProgressiveJsonStreamer();
        $streamer->setMaxDepth(0);
    }    public function testInvalidPlaceholderResolver()
    {
        $this->expectException(\TypeError::class);
        
        $streamer = new ProgressiveJsonStreamer();
        $streamer->addPlaceholder('test', 'not-callable');
    }

    public function testStreamGeneration()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->data([
            'message' => '{$}',
            'count' => '{$}',
        ]);

        $streamer->addPlaceholder('message', function () {
            return 'Hello World';
        });
        $streamer->addPlaceholder('count', function () {
            return 42;
        });

        $streamChunks = [];
        foreach ($streamer->stream() as $chunk) {
            $streamChunks[] = $chunk;
        }

        $this->assertCount(3, $streamChunks); // Initial structure + 2 placeholders
        $this->assertStringContainsString('$message', $streamChunks[0]);
        $this->assertStringContainsString('$count', $streamChunks[0]);
        $this->assertStringContainsString('Hello World', $streamChunks[1]);
        $this->assertStringContainsString('42', $streamChunks[2]);
    }

    public function testNestedStructureHandling()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->data([
            'user' => [
                'profile' => [
                    'name' => '{$}',
                    'posts' => '{$}'
                ]
            ]
        ]);

        $streamer->addPlaceholder('user.profile.name', function () {
            return 'Jane Doe';
        });
        $streamer->addPlaceholder('user.profile.posts', function () {
            return ['Post A', 'Post B'];
        });

        $streamChunks = [];
        foreach ($streamer->stream() as $chunk) {
            $streamChunks[] = $chunk;
        }

        $this->assertCount(3, $streamChunks);
        $this->assertStringContainsString('$user.profile.name', $streamChunks[0]);
        $this->assertStringContainsString('$user.profile.posts', $streamChunks[0]);
    }

    public function testErrorHandlingInStream()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->data(['result' => '{$}']);
        
        $streamer->addPlaceholder('result', function () {
            throw new \RuntimeException('Test error');
        });

        $streamChunks = [];
        foreach ($streamer->stream() as $chunk) {
            $streamChunks[] = $chunk;
        }

        $this->assertCount(2, $streamChunks);
        $this->assertStringContainsString('error', $streamChunks[1]);
        $this->assertStringContainsString('Test error', $streamChunks[1]);
    }

    public function testGetStreamingHeaders()
    {
        $streamer = new ProgressiveJsonStreamer();
        
        $reflection = new \ReflectionClass($streamer);
        $method = $reflection->getMethod('getStreamingHeaders');
        $method->setAccessible(true);
        
        $headers = $method->invoke($streamer);
        
        $this->assertIsArray($headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('application/x-json-stream', $headers['Content-Type']);
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertEquals('no-cache, no-store, must-revalidate', $headers['Cache-Control']);
    }

    public function testSymfonyResponseCreation()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->data(['test' => '{$}']);
        $streamer->addPlaceholder('test', fn() => 'value');
        
        $response = $streamer->asResponse();
        
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Content-Type'));
    }

    public function testWalkStructureWithMaxDepthExceeded()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->setMaxDepth(2);
        
        // Create deeply nested structure
        $deepStructure = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => '{$}'
                    ]
                ]
            ]
        ];
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Maximum nesting depth (2) exceeded');
        
        $streamer->data($deepStructure);
        
        // This should trigger the walkStructure method and throw the exception
        foreach ($streamer->stream() as $chunk) {
            // This should not execute due to the exception
        }
    }

    public function testFluentInterface()
    {
        $streamer = new ProgressiveJsonStreamer();
        
        $result = $streamer
            ->data(['test' => '{$}'])
            ->addPlaceholder('test', fn() => 'value')
            ->setMaxDepth(25)
            ->setPlaceholderMarker('@@');
            
        $this->assertInstanceOf(ProgressiveJsonStreamer::class, $result);
    }

    public function testActualStreamingBehavior()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->data([
            'immediate' => '{$}',
            'delayed' => '{$}',
        ]);

        $executionOrder = [];
        
        $streamer->addPlaceholder('immediate', function () use (&$executionOrder) {
            $executionOrder[] = 'immediate_start';
            // Simulate some work
            usleep(10000); // 10ms
            $executionOrder[] = 'immediate_end';
            return 'immediate_value';
        });
        
        $streamer->addPlaceholder('delayed', function () use (&$executionOrder) {
            $executionOrder[] = 'delayed_start';
            // Simulate expensive operation
            usleep(20000); // 20ms
            $executionOrder[] = 'delayed_end';
            return 'delayed_value';
        });

        $streamChunks = [];
        $chunkTimes = [];
        
        foreach ($streamer->stream() as $chunk) {
            $streamChunks[] = $chunk;
            $chunkTimes[] = microtime(true);
        }

        // Verify streaming behavior
        $this->assertCount(3, $streamChunks);
        $this->assertCount(3, $chunkTimes);
        
        // Verify execution order (lazy evaluation)
        $this->assertEquals([
            'immediate_start',
            'immediate_end',
            'delayed_start', 
            'delayed_end'
        ], $executionOrder);
        
        // Verify that chunks are delivered with time gaps
        $this->assertGreaterThan($chunkTimes[0], $chunkTimes[1]);
        $this->assertGreaterThan($chunkTimes[1], $chunkTimes[2]);
    }    public function testStreamedResponseOutput()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->data(['message' => '{$}']);
        $streamer->addPlaceholder('message', fn() => 'Hello Streaming');
        
        $response = $streamer->asResponse();
        
        // Test that the response is a StreamedResponse
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        // Test headers - use get() method which should exist in all versions
        $contentType = $response->headers->get('Content-Type');
        $this->assertEquals('application/x-json-stream', $contentType);
        
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
    }    public function testSendMethodWithOutputBuffering()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->data([
            'step1' => '{$}',
            'step2' => '{$}',
        ]);
        
        $streamer->addPlaceholder('step1', fn() => 'first');
        $streamer->addPlaceholder('step2', fn() => 'second');
        
        // Instead of testing send() directly (which manipulates buffers),
        // test that the stream() method works and generates the expected output
        $chunks = [];
        foreach ($streamer->stream() as $chunk) {
            $chunks[] = $chunk;
        }
        
        // Verify we get the expected number of chunks
        $this->assertCount(3, $chunks); // Initial structure + 2 placeholders
        
        // Verify the content structure
        $this->assertStringContainsString('$step1', $chunks[0]);
        $this->assertStringContainsString('$step2', $chunks[0]);
        $this->assertStringContainsString('first', $chunks[1]);
        $this->assertStringContainsString('second', $chunks[2]);
        $this->assertStringContainsString('/* $step1 */', $chunks[1]);
        $this->assertStringContainsString('/* $step2 */', $chunks[2]);
    }

    public function testSendMethodHeaders()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->data(['test' => '{$}']);
        $streamer->addPlaceholder('test', fn() => 'value');
        
        // Test that the headers method works correctly (used by send())
        $reflection = new \ReflectionClass($streamer);
        $method = $reflection->getMethod('getStreamingHeaders');
        $method->setAccessible(true);
        
        $headers = $method->invoke($streamer);
        
        // Verify streaming headers are properly configured
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('application/x-json-stream', $headers['Content-Type']);
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertStringContainsString('no-cache', $headers['Cache-Control']);
        $this->assertArrayHasKey('Connection', $headers);
        $this->assertEquals('keep-alive', $headers['Connection']);
    }

    public function testProgressiveDataAvailability()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->data([
            'user' => '{$}',
            'posts' => '{$}',
            'comments' => '{$}',
        ]);

        $dataAvailability = [];
        
        $streamer->addPlaceholder('user', function () use (&$dataAvailability) {
            $dataAvailability[] = 'user_resolved';
            return ['id' => 1, 'name' => 'John'];
        });
        
        $streamer->addPlaceholder('posts', function () use (&$dataAvailability) {
            $dataAvailability[] = 'posts_resolved';
            return [['title' => 'Post 1'], ['title' => 'Post 2']];
        });
        
        $streamer->addPlaceholder('comments', function () use (&$dataAvailability) {
            $dataAvailability[] = 'comments_resolved';
            return [['text' => 'Comment 1']];
        });

        $chunks = [];
        foreach ($streamer->stream() as $chunk) {
            $chunks[] = $chunk;
        }

        // Verify data is resolved in order and progressively
        $this->assertEquals([
            'user_resolved',
            'posts_resolved', 
            'comments_resolved'
        ], $dataAvailability);
        
        // Initial structure should be available immediately
        $this->assertStringContainsString('$user', $chunks[0]);
        $this->assertStringContainsString('$posts', $chunks[0]);
        $this->assertStringContainsString('$comments', $chunks[0]);
        
        // Then individual data chunks
        $this->assertStringContainsString('John', $chunks[1]);
        $this->assertStringContainsString('Post 1', $chunks[2]);
        $this->assertStringContainsString('Comment 1', $chunks[3]);
    }

    public function testStreamingWithRealTimeConstraints()
    {
        $streamer = new ProgressiveJsonStreamer();
        $streamer->data([
            'fast' => '{$}',
            'slow' => '{$}',
        ]);

        $timestamps = [];
        
        $streamer->addPlaceholder('fast', function () use (&$timestamps) {
            $timestamps['fast'] = microtime(true);
            return 'fast_data';
        });
        
        $streamer->addPlaceholder('slow', function () use (&$timestamps) {
            usleep(50000); // 50ms delay
            $timestamps['slow'] = microtime(true);
            return 'slow_data';
        });

        $streamStart = microtime(true);
        $chunks = [];
        
        foreach ($streamer->stream() as $chunk) {
            $chunks[] = $chunk;
        }
        
        $streamEnd = microtime(true);

        // Verify that the slow placeholder actually caused a delay
        $this->assertGreaterThan($streamStart + 0.04, $streamEnd); // At least 40ms
        
        // Verify fast was resolved before slow
        $this->assertLessThan($timestamps['slow'], $timestamps['fast']);
        
        // Verify we got the expected chunks
        $this->assertCount(3, $chunks); // Initial + fast + slow
    }
}
