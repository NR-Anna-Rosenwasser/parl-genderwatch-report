<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Canton;
use App\Models\Council;
use App\Models\Member;
use App\Models\ParlGroup;
use App\Models\Party;

class MemberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Member::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'externalId' => fake()->numberBetween(-10000, 10000),
            'externalPersonId' => fake()->numberBetween(-10000, 10000),
            'isActive' => fake()->boolean(),
            'firstName' => fake()->word(),
            'lastName' => fake()->word(),
            'genderAsString' => fake()->word(),
            'dateJoining' => fake()->dateTime(),
            'dateLeaving' => fake()->dateTime(),
            'dateElection' => fake()->dateTime(),
            'party_id' => Party::factory(),
            'parl_group_id' => ParlGroup::factory(),
            'canton_id' => Canton::factory(),
            'council_id' => Council::factory(),
        ];
    }
}
