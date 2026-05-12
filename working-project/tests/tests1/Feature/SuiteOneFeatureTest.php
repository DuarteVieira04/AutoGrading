<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Suite tests1 — pasta `tests/tests1`. */
class SuiteOneFeatureTest extends TestCase
{
    public function test_home_ok_in_suite_one(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
