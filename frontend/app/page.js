"use client";

import { useState } from "react";
import { login, storeToken } from "@/lib/api";
import { useRouter } from "next/navigation";
import { ArrowRight, ShieldCheck } from "lucide-react";

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState("admin@demo.local");
  const [password, setPassword] = useState("password");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  async function handleSubmit(event) {
    event.preventDefault();
    setLoading(true);
    setError("");

    try {
      const response = await login(email, password);
      storeToken(response.data.token);
      router.push("/dashboard");
    } catch (requestError) {
      setError(requestError.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className="login-screen app-shell">
      <section className="login-hero">
        <div>
          <div className="tenant-pill" style={{ width: "fit-content", marginBottom: 18 }}>
            <ShieldCheck size={14} />
            <span>Industrial telemetry workspace</span>
          </div>
          <h1>Monitor device fleets, telemetry drift, and alert load from one operational screen.</h1>
          <p>
            Frontend ini mengikuti progress blueprint aktif: dashboard operasional, device inventory,
            detail telemetry, alert review, dan kiosk monitoring untuk layar 24/7.
          </p>
          <div className="hero-grid">
            <div className="hero-tile">
              <strong>10k+</strong>
              <span>target device per cluster</span>
            </div>
            <div className="hero-tile">
              <strong>1s</strong>
              <span>minimum telemetry interval</span>
            </div>
            <div className="hero-tile">
              <strong>HA</strong>
              <span>pipeline ready for horizontal scale</span>
            </div>
          </div>
        </div>
        <div className="muted">Demo credential mengikuti seed Laravel backend yang baru diaktifkan.</div>
      </section>

      <section className="login-panel">
        <div className="login-card card">
          <h2>Sign in</h2>
          <p>Use the seeded tenant admin account to access the dashboard.</p>
          <form onSubmit={handleSubmit}>
            <div className="field">
              <label htmlFor="email">Email</label>
              <input id="email" value={email} onChange={(event) => setEmail(event.target.value)} />
            </div>
            <div className="field">
              <label htmlFor="password">Password</label>
              <input
                id="password"
                type="password"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
              />
            </div>
            {error ? <div className="error-state" style={{ marginBottom: 16 }}>{error}</div> : null}
            <button className="button button-primary" style={{ width: "100%" }} disabled={loading}>
              <ArrowRight size={16} />
              {loading ? "Signing in..." : "Open dashboard"}
            </button>
          </form>
        </div>
      </section>
    </main>
  );
}
