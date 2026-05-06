const mqtt = require('mqtt');
const dotenv = require('dotenv');
const path = require('path');

// Load .env from root
dotenv.config({ path: path.join(__dirname, '../../.env') });

const MQTT_HOST = process.env.MQTT_HOST || 'localhost';
const MQTT_PORT = process.env.MQTT_PORT || 1883;
const MQTT_URL = `mqtt://${MQTT_HOST}:${MQTT_PORT}`;

const client = mqtt.connect(MQTT_URL, {
    username: process.env.MQTT_USERNAME,
    password: process.env.MQTT_PASSWORD,
});

const tenantId = 'tenant-1';
const deviceId = 'device-123';
const topic = `tenant/${tenantId}/device/${deviceId}/data`;

client.on('connect', () => {
    console.log(`Connected to MQTT broker at ${MQTT_URL}`);
    
    // Send message every 5 seconds
    setInterval(() => {
        const payload = {
            device_time: new Date().toISOString(),
            values: {
                temperature: Number((Math.random() * 10 + 20).toFixed(2)),
                humidity: Number((Math.random() * 20 + 40).toFixed(2)),
                status: "OK"
            }
        };
        
        console.log(`Publishing to ${topic}:`, payload);
        client.publish(topic, JSON.stringify(payload));
    }, 5000);
});

client.on('error', (err) => {
    console.error('Connection error:', err);
    client.end();
});
