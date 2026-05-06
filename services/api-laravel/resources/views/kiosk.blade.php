<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiosk Mode - IoT Platform</title>
    <style>
        body {
            background-color: #000;
            color: #0f0;
            font-family: 'Courier New', Courier, monospace;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            border: 2px solid #0f0;
            padding: 20px;
            text-align: center;
        }
        .data-point {
            font-size: 3em;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>REALTIME MONITORING</h1>
        <div id="device-id">Device: Loading...</div>
        <div class="data-point" id="value">---</div>
        <div id="timestamp">---</div>
    </div>

    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <script>
        const socket = io(@json(env('WEBSOCKET_URL', 'http://127.0.0.1:3000')));
        const tenantId = 'tenant-1';
        const deviceId = 'device-123';
        
        socket.on('connect', () => {
            console.log('Connected to WebSocket');
            socket.emit('subscribe', `tenant:${tenantId}:device:${deviceId}`);
        });

        socket.on('telemetry.received', (payload) => {
            console.log('Received data:', payload);
            document.getElementById('device-id').innerText = `Device: ${payload.device_id}`;
            document.getElementById('value').innerText = JSON.stringify(payload.values);
            document.getElementById('timestamp').innerText = payload.device_time;
        });
    </script>
</body>
</html>
