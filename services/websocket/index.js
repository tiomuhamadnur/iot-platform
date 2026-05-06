const http = require('http');
const { Server } = require('socket.io');
const Redis = require('ioredis');
const dotenv = require('dotenv');
const path = require('path');

dotenv.config({ path: path.join(__dirname, '../../.env') });

const httpServer = http.createServer();
const io = new Server(httpServer, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

const redis = new Redis({
    host: process.env.REDIS_HOST || 'localhost',
    port: Number(process.env.REDIS_PORT || 6379),
});

const subRedis = redis.duplicate();

subRedis.subscribe('telemetry', (err, count) => {
    if (err) {
        console.error('Failed to subscribe to Redis:', err);
    } else {
        console.log(`Subscribed to ${count} channels.`);
    }
});

subRedis.on('message', (channel, message) => {
    if (channel === 'telemetry') {
        const data = JSON.parse(message);
        // Broadcast to specific room for tenant/device
        const room = `tenant:${data.tenant_id}:device:${data.device_id}`;
        io.to(room).emit('telemetry.received', data);
        
        // Also broadcast to tenant room
        io.to(`tenant:${data.tenant_id}`).emit('telemetry.received', data);
    }
});

io.on('connection', (socket) => {
    console.log('Client connected:', socket.id);

    socket.on('subscribe', (room) => {
        console.log(`Client ${socket.id} joining room: ${room}`);
        socket.join(room);
    });

    socket.on('unsubscribe', (room) => {
        console.log(`Client ${socket.id} leaving room: ${room}`);
        socket.leave(room);
    });

    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
    });
});

const PORT = Number(process.env.WEBSOCKET_PORT || process.env.WS_PORT || 3000);
httpServer.listen(PORT, () => {
    console.log(`WebSocket server running on port ${PORT}`);
});
