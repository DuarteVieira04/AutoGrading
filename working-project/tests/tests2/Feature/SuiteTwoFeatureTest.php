<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Suite tests2 — pasta `tests/tests2`. */
class SuiteTwoFeatureTest extends TestCase
{
    public function test_home_ok_in_suite_two(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
