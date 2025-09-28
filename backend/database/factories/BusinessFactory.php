<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Business;

class BusinessFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Business::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'externalId' => fake()->numberBetween(-10000, 10000),
            'shortNumber' => fake()->word(),
            'type' => fake()->numberBetween(-10000, 10000),
            'typeName' => fake()->word(),
            'typeAbbreviation' => fake()->word(),
            'title' => fake()->sentence(4),
            'description' => fake()->text(),
        ];
    }
}
