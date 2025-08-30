<?php
declare(strict_types=1);

class DataStore {
  // Directory for JSON file database
  private string $dir;
  public function __construct(string $dir) { $this->dir = $dir; }
  private function path(string $key): string { return $this->dir . '/' . $key . '.json'; }

  // Read data from JSON file with $key being the distinguishing factor
  private function read(string $key): array {
    $p = $this->path($key);
    if (!file_exists($p)) return [];
    $data = json_decode(file_get_contents($p), true);
    // return full array if valid, empty array if not
    return is_array($data) ? $data : [];
  }

  // Write data to JSON file, using Pretty Print for readability. Would use SQL in production.
  private function write(string $key, array $rows): void {
    file_put_contents($this->path($key), json_encode($rows, JSON_PRETTY_PRINT));
  }

  // Get all records for a given key
  public function getAll(string $key): array { return $this->read($key); }

  // Find a record by its ID
  public function findById(string $key, string $id): ?array {
    foreach ($this->read($key) as $row) if (($row['id'] ?? null) === $id) return $row;
    return null;
  }

  // Update or insert a record by its ID
  public function upsert(string $key, array $row): array {
    $rows = $this->read($key);
    $found = false;
    // Iterate through existing rows
    for ($i=0; $i<count($rows); $i++) {
      if (($rows[$i]['id'] ?? null) === $row['id']) { $rows[$i] = $row; $found = true; break; }
    }
    if (!$found) $rows[] = $row;
    $this->write($key, $rows);
    return $row;
  }

  // Create a new record with a unique ID
  public function create(string $key, array $payload): array {
    $row = $payload;
    $row['id'] = bin2hex(random_bytes(16)); // 32-hex id
    return $this->upsert($key, $row);
  }
}
