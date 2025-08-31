import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";

// Mock the API module used by the component
vi.mock("../../api.js", () => ({
  api: { createStaff: vi.fn().mockResolvedValue({ id: "s1" }) },
  ROLES: ["server", "cook", "manager"],
}));
import { api } from "../../api.js";
import StaffForm from "../../components/StaffForm.jsx";

describe("StaffForm", () => {
  it("submits valid staff and calls onCreated", async () => {
    const user = userEvent.setup();
    const onCreated = vi.fn();

    render(<StaffForm onCreated={onCreated} />);

    // Fill fields
    await user.type(screen.getByLabelText(/name/i), "Jane");
    await user.selectOptions(screen.getByLabelText(/role/i), "server");
    await user.type(screen.getByLabelText(/phone/i), "5551234567");

    // Submit
    await user.click(screen.getByRole("button", { name: /create staff/i }));

    expect(api.createStaff).toHaveBeenCalledWith({
      name: "Jane",
      role: "server",
      phone: "5551234567",
    });
    expect(onCreated).toHaveBeenCalled();
  });
});
