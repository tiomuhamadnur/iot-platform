const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || "http://127.0.0.1:8000/api/v1";

function getToken() {
  if (typeof window === "undefined") {
    return "";
  }

  return window.localStorage.getItem("iot_platform_token") || "";
}

export async function apiRequest(path, options = {}) {
  const headers = new Headers(options.headers || {});
  headers.set("Content-Type", "application/json");

  const token = getToken();
  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
    cache: "no-store",
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.message || "Request failed");
  }

  return data;
}

export async function login(email, password) {
  return apiRequest("/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });
}

export function storeToken(token) {
  if (typeof window !== "undefined") {
    window.localStorage.setItem("iot_platform_token", token);
  }
}

export function clearToken() {
  if (typeof window !== "undefined") {
    window.localStorage.removeItem("iot_platform_token");
  }
}

export async function fetchCurrentUser() {
  return apiRequest("/auth/me");
}

export async function fetchDevices() {
  return apiRequest("/devices");
}

export async function fetchDevice(id) {
  return apiRequest(`/devices/${id}`);
}

export async function fetchTelemetry(id, params = {}) {
  const query = new URLSearchParams({
    from: params.from || new Date(Date.now() - 6 * 60 * 60 * 1000).toISOString(),
    to: params.to || new Date().toISOString(),
    limit: String(params.limit || 200),
  });

  if (params.params) {
    query.set("params", params.params);
  }

  return apiRequest(`/devices/${id}/telemetry?${query.toString()}`);
}

export async function fetchCommands(id) {
  return apiRequest(`/devices/${id}/commands`);
}
