const BASE = "/api";

const http = async (path, { method = "GET", body } = {}) => {
  const res = await fetch(`${BASE}${path}`, {
    method,
    headers: { "Content-Type": "application/json" },
    body: body ? JSON.stringify(body) : undefined,
  });

  const data = await res.json();
  if (!res.ok) throw new Error(data?.error?.message || `HTTP ${res.status}`);
  return data;
};

const get = (p) => http(p);
const post = (p, b) => http(p, { method: "POST", body: b });

export const api = {
  // Staff
  getStaff: () => get("/staff"),
  createStaff: (payload) => post("/staff", payload),

  // Shifts
  getShifts: () => get("/shifts"),
  createShift: (payload) => post("/shifts", payload),

  // Assign
  assignShift: (shiftId, staffId) =>
    post(`/shifts/${shiftId}/assign`, { staffId }),
};

export const ROLES = ["server", "cook", "manager"];
