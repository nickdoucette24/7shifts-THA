// HTTP request helper function
const http = async (path, { method = "GET", body } = {}) => {
  const res = await fetch(`api${path}`, {
    method,
    headers: { "Content-Type": "application/json" },
    body: body ? JSON.stringify(body) : undefined,
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data?.error?.message || `HTTP ${res.status}`);
  return data;
};

// API call helper functions
const get = (p) => http(p);
const post = (p, b) => http(p, { method: "POST", body: b });

// Object of API functions
export const api = {
  getStaff: () => get("/staff"),
  createStaff: (payload) => post("/staff", payload),
  getShifts: () => get("/shifts"),
  createShift: (payload) => post("/shifts", payload),
  assignShift: (shiftId, staffId) =>
    post(`/shifts/${shiftId}/assign`, { staffId }),
};

// Global roles
export const ROLES = ["server", "cook", "manager"];
