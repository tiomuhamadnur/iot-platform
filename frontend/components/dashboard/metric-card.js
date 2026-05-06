export function MetricCard({ label, value, detail }) {
  return (
    <div className="card">
      <div className="metric-label">{label}</div>
      <div className="metric-value">{value}</div>
      <div className="metric-detail">{detail}</div>
    </div>
  );
}
