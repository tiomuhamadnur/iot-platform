"use client";

import { LogOut, RefreshCw, UserCircle2 } from "lucide-react";
import { clearToken } from "@/lib/api";
import { useRouter } from "next/navigation";

export function Topbar({ title, description, user, onRefresh }) {
  const router = useRouter();

  return (
    <div className="topbar">
      <div className="title-block">
        <h2>{title}</h2>
        <p>{description}</p>
      </div>
      <div className="toolbar">
        {onRefresh ? (
          <button className="button button-secondary" onClick={onRefresh}>
            <RefreshCw size={16} />
            Refresh
          </button>
        ) : null}
        <div className="tenant-pill">
          <UserCircle2 size={16} />
          <span>{user?.tenant?.name || "Tenant not loaded"}</span>
        </div>
        <button
          className="button button-secondary"
          onClick={() => {
            clearToken();
            router.push("/");
          }}
        >
          <LogOut size={16} />
          Logout
        </button>
      </div>
    </div>
  );
}
