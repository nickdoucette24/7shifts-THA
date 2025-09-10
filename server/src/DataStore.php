<?php
declare(strict_types=1);

final class DataStore
{
    private \PDO $pdo;

    public function __construct(string $dataDir)
    {
        $dbPath = rtrim($dataDir, '/').'/app.db';
        @mkdir($dataDir, 0777, true);

        $this->pdo = new \PDO('sqlite:'.$dbPath);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->initSchema();
    }

    private function initSchema(): void
    {
        $sql = file_exists(__DIR__.'/../db/schema.sql')
            ? file_get_contents(__DIR__.'/../db/schema.sql')
            : "
            PRAGMA foreign_keys = ON;
            CREATE TABLE IF NOT EXISTS staff (
              id TEXT PRIMARY KEY,
              name TEXT NOT NULL,
              role TEXT NOT NULL CHECK (role IN ('server','cook','manager')),
              phone TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS shifts (
              id TEXT PRIMARY KEY,
              day TEXT NOT NULL,
              start TEXT NOT NULL,
              end TEXT NOT NULL,
              role TEXT NOT NULL CHECK (role IN ('server','cook','manager')),
              assigned_staff_id TEXT NULL,
              FOREIGN KEY (assigned_staff_id) REFERENCES staff(id) ON DELETE RESTRICT
            );
            ";
        $this->pdo->exec($sql);
    }

    private function newId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function getAll(string $name): array
    {
        switch ($name) {
            case 'staff':
                $stmt = $this->pdo->query(
                    "SELECT id, name, role, phone
                     FROM staff
                     ORDER BY name"
                );
                return $stmt->fetchAll();

            case 'shifts':
                $stmt = $this->pdo->query(
                    "SELECT id, day, start, end, role,
                            assigned_staff_id AS assignedStaffId
                     FROM shifts
                     ORDER BY day, start"
                );
                return $stmt->fetchAll();

            default:
                return [];
        }
    }

    public function findById(string $name, string $id): ?array
    {
        switch ($name) {
            case 'staff':
                $stmt = $this->pdo->prepare(
                    "SELECT id, name, role, phone FROM staff WHERE id = :id"
                );
                break;
            case 'shifts':
                $stmt = $this->pdo->prepare(
                    "SELECT id, day, start, end, role,
                            assigned_staff_id AS assignedStaffId
                     FROM shifts WHERE id = :id"
                );
                break;
            default:
                return null;
        }
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $name, array $row): array
    {
        if ($name === 'staff') {
            $id = $this->newId();
            $stmt = $this->pdo->prepare(
                "INSERT INTO staff (id, name, role, phone)
                 VALUES (:id, :name, :role, :phone)"
            );
            $stmt->execute([
                ':id'    => $id,
                ':name'  => $row['name'],
                ':role'  => $row['role'],
                ':phone' => $row['phone'],
            ]);
            return ['id'=>$id] + $row;
        }

        if ($name === 'shifts') {
            $id = $this->newId();
            $stmt = $this->pdo->prepare(
                "INSERT INTO shifts (id, day, start, end, role, assigned_staff_id)
                 VALUES (:id, :day, :start, :end, :role, :assigned)"
            );
            $stmt->execute([
                ':id'       => $id,
                ':day'      => $row['day'],
                ':start'    => $row['start'],
                ':end'      => $row['end'],
                ':role'     => $row['role'],
                ':assigned' => $row['assignedStaffId'] ?? null,
            ]);
            return ['id'=>$id] + $row;
        }

        return [];
    }

    public function upsert(string $name, array $row): array
    {
        if ($name === 'staff') {
            $stmt = $this->pdo->prepare(
                "UPDATE staff
                 SET name = :name, role = :role, phone = :phone
                 WHERE id = :id"
            );
            $stmt->execute([
                ':name'  => $row['name'],
                ':role'  => $row['role'],
                ':phone' => $row['phone'],
                ':id'    => $row['id'],
            ]);
            return $this->findById('staff', $row['id']);
        }

        if ($name === 'shifts') {
            $stmt = $this->pdo->prepare(
                "UPDATE shifts
                 SET day = :day, start = :start, end = :end, role = :role,
                     assigned_staff_id = :assigned
                 WHERE id = :id"
            );
            $stmt->execute([
                ':day'      => $row['day'],
                ':start'    => $row['start'],
                ':end'      => $row['end'],
                ':role'     => $row['role'],
                ':assigned' => $row['assignedStaffId'] ?? null,
                ':id'       => $row['id'],
            ]);
            return $this->findById('shifts', $row['id']);
        }

        return [];
    }

    public function delete(string $name, string $id): bool
    {
        switch ($name) {
            case 'staff':
                $stmt = $this->pdo->prepare("DELETE FROM staff WHERE id = :id");
                break;
            case 'shifts':
                $stmt = $this->pdo->prepare("DELETE FROM shifts WHERE id = :id");
                break;
            default:
                return false;
        }
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function pdo(): \PDO { return $this->pdo; }
}
