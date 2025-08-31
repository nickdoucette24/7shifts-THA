<?php
use PHPUnit\Framework\TestCase;

final class ValidatorsTest extends TestCase
{
    private string $serverRoot;

    protected function setUp(): void
    {
        // Absolute path to server/ so we can bootstrap inside child PHP processes
        $root = realpath(__DIR__ . '/..');
        $this->assertNotFalse($root, 'Failed to resolve server root');
        $this->serverRoot = $root;
    }

    /**
     * Run a short PHP snippet in a separate PHP process.
     * Returns [combinedOutput, exitCode].
     */
    private function runPhp(string $snippet): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'phptest_');
        $bootstrap  = "<?php\n";
        $bootstrap .= "require '{$this->serverRoot}/src/helpers.php';\n";
        $bootstrap .= "require '{$this->serverRoot}/src/DataStore.php';\n";
        $bootstrap .= "require '{$this->serverRoot}/src/Validators.php';\n";
        file_put_contents($tmp, $bootstrap . $snippet . "\n");

        $cmd = 'php ' . escapeshellarg($tmp) . ' 2>&1';
        $lines = [];
        $code  = 0;
        exec($cmd, $lines, $code);
        @unlink($tmp);
        return [implode("\n", $lines), $code];
    }

    /* ---------- Success paths (direct calls) ---------- */

    public function testValidateStaffSuccess(): void
    {
        $out = validate_staff([
            'name'  => 'Jane Doe',
            'role'  => 'server',
            'phone' => '5551234567',
        ]);
        $this->assertSame('Jane Doe', $out['name']);
        $this->assertSame('server',   $out['role']);
        $this->assertMatchesRegularExpression('/^\d{10,}$/', (string)$out['phone']);
    }

    public function testValidateShiftSuccess(): void
    {
        $out = validate_shift([
            'day'   => '2025-08-27',
            'start' => '10:00',
            'end'   => '12:00',
            'role'  => 'server',
        ]);
        $this->assertSame('2025-08-27', $out['day']);
        $this->assertSame('10:00',      $out['start']);
        $this->assertSame('12:00',      $out['end']);
        $this->assertSame('server',     $out['role']);
        $this->assertArrayHasKey('assignedStaffId', $out);
        $this->assertNull($out['assignedStaffId']);
    }

    public function testValidateAssignSuccess(): void
    {
        $out = validate_assign(['staffId' => 'abc123']);
        $this->assertSame('abc123', $out['staffId']);
    }

    public function testAssignShiftHappyPath(): void
    {
        $tmp = sys_get_temp_dir() . '/validators_' . uniqid();
        mkdir($tmp);
        try {
            $ds    = new DataStore($tmp);
            $staff = $ds->create('staff', ['name'=>'Jane','role'=>'server','phone'=>'5551234567']);
            $shift = $ds->create('shifts', [
                'day'=>'2025-08-27','start'=>'10:00','end'=>'12:00','role'=>'server','assignedStaffId'=>null
            ]);

            $updated = assign_shift($ds, $shift['id'], $staff['id']);

            $this->assertSame($staff['id'], $updated['assignedStaffId']);
            $all = $ds->getAll('shifts');
            $this->assertSame($staff['id'], $all[0]['assignedStaffId']);
        } finally {
            foreach (glob($tmp.'/*.json') ?: [] as $f) { @unlink($f); }
            @rmdir($tmp);
        }
    }

    /* ---------- Error paths (run in external PHP process) ---------- */

    public function testValidateStaffRejectsBadRole(): void
    {
        [$out, $code] = $this->runPhp(<<<'PHP'
validate_staff([
  'name'  => 'Jane Doe',
  'role'  => 'bartender', // invalid
  'phone' => '5551234567',
]);
PHP);
        $this->assertStringContainsString('"Validation failed"', $out);
        $this->assertStringContainsString('"role"', $out);
        $this->assertSame(0, $code); // json_response exits without a non-zero code
    }

    public function testValidateShiftRejectsStartAfterEnd(): void
    {
        [$out, $code] = $this->runPhp(<<<'PHP'
validate_shift([
  'day'   => '2025-08-27',
  'start' => '12:00',
  'end'   => '10:00',
  'role'  => 'server',
]);
PHP);
        $this->assertStringContainsString('"Validation failed"', $out);
        $this->assertStringContainsString('"time"', $out);
        $this->assertStringContainsString('Start must be before end', $out);
        $this->assertSame(0, $code);
    }

    public function testAssignShiftRejectsRoleMismatch(): void
    {
        [$out, $code] = $this->runPhp(<<<'PHP'
$tmp = sys_get_temp_dir() . '/role_mismatch_' . uniqid();
mkdir($tmp);
file_put_contents("$tmp/staff.json", "[]");
file_put_contents("$tmp/shifts.json", "[]");

$ds = new DataStore($tmp);
$staff = $ds->create('staff', ['name'=>'C','role'=>'cook','phone'=>'5551234567']);
$shift = $ds->create('shifts', [
  'day'=>'2025-08-27','start'=>'10:00','end'=>'12:00','role'=>'server','assignedStaffId'=>null
]);

assign_shift($ds, $shift['id'], $staff['id']);
PHP);
        $this->assertStringContainsString('Role mismatch', $out);
        $this->assertSame(0, $code);
    }

    public function testAssignShiftRejectsOverlap(): void
    {
        [$out, $code] = $this->runPhp(<<<'PHP'
$tmp = sys_get_temp_dir() . '/overlap_' . uniqid();
mkdir($tmp);
file_put_contents("$tmp/staff.json", "[]");
file_put_contents("$tmp/shifts.json", "[]");

$ds = new DataStore($tmp);
$staff = $ds->create('staff', ['name'=>'S','role'=>'server','phone'=>'5551234567']);

$ds->create('shifts', [
  'day'=>'2025-08-27','start'=>'10:00','end'=>'12:00','role'=>'server','assignedStaffId'=>$staff['id']
]);

$sh2 = $ds->create('shifts', [
  'day'=>'2025-08-27','start'=>'11:00','end'=>'13:00','role'=>'server','assignedStaffId'=>null
]);

assign_shift($ds, $sh2['id'], $staff['id']);
PHP);
        $this->assertStringContainsString('overlaps an existing assignment', $out);
        $this->assertSame(0, $code);
    }
}
