<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\;
use App\Models\Council;
use App\Models\Member;
use App\Models\ParlSession;
use App\Models\Transcript;

class TranscriptFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transcript::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'externalId' => fake()->numberBetween(-10000, 10000),
            'text' => fake()->word(),
            'start' => fake()->dateTime(),
            'end' => fake()->dateTime(),
            'languageOfText' => fake()->word(),
            'business_id' => ::factory(),
            'parl_session_id' => ParlSession::factory(),
            'council_id' => Council::factory(),
            'member_id' => Member::factory(),
        ];
    }
}
