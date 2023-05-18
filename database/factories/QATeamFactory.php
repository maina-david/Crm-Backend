<?php

namespace Database\Factories;

use App\Models\QAForm;
use App\Models\QATeam;
use Illuminate\Database\Eloquent\Factories\Factory;

class QATeamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = QATeam::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "supervisor_id" => 54,
            "name" => $this->faker->name(),
            "description" => $this->faker->text(),
            "q_a_form_id" => QAForm::all()->random()->id,
            "company_id" => 11
        ];
    }
}