"use client";

import { useEffect, useMemo, useState } from "react";
import { fetchCommands, fetchCurrentUser, fetchDevice, fetchTelemetry } from "@/lib/api";
import { Topbar } from "@/components/dashboard/topbar";
import { TelemetryChart } from "@/components/charts/telemetry-chart";
import { subscribeToDeviceRoom } from "@/lib/websocket";
import { formatNumber } from "@/lib/formatters";

export default function DeviceDetailPage({ params }) {
  const [user, setUser] = useState(null);
  const [device, setDevice] = useState(null);
  const [telemetry, setTelemetry] = useState([]);
  const [commands, setCommands] = useState([]);
  const [error, setError] = useState("");

  async function load() {
    try {
      const [userResponse, deviceResponse, telemetryResponse, commandResponse] = await Promise.all([
        fetchCurrentUser(),
        fetchDevice(params.id),
        fetchTelemetry(params.id),
        fetchCommands(params.id),
      ]);

      setUser(userResponse.data);
      setDevice(deviceResponse.data);
      setTelemetry(telemetryResponse.data || []);
      setCommands(commandResponse.data || []);
      setError("");
    } catch (loadError) {
      setError(loadError.message);
    }
  }

  useEffect(() => {
    load();
  }, [params.id]);

  useEffect(() => {
    if (!device) {
      return undefined;
    }

    return subscribeToDeviceRoom({
      tenantId: device.tenant_id,
      deviceId: device.device_id,
      onMessage: (message) => {
        const entries = Object.entries(message.values || {}).map(([key, value]) => ({
          param_key: key,
          value: typeof value === "number" ? value : null,
          str_value: typeof value === "string" ? value : null,
          timestamp: new Date(message.device_time).toLocaleTimeString(),
        }));

        setTelemetry((current) => [...current.slice(-120), ...entries]);
      },
    });
  }, [device]);

  const numericSeries = useMemo(() => {
    return telemetry
      .filter((row) => row.param_key === "temperature" && row.value !== null)
      .map((row) => ({
        timestamp: new Date(row.timestamp).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }),
        value: Number(row.value),
      }));
  }, [telemetry]);

  const latestValues = useMemo(() => {
    const result = new Map();

    telemetry.forEach((entry) => {
      result.set(entry.param_key, entry);
    });

    return Array.from(result.values());
  }, [telemetry]);

  return (
    <>
      <Topbar
        title={device?.name || "Device detail"}
        description={device ? `Device key ${device.device_id} with realtime telemetry stream.` : "Loading device metadata."}
        user={user}
        onRefresh={load}
      />

      {error ? <div className="error-state" style={{ marginBottom: 16 }}>{error}</div> : null}

      <div className="split-grid">
        <div className="chart-panel">
          <div className="section-heading">
            <div>
              <h3>Temperature stream</h3>
              <p>Historical API result with realtime Socket.IO append.</p>
            </div>
          </div>
          {numericSeries.length ? <TelemetryChart data={numericSeries} /> : <div className="empty-state">No numeric telemetry available yet.</div>}
        </div>

        <div className="stats-stack">
          <div className="panel">
            <div className="section-heading">
              <div>
                <h3>Latest values</h3>
                <p>Last values grouped by parameter key.</p>
              </div>
            </div>
            <div className="kv-list">
              {latestValues.map((entry) => (
                <div key={`${entry.param_key}-${entry.timestamp}`} className="kv-row">
                  <span>{entry.param_key}</span>
                  <strong>{entry.value !== null ? formatNumber(entry.value) : entry.str_value || "-"}</strong>
                </div>
              ))}
            </div>
          </div>

          <div className="panel">
            <div className="section-heading">
              <div>
                <h3>Command history</h3>
                <p>Output from `/api/v1/devices/{`{id}`}/commands`.</p>
              </div>
            </div>
            <div className="kv-list">
              {commands.length ? commands.map((command) => (
                <div key={command.id} className="kv-row">
                  <span>{command.command}</span>
                  <strong>{command.status}</strong>
                </div>
              )) : <div className="muted">No commands recorded for this device.</div>}
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
