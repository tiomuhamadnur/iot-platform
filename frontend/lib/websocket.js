import { io } from "socket.io-client";

const WS_URL = process.env.NEXT_PUBLIC_WS_URL || "http://127.0.0.1:3000";

export function subscribeToDeviceRoom({ tenantId, deviceId, onMessage }) {
  if (!tenantId || !deviceId || typeof window === "undefined") {
    return () => {};
  }

  const socket = io(WS_URL, {
    transports: ["websocket", "polling"],
  });

  const room = `tenant:${tenantId}:device:${deviceId}`;

  socket.on("connect", () => {
    socket.emit("subscribe", room);
  });

  socket.on("telemetry.received", onMessage);

  return () => {
    socket.emit("unsubscribe", room);
    socket.disconnect();
  };
}
