import { useState } from "react";
import { api, ROLES } from "../api";

const StaffForm = ({ onCreated }) => {
  const [name, setName] = useState("");
  const [role, setRole] = useState("");
  const [phone, setPhone] = useState("");
  const [err, setErr] = useState("");

  const submit = async (e) => {
    e.preventDefault();
    setErr("");
    try {
      await api.createStaff({
        name: name.trim(),
        role,
        phone: phone.trim(),
      });
      setName("");
      setPhone("");
      setRole("");
      onCreated();
    } catch (e) {
      setErr(e.message);
    }
  };

  return (
    <form className="card form" onSubmit={submit}>
      <h2>Add Staff</h2>
      {err && (
        <div role="alert" className="error">
          {err}
        </div>
      )}
      <label>
        Name
        <input
          type="text"
          value={name}
          onChange={(e) => setName(e.target.value)}
          required
        />
      </label>
      <label>
        Role
        <select value={role} onChange={(e) => setRole(e.target.value)} required>
          <option value="" disabled>
            Select role
          </option>
          {ROLES.map((r) => (
            <option key={r} value={r}>
              {r}
            </option>
          ))}
        </select>
      </label>
      <label>
        Phone
        <input
          type="tel"
          placeholder="555-123-4567"
          value={phone}
          onChange={(e) => setPhone(e.target.value)}
          required
        />
      </label>
      <button type="submit">Create Staff</button>
    </form>
  );
};

export default StaffForm;
