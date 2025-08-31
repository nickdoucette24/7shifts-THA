import { useState, useEffect } from "react";
import { api } from "./api";
import StaffForm from "./components/StaffForm.jsx";
import StaffList from "./components/StaffList.jsx";
import ShiftForm from "./components/ShiftForm.jsx";
import ShiftList from "./components/ShiftList.jsx";
import "./styles/App.scss";

function App() {
  const [tab, setTab] = useState("staff");
  const [staff, setStaff] = useState([]);
  const [shifts, setShifts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  async function refresh() {
    setLoading(true);
    setError("");
    try {
      // Parallel fetching and loading
      const [staffData, shiftsData] = await Promise.all([
        api.getStaff(),
        api.getShifts(),
      ]);
      setStaff(staffData);
      setShifts(shiftsData);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }

  // Initial page load
  useEffect(() => {
    refresh();
  }, []);

  return (
    <div className="container">
      <h1 className="container__header">7shifts — Staff Scheduling</h1>

      <nav className="tabs">
        <button
          className={tab === "staff" ? "active" : ""}
          onClick={() => setTab("staff")}
        >
          Staff
        </button>
        <button
          className={tab === "shifts" ? "active" : ""}
          onClick={() => setTab("shifts")}
        >
          Shifts
        </button>
      </nav>

      {error && (
        <div role="alert" className="error">
          {error}
        </div>
      )}
      {loading ? (
        <p>Loading…</p>
      ) : tab === "staff" ? (
        <section>
          <StaffForm onCreated={refresh} />
          <StaffList staff={staff} />
        </section>
      ) : (
        <section>
          <ShiftForm onCreated={refresh} />
          <ShiftList shifts={shifts} staff={staff} onAssigned={refresh} />
        </section>
      )}
    </div>
  );
}

export default App;
