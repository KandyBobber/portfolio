<?php

namespace Database\Factories;

use App\Models\People;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDateCH = Carbon::createFromFormat('Y-m-d', '2020-01-01');
        $endDateCH = Carbon::createFromFormat('Y-m-d', '2024-10-10');
        $startDateBT = Carbon::createFromFormat('Y-m-d', '1970-01-01');
        $endDateBT = Carbon::createFromFormat('Y-m-d', '2001-12-31');

        return [
            'people_id' => People::get()->random()->id,
            'last_name' => Str::random(6),
            'first_name' => Str::random(4),
            'blood' => $this->faker->bloodGroup(),
            'birth_date' => $this->faker->dateTimeBetween($startDateBT, $endDateBT),
            'change_date' => $this->faker->dateTimeBetween($startDateCH, $endDateCH),
            'photo' => $this->faker->imageUrl(),
            'middle_name' => Str::random(7),
        ];
    }
}
