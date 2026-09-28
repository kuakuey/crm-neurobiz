<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    public function definition(): array
    {
        $phone = '09'.fake()->numerify('########');

        return [
            'owner_id' => User::factory(),
            'name' => fake()->name(),
            'phone_raw' => $phone,
            'email' => fake()->unique()->safeEmail(),
            'source' => 'manual',
        ];
    }
}
