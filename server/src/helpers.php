<?php
declare(strict_types=1);

// Send a JSON response with status code and exit
// Everything will return the same shape, consolidates header logic, encodes whatever data structure is passed in and exits
// ! I'd use a global exception handler in production
function json_response($data, int $status = 200): void {
  header('Content-Type: application/json');
  http_response_code($status);
  echo json_encode($data);
  exit;
}

// Read JSON input and return an array
// Ensures a safe call without extra isset checks because an empty array is fine for validation
function read_json(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

// Convert HH:MM to minutes to ensure clean overlap logic + O1
function as_minutes(string $hhmm): int {
  [$h,$m] = explode(':', $hhmm);
  return (int)$h * 60 + (int)$m;
}

// Check if time ranges overlap with a current shift
// Overnight shifts are not considered for take-home assignment simplicity
// ! For overnight shifts I'd carry the actual day into this
function overlaps(string $s1, string $e1, string $s2, string $e2): bool {
  $a1 = as_minutes($s1); $b1 = as_minutes($e1);
  $a2 = as_minutes($s2); $b2 = as_minutes($e2);
  return $a1 < $b2 && $a2 < $b1;
}
