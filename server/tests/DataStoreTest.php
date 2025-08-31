<?php
use PHPUnit\Framework\TestCase;

final class DataStoreTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/datastore_' . uniqid();
        mkdir($this->tmp);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmp . '/*.json') ?: [] as $f) { @unlink($f); }
        @rmdir($this->tmp);
    }

    public function testCreateAndFindById(): void
    {
        $ds = new DataStore($this->tmp);

        $row = $ds->create('staff', ['name' => 'Jane', 'role' => 'server', 'phone' => '5551234567']);
        $this->assertIsArray($row);
        $this->assertArrayHasKey('id', $row);
        $this->assertSame(32, strlen($row['id']));

        $found = $ds->findById('staff', $row['id']);
        $this->assertNotNull($found);
        $this->assertSame('Jane', $found['name']);
    }

    public function testUpsertUpdatesExistingRow(): void
    {
        $ds = new DataStore($this->tmp);

        $a = $ds->create('shifts', [
            'day'=>'2025-08-27','start'=>'10:00','end'=>'12:00','role'=>'server','assignedStaffId'=>null
        ]);
        $a['end'] = '13:00';

        $saved = $ds->upsert('shifts', $a);
        $this->assertSame('13:00', $saved['end']);

        $all = $ds->getAll('shifts');
        $this->assertCount(1, $all);
        $this->assertSame('13:00', $all[0]['end']);
    }

    public function testGetAllReturnsAllRows(): void
    {
        $ds = new DataStore($this->tmp);
        $ds->create('staff', ['name'=>'A','role'=>'server','phone'=>'1']);
        $ds->create('staff', ['name'=>'B','role'=>'cook','phone'=>'2']);

        $all = $ds->getAll('staff');
        $this->assertIsArray($all);
        $this->assertCount(2, $all);
        $this->assertSame(['A','B'], array_column($all, 'name'));
    }
}
