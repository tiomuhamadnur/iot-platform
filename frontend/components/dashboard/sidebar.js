"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Activity, Bell, LayoutDashboard, Monitor, RadioTower } from "lucide-react";

const items = [
  { href: "/dashboard", label: "Overview", icon: LayoutDashboard },
  { href: "/devices", label: "Devices", icon: RadioTower },
  { href: "/alerts", label: "Alerts", icon: Bell },
  { href: "/kiosk/main", label: "Kiosk", icon: Monitor },
];

export function Sidebar() {
  const pathname = usePathname();

  return (
    <aside className="sidebar">
      <h1>IoT Platform</h1>
      <p>Operational workspace for telemetry, alerts, and command visibility.</p>
      <nav className="sidebar-nav">
        {items.map((item) => {
          const Icon = item.icon;
          const isActive = pathname === item.href || pathname.startsWith(`${item.href}/`);

          return (
            <Link key={item.href} href={item.href} className={`sidebar-link${isActive ? " active" : ""}`}>
              <Icon size={18} />
              <span>{item.label}</span>
            </Link>
          );
        })}
      </nav>
      <div className="panel" style={{ marginTop: 24 }}>
        <div className="section-heading" style={{ marginBottom: 10 }}>
          <div>
            <h3>Pipeline</h3>
          </div>
        </div>
        <div className="kv-list">
          <div className="kv-row"><span>MQTT</span><strong>EMQX</strong></div>
          <div className="kv-row"><span>Ingestion</span><strong>Go</strong></div>
          <div className="kv-row"><span>Realtime</span><strong>Socket.IO</strong></div>
          <div className="kv-row"><span>Storage</span><strong>InfluxDB</strong></div>
        </div>
      </div>
      <div className="tenant-pill" style={{ marginTop: 18 }}>
        <Activity size={14} />
        <span>Phase 11 baseline</span>
      </div>
    </aside>
  );
}
