const BASE = "/api";

async function request(path, options = {}) {
  const res = await fetch(`${BASE}${path}`, {
    headers: { "Content-Type": "application/json" },
    ...options,
    body: options.body ? JSON.stringify(options.body) : undefined,
  });

  const data = await res.json(); // backend always returns JSON
  if (!res.ok) throw new Error(data?.error?.message || `HTTP ${res.status}`);
  return data;
}

export const api = {
  // Staff
  getStaff: () => request("/staff"),
  createStaff: (payload) =>
    request("/staff", { method: "POST", body: payload }),

  // Shifts
  getShifts: () => request("/shifts"),
  createShift: (payload) =>
    request("/shifts", { method: "POST", body: payload }),

  // Assign
  assignShift: (shiftId, staffId) =>
    request(`/shifts/${shiftId}/assign`, { method: "POST", body: { staffId } }),
};

export const ROLES = ["server", "cook", "manager"];
