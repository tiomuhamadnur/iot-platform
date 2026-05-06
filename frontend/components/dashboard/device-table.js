import Link from "next/link";
import { formatRelativeTime } from "@/lib/formatters";

function statusClass(status) {
  if (status === "active") return "badge badge-active";
  if (status === "warning") return "badge badge-warning";
  return "badge badge-offline";
}

export function DeviceTable({ devices }) {
  return (
    <div className="device-table">
      <div className="device-row header">
        <div>Device</div>
        <div>Status</div>
        <div>Tenant</div>
        <div>Last Seen</div>
        <div />
      </div>
      {devices.map((device) => (
        <div className="device-row" key={device.id}>
          <div>
            <div className="device-link">{device.name}</div>
            <div className="muted">{device.device_id}</div>
          </div>
          <div><span className={statusClass(device.status)}>{device.status}</span></div>
          <div>{device.tenant_id}</div>
          <div>{formatRelativeTime(device.last_seen_at)}</div>
          <div>
            <Link className="button button-secondary" href={`/devices/${device.id}`}>
              Inspect
            </Link>
          </div>
        </div>
      ))}
    </div>
  );
}
