<?php

namespace Database\Factories;

use App\Models\Universitas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Universitas>
 */
class UniversitasFactory extends Factory
{
    protected $model = Universitas::class;

    public function definition(): array
    {
        return [
            'nama_universitas' => fake()->unique()->company() . ' ' . fake()->unique()->city(),
            'logo'             => null,
        ];
    }
}
