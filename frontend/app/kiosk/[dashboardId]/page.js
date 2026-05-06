"use client";

import { useEffect, useState } from "react";
import { fetchDevices } from "@/lib/api";
import { MetricCard } from "@/components/dashboard/metric-card";

export default function KioskPage({ params }) {
  const [devices, setDevices] = useState([]);

  useEffect(() => {
    fetchDevices().then((response) => setDevices(response.data || [])).catch(() => {});
    const timer = setInterval(() => {
      fetchDevices().then((response) => setDevices(response.data || [])).catch(() => {});
    }, 5000);

    return () => clearInterval(timer);
  }, []);

  return (
    <main className="kiosk">
      <div className="kiosk-top">
        <div>
          <h1>Monitoring screen</h1>
          <div className="muted">Dashboard token: {params.dashboardId}</div>
        </div>
        <div className="status-pill">Auto refresh 5s</div>
      </div>

      <div className="kiosk-grid">
        <MetricCard label="Visible devices" value={devices.length} detail="Tenant-scoped inventory" />
        <MetricCard label="Active devices" value={devices.filter((item) => item.status === "active").length} detail="Status from metadata" />
        <MetricCard label="Offline devices" value={devices.filter((item) => item.status !== "active").length} detail="Needs operator review" />
        <MetricCard label="Telemetry mode" value="Realtime" detail="Socket.IO / polling hybrid baseline" />
      </div>

      <div className="panel">
        <div className="section-heading">
          <div>
            <h3>Device wall</h3>
            <p>Full-screen operational listing for TV or monitoring display.</p>
          </div>
        </div>
        <div className="device-table">
          <div className="device-row header">
            <div>Device</div>
            <div>Status</div>
            <div>Tenant</div>
            <div>Last Seen</div>
            <div>Mode</div>
          </div>
          {devices.map((device) => (
            <div className="device-row" key={device.id}>
              <div>
                <strong>{device.name}</strong>
                <div className="muted">{device.device_id}</div>
              </div>
              <div>
                <span className={`badge ${device.status === "active" ? "badge-online" : "badge-offline"}`}>{device.status}</span>
              </div>
              <div>{device.tenant_id}</div>
              <div>{device.last_seen_at || "No signal"}</div>
              <div>Watch</div>
            </div>
          ))}
        </div>
      </div>
    </main>
  );
}
