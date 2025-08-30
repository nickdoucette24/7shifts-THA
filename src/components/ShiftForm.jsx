import { useState } from "react";
import { api } from "../api";

const ROLES = ["server", "cook", "manager"];

export default function ShiftForm({ onCreated }) {
  const [day, setDay] = useState("");
  const [start, setStart] = useState("");
  const [end, setEnd] = useState("");
  const [role, setRole] = useState(ROLES[0]);
  const [err, setErr] = useState("");

  async function submit(e) {
    e.preventDefault();
    setErr("");
    try {
      await api.createShift({ day, start, end, role });
      setDay("");
      setStart("");
      setEnd("");
      onCreated?.();
    } catch (e) {
      setErr(e.message);
    }
  }

  return (
    <form className="card form" onSubmit={submit}>
      <h2>Create Shift</h2>
      {err && (
        <div role="alert" className="error">
          {err}
        </div>
      )}
      <label>
        Day
        <input
          type="date"
          value={day}
          onChange={(e) => setDay(e.target.value)}
          required
        />
      </label>
      <label>
        Start
        <input
          type="time"
          value={start}
          onChange={(e) => setStart(e.target.value)}
          required
        />
      </label>
      <label>
        End
        <input
          type="time"
          value={end}
          onChange={(e) => setEnd(e.target.value)}
          required
        />
      </label>
      <label>
        Role
        <select value={role} onChange={(e) => setRole(e.target.value)}>
          {ROLES.map((r) => (
            <option key={r} value={r}>
              {r}
            </option>
          ))}
        </select>
      </label>
      <button type="submit">Create Shift</button>
    </form>
  );
}
