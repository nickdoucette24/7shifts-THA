PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS staff (
  id    TEXT PRIMARY KEY,
  name  TEXT NOT NULL,
  role  TEXT NOT NULL CHECK (role IN ('server','cook','manager')),
  phone TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS shifts (
  id      TEXT PRIMARY KEY,
  day     TEXT NOT NULL,
  start   TEXT NOT NULL,
  end     TEXT NOT NULL,
  role    TEXT NOT NULL CHECK (role IN ('server','cook','manager')),
  assigned_staff_id TEXT NULL,
  FOREIGN KEY (assigned_staff_id) REFERENCES staff(id) ON DELETE RESTRICT
);