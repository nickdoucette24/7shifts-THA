export default function StaffList({ staff }) {
  if (!staff.length) return <p>No staff members yet.</p>;
  return (
    <div className="card">
      <h2>All Staff</h2>
      <ul className="list">
        {staff.map((staffMember) => (
          <li key={staffMember.id} className="row">
            <span className="grow">
              <strong>{staffMember.name}</strong> — {staffMember.role}
            </span>{" "}
            — <span>{staffMember.phone}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}
