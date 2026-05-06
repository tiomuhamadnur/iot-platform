<?php

namespace App\Services;

use PhpMqtt\Client\Facades\MQTT;

class MqttService
{
    public function publish($topic, $message)
    {
        $mqtt = new \PhpMqtt\Client\MqttClient(
            config('services.mqtt.host'),
            config('services.mqtt.port'),
            'api-laravel'
        );

        $mqtt->connect();
        $mqtt->publish($topic, $message, 0);
        $mqtt->disconnect();
    }
}
