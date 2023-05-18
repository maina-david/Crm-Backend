<?php

namespace Database\Factories;

use App\Models\QAForm;
use Illuminate\Database\Eloquent\Factories\Factory;

class QAFormFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = QAForm::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'company_id' => 11,
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
        ];
    }
}
