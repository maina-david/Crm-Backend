<?php

namespace Database\Factories;

use App\Models\QATeam;
use App\Models\QATeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QATeamMemberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = QATeamMember::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "q_a_team_id" => QATeam::all()->random()->id,
            "member_id" => User::all()->random()->id
        ];
    }
}