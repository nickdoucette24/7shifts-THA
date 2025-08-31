import { useState } from "react";
import { api } from "../api";

const ROLES = ["server", "cook", "manager"];

export default function ShiftForm({ onCreated }) {
  const [day, setDay] = useState("");
  const [start, setStart] = useState("");
  const [end, setEnd] = useState("");
  const [role, setRole] = useState("");
  const [err, setErr] = useState("");

  async function submit(e) {
    e.preventDefault();
    setErr("");
    try {
      await api.createShift({ day, start, end, role });
      setDay("");
      setStart("");
      setEnd("");
      setRole("");
      onCreated?.();
    } catch (e) {
      setErr(e.message);
    }
  }

  return (
    <form className="card form" onSubmit={submit}>
      <h2 className="form__title">Create Shift</h2>
      <div className="form__fields">
        {err && (
          <div role="alert" className="error">
            {err}
          </div>
        )}
        <div className="form__info-fields">
          <label className="form__label">
            Day
            <input
              className="form__select"
              type="date"
              value={day}
              onChange={(e) => setDay(e.target.value)}
              required
            />
          </label>
          <label className="form__label">
            Role
            <select
              className="form__select"
              value={role}
              onChange={(e) => setRole(e.target.value)}
            >
              <option value="" disabled>
                Select role
              </option>
              {ROLES.map((role) => (
                <option key={role} value={role}>
                  {role}
                </option>
              ))}
            </select>
          </label>
        </div>
        <div className="form__time-fields">
          <label className="form__label">
            Start
            <input
              className="form__select"
              type="time"
              value={start}
              onChange={(e) => setStart(e.target.value)}
              required
            />
          </label>
          <label className="form__label">
            End
            <input
              className="form__select"
              type="time"
              value={end}
              onChange={(e) => setEnd(e.target.value)}
              required
            />
          </label>
        </div>
      </div>
      <button className="form__button" type="submit">
        Create Shift
      </button>
    </form>
  );
}
