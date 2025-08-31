import { describe, it, expect, vi } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";

vi.mock("../../api.js", () => ({
  api: { assignShift: vi.fn().mockResolvedValue({}) },
}));
import { api } from "../../api.js";
import ShiftList from "../../components/ShiftList.jsx";

describe("ShiftList", () => {
  it("assigns a staff member when selected", async () => {
    const user = userEvent.setup();
    const onAssigned = vi.fn();

    const shifts = [
      {
        id: "sh1",
        day: "2025-08-27",
        start: "10:00",
        end: "12:00",
        role: "server",
        assignedStaffId: null,
      },
    ];
    const staff = [
      { id: "s1", name: "Jane", role: "server", phone: "5551234567" },
    ];

    render(<ShiftList shifts={shifts} staff={staff} onAssigned={onAssigned} />);

    // Find the "Select server" dropdown then choose Jane
    const select = screen.getByText(/select server/i).closest("select");
    if (select) {
      await user.selectOptions(select, "s1");
    }

    expect(api.assignShift).toHaveBeenCalledWith("sh1", "s1");

    await waitFor(() => expect(onAssigned).toHaveBeenCalled());
  });
});
