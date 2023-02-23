<?php

declare(strict_types=1);

namespace Manzadey\SentryCronMonitor;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Manzadey\SentryCronMonitor\Exceptions\CronMonitorException;

class CronMonitor
{
    private ?int $time = null;

    private ?int $timeEnd = null;

    private ?string $checkinId = null;

    private array $errors = [];

    private ?string $monitorId = null;

    private array $data = [];

    public function __construct(
        readonly private string $dsn,
    )
    {
    }

    public static function init(string $dsn) : CronMonitor
    {
        return new CronMonitor($dsn);
    }

    public function getErrors() : array
    {
        return $this->errors;
    }

    public function hasErrors() : bool
    {
        return count($this->errors) === 0;
    }

    public function getData() : array
    {
        return $this->data;
    }

    private function hasCheckinId() : void
    {
        if(empty($this->checkinId)) {
            throw new CronMonitorException('`checkinId` attribute is empty or null');
        }
    }

    public static function inProgress(string $dsn, string $monitorId) : CronMonitor
    {
        $monitor = new CronMonitor($dsn);
        $monitor->progress($monitorId);

        return $monitor;
    }

    public function progress(string $monitorId) : void
    {
        $this->monitorId = $monitorId;
        $this->time      = time();

        $response = $this->setStatusProgress();

        $this->checkinId = $response['id'] ?? null;
    }

    private function setStatusProgress() : ?array
    {
        return $this->request(
            status: CronMonitorStatus::InProgress,
            uri: sprintf(CronMonitorStatus::getUri(CronMonitorStatus::InProgress), $this->monitorId),
            data: [
                'duration' => time() - $this->time,
            ],
        );
    }

    public function getDataProgress() : ?array
    {
        return $this->data[CronMonitorStatus::InProgress->value] ?? null;
    }

    public function ok() : ?array
    {
        return $this->setStatusOk();
    }

    private function setStatusOk() : ?array
    {
        $this->hasCheckinId();

        $this->timeEnd = time();

        return $this->request(
            status: CronMonitorStatus::Ok,
            uri: sprintf(CronMonitorStatus::getUri(CronMonitorStatus::Ok), $this->monitorId, $this->checkinId),
            data: [
                'duration' => $this->getTimeSeconds(),
            ],
        );
    }

    public function getDataOk() : ?array
    {
        return $this->data[CronMonitorStatus::Ok->value] ?? null;
    }

    public function error() : ?array
    {
        return $this->setStatusError();
    }

    private function setStatusError() : ?array
    {
        $this->hasCheckinId();

        $this->timeEnd = time();

        return $this->request(
            status: CronMonitorStatus::Error,
            uri: sprintf(CronMonitorStatus::getUri(CronMonitorStatus::Error), $this->monitorId, $this->checkinId),
            data: [
                'duration' => $this->getTimeSeconds(),
            ],
        );
    }

    public function getDataError() : ?array
    {
        return $this->data[CronMonitorStatus::Error->value] ?? null;
    }

    private function request(CronMonitorStatus $status, $uri, array $data = []) : ?array
    {
        try {
            $response = $this->client()->request(
                method: CronMonitorStatus::getMethod($status),
                uri: $uri,
                options: [
                    'json' => array_merge([
                        'status' => $status->value,
                    ], $data),
                ]
            );

            $this->data[$status->value] = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException $e) {
            $this->errors[] = [
                GuzzleException::class => $e->getMessage(),
            ];
        } catch (JsonException $e) {
            $this->errors[] = [
                JsonException::class => $e->getMessage(),
            ];
        }

        return $this->data[$status->value] ?? null;
    }

    private function client() : Client
    {
        return new Client([
            'base_uri' => 'https://sentry.io',
            'headers'  => [
                'Authorization' => "DSN $this->dsn",
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
        ]);
    }

    public function getTime() : ?int
    {
        return $this->time;
    }

    public function getTimeEnd() : ?int
    {
        return $this->timeEnd;
    }

    public function getTimeSeconds() : ?int
    {
        if($this->time !== null && $this->timeEnd !== null) {
            return $this->timeEnd - $this->time;
        }

        return null;
    }
}
