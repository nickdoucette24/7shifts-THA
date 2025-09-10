<?php
declare(strict_types=1);

// Staff validation
function validate_staff(array $input): array {
  $roles = ['server','cook','manager'];
  $name  = trim((string)($input['name']  ?? ''));
  $role  = (string)($input['role']  ?? '');
  $phone = preg_replace('/\D+/', '', (string)($input['phone'] ?? ''));
  $err = [];
  if ($name === '') $err['name'] = 'Name is required.';
  if (!in_array($role, $roles, true)) $err['role'] = 'Role must be server|cook|manager.';
  if (strlen($phone) < 10) $err['phone'] = 'Phone number should have at least 10 digits.';
  if (strlen($phone) > 15) $err['phone'] = 'Phone number should not exceed 15 digits.';
  if ($err) json_response(['error'=>['message'=>'Validation failed','fields'=>$err]], 422);
  return ['name'=>$name,'role'=>$role,'phone'=>$phone];
}

// Shift validation
function validate_shift(array $input): array {
  $roles = ['server','cook','manager'];
  $day   = (string)($input['day']   ?? '');
  $start = (string)($input['start'] ?? '');
  $end   = (string)($input['end']   ?? '');
  $role  = (string)($input['role']  ?? '');
  $err = [];
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day))   $err['day']   = 'Use YYYY-MM-DD.';
  if (!preg_match('/^\d{2}:\d{2}$/', $start))       $err['start'] = 'Use HH:MM.';
  if (!preg_match('/^\d{2}:\d{2}$/', $end))         $err['end']   = 'Use HH:MM.';
  if (preg_match('/^\d{2}:\d{2}$/',$start) && preg_match('/^\d{2}:\d{2}$/',$end) && as_minutes($start) >= as_minutes($end)) {
    $err['time'] = 'Start must be before end.';
  }
  if (!in_array($role, $roles, true)) $err['role'] = 'Role must be server|cook|manager.';
  if ($err) json_response(['error'=>['message'=>'Validation failed','fields'=>$err]], 422);
  return ['day'=>$day,'start'=>$start,'end'=>$end,'role'=>$role,'assignedStaffId'=>null];
}

// Assignment validation
function validate_assign(array $input): array {
  $staffId = (string)($input['staffId'] ?? '');
  if ($staffId === '') json_response(['error'=>['message'=>'staffId is required']], 422);
  return ['staffId'=>$staffId];
}

// Assign a shift to a staff member validation
function assign_shift(DataStore $store, string $shiftId, string $staffId): array {
  $shift = $store->findById('shifts', $shiftId);
  if (!$shift) json_response(['error'=>['message'=>'Shift not found']], 404);
  $staff = $store->findById('staff', $staffId);
  if (!$staff) json_response(['error'=>['message'=>'Staff not found']], 404);
  if (($shift['role'] ?? null) !== ($staff['role'] ?? null)) {
    json_response(['error'=>['message'=>'Role mismatch: cannot assign']], 422);
  }

  // Prevent overlap for same staff + same day
  // ! New DB-backed validation
  $q = $store->pdo()->prepare("
    SELECT 1
    FROM shifts
    WHERE assigned_staff_id = :staffId
      AND day = :day
      AND id <> :id
      AND start < :end
      AND :start < end
    LIMIT 1
  ");

  $q->execute([
    ':staffId' => $staffId,
    ':day'     => $shift['day'],
    ':id'      => $shift['id'],
    ':start'   => $shift['start'],
    ':end'     => $shift['end'],
  ]);

  if ($q->fetch()) {
    json_response(['error'=>['message'=>'Shift overlaps an existing assignment']], 422);
  }

  $shift['assignedStaffId'] = $staffId;
  return $store->upsert('shifts', $shift);
}
