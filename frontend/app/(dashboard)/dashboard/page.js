"use client";

import { useEffect, useMemo, useState } from "react";
import { fetchDevices } from "@/lib/api";
import { Topbar } from "@/components/dashboard/topbar";
import { MetricCard } from "@/components/dashboard/metric-card";
import { DeviceTable } from "@/components/dashboard/device-table";
import { AlertList } from "@/components/dashboard/alert-list";

const alertSeed = [
  { id: "a1", name: "High temperature", severity: "critical", state: "PROBLEM", device: "device-001", message: "Temperature over 80C", updatedAt: "just now" },
  { id: "a2", name: "Pressure drift", severity: "warning", state: "OK", device: "gateway-001", message: "Recovered after cooldown", updatedAt: "12m ago" },
];

export default function DashboardPage() {
  const [devices, setDevices] = useState([]);
  const [user, setUser] = useState(null);
  const [error, setError] = useState("");

  async function load() {
    try {
      const [userModule, devicesResponse] = await Promise.all([
        import("@/lib/api").then((mod) => mod.fetchCurrentUser()),
        fetchDevices(),
      ]);

      setUser(userModule.data);
      setDevices(devicesResponse.data || []);
      setError("");
    } catch (loadError) {
      setError(loadError.message);
    }
  }

  useEffect(() => {
    load();
  }, []);

  const metrics = useMemo(() => {
    const activeCount = devices.filter((device) => device.status === "active").length;

    return [
      { label: "Registered devices", value: devices.length, detail: "Inventory currently visible in tenant scope" },
      { label: "Active devices", value: activeCount, detail: "Devices marked active in metadata" },
      { label: "Alert pressure", value: alertSeed.filter((item) => item.state === "PROBLEM").length, detail: "Current problem state events" },
      { label: "Plan capacity", value: "20%", detail: "2 of 10 device slots used in seeded tenant" },
    ];
  }, [devices]);

  return (
    <>
      <Topbar
        title="Operational overview"
        description="Cross-service telemetry visibility, current alert load, and tenant device inventory."
        user={user}
        onRefresh={load}
      />

      {error ? <div className="error-state" style={{ marginBottom: 16 }}>{error}</div> : null}

      <div className="metrics-grid">
        {metrics.map((metric) => (
          <MetricCard key={metric.label} {...metric} />
        ))}
      </div>

      <div className="content-grid">
        <div className="table-panel">
          <div className="section-heading">
            <div>
              <h3>Devices</h3>
              <p>Tenant-scoped inventory from Laravel API.</p>
            </div>
          </div>
          {devices.length ? <DeviceTable devices={devices} /> : <div className="empty-state">No devices loaded.</div>}
        </div>

        <div className="panel">
          <div className="section-heading">
            <div>
              <h3>Alert state</h3>
              <p>Phase 8/11 merged view for operator workflow.</p>
            </div>
          </div>
          <AlertList alerts={alertSeed} />
        </div>
      </div>
    </>
  );
}
