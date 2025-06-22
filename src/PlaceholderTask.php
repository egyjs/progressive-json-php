<?php

namespace Egyjs\ProgressiveJson;

/**
 * Task class for amphp/parallel execution
 */
class PlaceholderTask implements \Amp\Parallel\Worker\Task
{
    private $resolver;
    private string $key;

    public function __construct(callable $resolver, string $key)
    {
        $this->resolver = $resolver;
        $this->key = $key;
    }

    public function run(\Amp\Parallel\Worker\Environment $environment): mixed
    {
        return ($this->resolver)();
    }
}