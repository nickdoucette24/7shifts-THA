import { describe, it, expect, vi } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import userEvent from "@testing-library/user-event";

vi.mock("../../api.js", () => ({
  api: { createShift: vi.fn().mockResolvedValue({ id: "sh1" }) },
  ROLES: ["server", "cook", "manager"],
}));
import { api } from "../../api.js";
import ShiftForm from "../../components/ShiftForm.jsx";

describe("ShiftForm", () => {
  it("creates a shift and calls onCreated", async () => {
    const user = userEvent.setup();
    const onCreated = vi.fn();

    render(<ShiftForm onCreated={onCreated} />);

    // date inputs can be finicky; set value via change
    const day = screen.getByLabelText(/day/i);
    fireEvent.change(day, { target: { value: "2025-08-27" } });

    await user.type(screen.getByLabelText(/^start$/i), "10:00");
    await user.type(screen.getByLabelText(/^end$/i), "16:00");
    await user.selectOptions(screen.getByLabelText(/role/i), "server");

    await user.click(screen.getByRole("button", { name: /create shift/i }));

    expect(api.createShift).toHaveBeenCalledWith({
      day: "2025-08-27",
      start: "10:00",
      end: "16:00",
      role: "server",
    });
    expect(onCreated).toHaveBeenCalled();
  });
});
