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
}
