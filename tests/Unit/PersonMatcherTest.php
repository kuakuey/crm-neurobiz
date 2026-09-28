<?php

namespace Tests\Unit;

use App\Services\PersonMatcher;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PersonMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_matches_by_normalized_phone(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        Person::factory()->create([
            'owner_id' => $user->id,
            'phone_raw' => '0980918462',
            'name' => 'Original',
        ]);

        $matched = app(PersonMatcher::class)->find(['phone' => '+593 98 091 8462']);
        $this->assertSame('Original', $matched?->name);
    }
}
