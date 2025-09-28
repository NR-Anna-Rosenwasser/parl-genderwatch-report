<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\ParlGroup;

class ParlGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ParlGroup::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'externalId' => fake()->numberBetween(-10000, 10000),
            'number' => fake()->numberBetween(-10000, 10000),
            'isActive' => fake()->boolean(),
            'code' => fake()->word(),
            'name' => fake()->name(),
            'abbreviation' => fake()->word(),
            'nameUsedSince' => fake()->dateTime(),
            'modified' => fake()->dateTime(),
            'colour' => fake()->regexify('[A-Za-z0-9]{8}'),
        ];
    }
}
