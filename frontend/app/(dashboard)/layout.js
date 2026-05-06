import { Sidebar } from "@/components/dashboard/sidebar";

export default function DashboardLayout({ children }) {
  return (
    <div className="dashboard-shell">
      <Sidebar />
      <main className="main-panel">{children}</main>
    </div>
  );
}
