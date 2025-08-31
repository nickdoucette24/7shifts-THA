import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";
import StaffList from "../../components/StaffList.jsx";

describe("StaffList", () => {
  it("renders staff entries", () => {
    render(
      <StaffList
        staff={[
          { id: "s1", name: "Jane", role: "server", phone: "5551234567" },
          { id: "s2", name: "Bob", role: "cook", phone: "5557654321" },
        ]}
      />
    );

    expect(screen.getByText(/Jane/i)).toBeInTheDocument();
    expect(screen.getByText(/server/i)).toBeInTheDocument();
    expect(screen.getByText(/5551234567/)).toBeInTheDocument();

    expect(screen.getByText(/Bob/i)).toBeInTheDocument();
    expect(screen.getByText(/cook/i)).toBeInTheDocument();
  });

  it("shows empty state", () => {
    render(<StaffList staff={[]} />);
    expect(screen.getByText(/no staff members yet\./i)).toBeInTheDocument();
  });
});
