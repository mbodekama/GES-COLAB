<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class DailyPathLogger
{
    public function __invoke(array $config): Logger
    {
        $path = storage_path('logs/' . now()->format('Y/m/d') . '/laravel.log');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $level   = Level::fromName($config['level'] ?? 'debug');
        $handler = new StreamHandler($path, $level);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $logger = new Logger('daily');
        $logger->pushHandler($handler);

        return $logger;
    }
}