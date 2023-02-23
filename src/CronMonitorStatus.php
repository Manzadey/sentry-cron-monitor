<?php

declare(strict_types=1);

namespace Manzadey\SentryCronMonitor;

enum CronMonitorStatus: string
{
    case Ok = 'ok';

    case Error = 'error';

    case InProgress = 'in_progress';

    public static function getMethod(self $value) : string
    {
        return match ($value) {
            self::InProgress => 'POST',
            self::Ok, self::Error => 'PUT'
        };
    }

    public static function getUri(self $value) : string
    {
        return match ($value) {
            self::InProgress => '/api/0/monitors/%s/checkins/',
            self::Ok, self::Error => '/api/0/monitors/%s/checkins/%s/'
        };
    }
}
