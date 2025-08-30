import { api } from "../api";

export default function ShiftList({ shifts, staff, onAssigned }) {
  if (!shifts.length) return <p>No shifts yet.</p>;

  function staffByRole(role) {
    return staff.filter((s) => s.role === role);
  }
  function nameOf(id) {
    return staff.find((s) => s.id === id)?.name || "";
  }

  async function assign(shiftId, staffId) {
    if (!staffId) return;
    await api.assignShift(shiftId, staffId);
    onAssigned?.();
  }

  return (
    <div className="card">
      <h2>Shifts</h2>
      <ul className="list">
        {shifts.map((sh) => (
          <li key={sh.id} className="row">
            <span className="grow">
              <strong>{sh.day}</strong> — {sh.start} to {sh.end} — ({sh.role})
            </span>
            {sh.assignedStaffId ? (
              <em> — Assigned: {nameOf(sh.assignedStaffId)}</em>
            ) : (
              <select
                defaultValue=""
                onChange={(e) => assign(sh.id, e.target.value)}
              >
                <option value="" disabled>
                  Select {sh.role}
                </option>
                {staffByRole(sh.role).map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.name}
                  </option>
                ))}
              </select>
            )}
          </li>
        ))}
      </ul>
    </div>
  );
}
