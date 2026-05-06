"use client";

import { useEffect, useState } from "react";
import { fetchDevices, fetchCurrentUser } from "@/lib/api";
import { Topbar } from "@/components/dashboard/topbar";
import { DeviceTable } from "@/components/dashboard/device-table";

export default function DevicesPage() {
  const [devices, setDevices] = useState([]);
  const [user, setUser] = useState(null);
  const [error, setError] = useState("");

  async function load() {
    try {
      const [userResponse, devicesResponse] = await Promise.all([fetchCurrentUser(), fetchDevices()]);
      setUser(userResponse.data);
      setDevices(devicesResponse.data || []);
      setError("");
    } catch (loadError) {
      setError(loadError.message);
    }
  }

  useEffect(() => {
    load();
  }, []);

  return (
    <>
      <Topbar
        title="Device inventory"
        description="Operational list for registered devices, tenant scope, and last signal visibility."
        user={user}
        onRefresh={load}
      />
      {error ? <div className="error-state" style={{ marginBottom: 16 }}>{error}</div> : null}
      <div className="table-panel">
        <div className="section-heading">
          <div>
            <h3>All devices</h3>
            <p>Backed by `/api/v1/devices`.</p>
          </div>
        </div>
        {devices.length ? <DeviceTable devices={devices} /> : <div className="empty-state">No devices available.</div>}
      </div>
    </>
  );
}
