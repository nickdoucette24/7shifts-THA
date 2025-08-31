<?php
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testAsMinutes(): void
    {
        $this->assertSame(0, as_minutes('00:00'));
        $this->assertSame(630, as_minutes('10:30'));
        $this->assertSame(1439, as_minutes('23:59'));
    }

    public function testOverlaps(): void
    {
        $this->assertTrue(overlaps('10:00','12:00','11:00','11:30'));         // overlap
        $this->assertFalse(overlaps('10:00','12:00','12:00','14:00'));        // back-to-back ok
        $this->assertFalse(overlaps('08:00','09:00','10:00','11:00'));        // disjoint
    }
}
