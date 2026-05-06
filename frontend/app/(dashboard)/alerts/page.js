"use client";

import { useEffect, useState } from "react";
import { fetchCurrentUser } from "@/lib/api";
import { Topbar } from "@/components/dashboard/topbar";
import { AlertList } from "@/components/dashboard/alert-list";

const alerts = [
  { id: "al-01", name: "Temperature threshold", severity: "critical", state: "PROBLEM", device: "device-001", message: "Observed above 80C for 60s", updatedAt: "just now" },
  { id: "al-02", name: "Pressure recovery", severity: "warning", state: "OK", device: "device-001", message: "Recovered after cooldown window", updatedAt: "21m ago" },
  { id: "al-03", name: "Gateway heartbeat", severity: "warning", state: "PROBLEM", device: "gateway-001", message: "Last heartbeat exceeded expected interval", updatedAt: "34m ago" },
];

export default function AlertsPage() {
  const [user, setUser] = useState(null);

  useEffect(() => {
    fetchCurrentUser().then((response) => setUser(response.data)).catch(() => {});
  }, []);

  return (
    <>
      <Topbar
        title="Alert review"
        description="Operator-oriented alert list while final alert events API is still being normalized."
        user={user}
      />
      <div className="panel">
        <div className="section-heading">
          <div>
            <h3>Alert queue</h3>
            <p>UI baseline for Phase 8 and Phase 11 integration.</p>
          </div>
        </div>
        <AlertList alerts={alerts} />
      </div>
    </>
  );
}
