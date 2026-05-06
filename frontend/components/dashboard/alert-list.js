export function AlertList({ alerts }) {
  return (
    <div className="alert-list">
      <div className="alert-row header">
        <div>Rule</div>
        <div>Severity</div>
        <div>State</div>
        <div>Device</div>
        <div>Updated</div>
      </div>
      {alerts.map((alert) => (
        <div className="alert-row" key={alert.id}>
          <div>
            <strong>{alert.name}</strong>
            <div className="muted">{alert.message}</div>
          </div>
          <div>
            <span className={`badge ${alert.severity === "critical" ? "badge-critical" : "badge-warning"}`}>
              {alert.severity}
            </span>
          </div>
          <div>{alert.state}</div>
          <div>{alert.device}</div>
          <div>{alert.updatedAt}</div>
        </div>
      ))}
    </div>
  );
}
