<?php

namespace Tests\Feature;
use App\Models\ItemsOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\TestCase;

class ItemsOrderTest extends TestCase
{
    /** @test */
    public function it_fails_when_expect_wrong_view()
    {
        $response = $this->get(route('itemsOrders.index'));

        // Isto vai falhar porque a view correta é 'itemsOrders.index'
        $response->assertViewIs('wrong.view.name');
    }
}