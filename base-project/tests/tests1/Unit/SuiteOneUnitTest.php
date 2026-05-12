<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Suite tests1 — pasta `tests/tests1`. */
class SuiteOneUnitTest extends TestCase
{
    public function test_suite_one_marker(): void
    {
        $this->assertSame('tests1', 'tests1');
    }
}
