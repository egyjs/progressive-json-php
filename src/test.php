<?php

use App\Services\ProgressiveJsonStreamer;

require __DIR__ . '/../vendor/autoload.php';

$streamer = new ProgressiveJsonStreamer();


$streamer->data([
    'message' => '{$}',
    'status' => '200',
    'items' => '{$}',
]);


$streamer->addPlaceholder('message', function () {
    sleep(2); // Simulate delay
    return 'Hello, this is a progressive JSON response!';
});
$streamer->addPlaceholder('items', function () {
    // Simulate fetching items from a database or API
    sleep(3); // Simulate delay
    return [
        ['id' => 1, 'name' => 'Item 1'],
        ['id' => 2, 'name' => 'Item 2'],
        ['id' => 3, 'name' => 'Item 3'],
    ];
});

$streamer->send();
