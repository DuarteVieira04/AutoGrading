<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Suite tests2 — pasta `tests/tests2`; diferente de tests1 para validar filtro por pasta. */
class SuiteTwoUnitTest extends TestCase
{
    public function test_suite_two_marker(): void
    {
        $this->assertSame('tests2', 'tests2');
    }
}
