<?php
return [
    'consumer'  => [
        'handler'     => Webman\Stomp\Process\Consumer::class,
        'count'       => 8, // 可多进程消费
        'constructor' => [
            // 消费者类目录
            'consumer_dir' => app_path() . '/queue/stomp'
        ]
    ]
];