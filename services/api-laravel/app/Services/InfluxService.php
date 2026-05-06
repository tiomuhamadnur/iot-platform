<?php

namespace App\Services;

use InfluxDB2\Client;
use InfluxDB2\Model\WritePrecision;

class InfluxService
{
    protected $client;
    protected $org;
    protected $bucket;

    public function __construct()
    {
        $this->client = new Client([
            "url" => config('services.influx.url'),
            "token" => config('services.influx.token'),
        ]);
        $this->org = config('services.influx.org');
        $this->bucket = config('services.influx.bucket');
    }

    public function query($fluxQuery)
    {
        return $this->client->createQueryApi()->query($fluxQuery, $this->org);
    }

    public function bucket(): string
    {
        return $this->bucket;
    }
}
