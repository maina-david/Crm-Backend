<?php

namespace App\Http\Controllers\quality_assurance;

use App\Http\Controllers\Controller;
use App\Http\Resources\QATeamResource;
use App\Models\QAForm;
use App\Models\QATeam;
use App\Models\QATeamMember;
use App\Models\QATeamQueue;
use App\Models\QATeamSupervisor;
use App\Models\Queue;
use App\Models\User;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;

class QATeamController extends Controller
{
    /**
     * It creates a quality assurance team
     * 
     * @param Request request This is the request object that contains the data that was sent to the
     * API.
     * 
     * @return A JSON response with the message "Successfully added" and a status code of 200.
     */
    public function create_q_a_team(Request $request)
    {
        $request->validate([
            "name" => "required|string",
            "description" => "required|string"
        ]);

        $check_duplicate = QATeam::where(["name" => $request->name, "company_id" => Auth::user()->company_id])->first();

        if ($check_duplicate) {
            return response()->json(['message' => "You have Quality assurance team with the same name!"], 422);
        }

        $QA_Team = QATeam::create([
            "name" => $request->name,
            "description" => $request->description,
            "company_id" => Auth::user()->company_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quality Assurance team created successfully!'
        ], 200);
    }

    /**
     * It updates a quality assurance team
     * 
     * @param Request request The request object
     */
    public function update_q_a_team(Request $request)
    {
        $request->validate([
            "q_a_team_id" => "required|exists:q_a_teams,id",
            "name" => "required|string",
            "description" => "required|string"
        ]);

        $check_duplicate = QATeam::where(["name" => $request->name, "company_id" => Auth::user()->company_id])->first();

        if ($check_duplicate) {
            if ($check_duplicate->id != $request->q_a_team_id)
                return response()->json(['message' => "You have Quality assurance team with the same name!"], 422);
        }

        $item_update = QATeam::find($request->q_a_team_id);
        if ($item_update->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "The team doesn't belong to your company!"], 401);
        }
        $item_update->update([
            "name" => $request->name,
            "description" => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quality Assurance team updated successfully!'
        ], 200);
    }

    /**
     * It gets all the QA teams for the company that the user is logged in to
     * 
     * @return A collection of QA Teams
     */
    public function get_q_a_teams()
    {
        $qa_teams = QATeam::withcount('team_members', 'team_supervisors')
            ->where("company_id", Auth::user()->company_id)
            ->get();

        return response()->json(QATeamResource::collection($qa_teams), 200);
    }

    /**
     * It takes a quality assurance team id and a quality assurance form id and updates the quality
     * assurance team with the form id
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a message and a status code.
     */
    public function add_form_to_q_a_team(Request $request)
    {
        $request->validate([
            "q_a_team_id" => "required|exists:q_a_teams,id",
            "q_a_form_id" => "required|exists:q_a_forms,id"
        ]);
        $item_update = QATeam::find($request->q_a_team_id);
        $qa_form = QAForm::find($request->q_a_form_id);
        if ($item_update->company_id != Auth::user()->company_id || $qa_form->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "The QA team doesn't belong to your company!"], 401);
        }

        $item_update->update([
            "q_a_form_id" => $request->q_a_form_id
        ]);
        return response()->json("Successfully updated!", 200);
    }

    /**
     * It adds members to a quality assurance team
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a message.
     */
    public function add_members_to_qa_team(Request $request)
    {
        $request->validate([
            "qa_team_id" => "required|exists:q_a_teams,id",
            "users" => "array"
        ]);

        $qa_team = QATeam::find($request->qa_team_id);

        if ($qa_team->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "The QA Team doesn't belong to your company!"], 401);
        }
        try {
            DB::beginTransaction();
            foreach ($request->users as $key => $user) {
                $user_check = User::find($user);
                if ($user_check->company_id != Auth::user()->company_id) {
                    throw ValidationException::withMessages(["You cannot access user with id $user"]);
                }
                $check_existing_member = QATeamMember::where([
                    "member_id" => $user,
                    "q_a_team_id" => $request->qa_team_id
                ])->first();
                if ($check_existing_member) {
                    throw ValidationException::withMessages(["User with id $user is already a member of this team"]);
                } else {
                    QATeamMember::create([
                        "member_id" => $user,
                        "q_a_team_id" => $request->qa_team_id
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'QA Team members added successfully!'
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        };
    }

    /**
     * It removes a member from a QA team
     * 
     * @param Request request The request object
     * 
     * @return The response is a json object with a message and a status code.
     */
    public function remove_member_from_qa_team(Request $request)
    {
        $request->validate([
            "user_id" => "required|exists:users,id",
            "qa_team_id" => "required|exists:q_a_teams,id"
        ]);

        $qa_member = QATeamMember::where([
            "member_id" => $request->user_id,
            "q_a_team_id" => $request->qa_team_id
        ])->first();

        if (!$qa_member) {
            return response()->json(['success' => false, 'message' => 'User not assigned to this QA Team!'], 404);
        }

        $qa_team = QATeam::find($request->qa_team_id);
        if ($qa_team->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "The QA team doesn't belong to your company!"], 401);
        }

        $qa_member = QATeamMember::where([
            "member_id" => $request->user_id,
            "q_a_team_id" => $request->qa_team_id
        ])->delete();

        return response()->json(['success' => true, 'message' => 'Member removed from this QA Team!'], 200);
    }

    /**
     * It toggles the status of a quality assurance team member
     * 
     * @param Request request The request object
     * 
     * @return The response is a json object with a message and a status code.
     */
    public function toggle_qa_member_status(Request $request)
    {
        $request->validate([
            "member_id" => "required|exists:users,id",
            "qa_team_id" => 'required|exists:q_a_teams,id'
        ]);

        $qa_team = QATeam::find($request->qa_team_id);

        if ($qa_team->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "The QA Team doesn't belong to your company!"], 401);
        }

        $qa_team_member = QATeamMember::where([
            'member_id' => $request->member_id,
            'q_a_team_id' => $request->qa_team_id
        ])->first();

        if ($qa_team_member) {
            $new_status = ($qa_team_member->is_available == true) ? 0 : 1;

            $qa_team_member->update([
                "is_available" => $new_status
            ]);

            return response()->json(['success' => true, 'message' => 'QA Member status updated successfully!'], 200);
        }

        return response()->json(['success' => false, 'message' => 'User not assigned to this QA Team!'], 404);
    }

    /**
     * It returns all users from the database that have the same company_id as the currently logged in user
     * 
     * @return A collection of users.
     */
    public function get_qa_users()
    {
        return User::where("company_id", Auth::user()->company_id)->get();
    }

    /**
     * It adds a supervisor to a team
     * 
     * @param Request request The request object.
     */
    public function add_supervisor_to_qa_team(Request $request)
    {
        $request->validate([
            "team_id" => "required|exists:q_a_teams,id",
            "user_id" => "required|exists:users,id"
        ]);

        $team = QATeam::find($request->team_id);
        $user = User::find($request->user_id);
        if ($team->company_id != Auth::user()->company_id || $user->company_id != Auth::user()->company_id) {
            return response()->json(["You are not allowed to access, the user or team!"], 401);
        }
        $supervisorCheck = QATeamSupervisor::where([
            "team_id" => $request->team_id,
            "user_id" => $request->user_id
        ])->first();

        if (!$supervisorCheck) {
            QATeamSupervisor::create([
                "team_id" => $request->team_id,
                "user_id" => $request->user_id
            ]);
        }

        return response()->json(["successfully added"], 200);
    }

    /**
     * It removes a supervisor from a QA team
     * 
     * @param Request request The request object
     * 
     * @return The response is a JSON object with a success key and a message key.
     */
    public function remove_supervisor_from_qa_team(Request $request)
    {
        $request->validate([
            "user_id" => "required|exists:users,id",
            "qa_team_id" => "required|exists:q_a_teams,id"
        ]);

        $qa_team = QATeam::find($request->qa_team_id);
        if ($qa_team->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "The QA team doesn't belong to your company!"], 401);
        }

        $qa_member = QATeamSupervisor::where([
            "user_id" => $request->user_id,
            "team_id" => $request->qa_team_id
        ])->first();

        if (!$qa_member) {
            return response()->json(['success' => false, 'message' => 'User not assigned to this QA Team!'], 404);
        }

        $qa_member = QATeamSupervisor::where([
            "user_id" => $request->user_id,
            "team_id" => $request->qa_team_id
        ])->delete();

        return response()->json(['success' => true, 'message' => 'Supervisor removed from this QA Team!'], 200);
    }

    /**
     * It adds a queue to a QA team
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a success message.
     */
    public function add_queue_to_qa_team(Request $request)
    {
        $request->validate([
            "team_id" => "required|exists:q_a_teams,id",
            "queue_id" => "required",
            "queue_type" => "required|in:voice,chat"
        ]);

        if ($request->queue_type == "voice") {
            $request->validate([
                "queue_id" => "exists:queues,id"
            ]);
        } else {
            $request->validate([
                "queue_id" => "exists:chat_queues,id"
            ]);
        }

        $team = QATeam::find($request->team_id);
        if ($team->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "The QA team doesn't belong to your company!"], 401);
        }

        $QATeamCheck =
            QATeamQueue::where([
                "team_id" => $request->team_id,
                "queue_id" => $request->queue_id,
                "queue_type" => $request->queue_type
            ])->first();

        if ($QATeamCheck) {
            return response()->json(['message' => "The QA team already linked to this queue!"], 422);
        }

        QATeamQueue::create([
            "team_id" => $request->team_id,
            "queue_id" => $request->queue_id,
            "queue_type" => $request->queue_type
        ]);

        return response()->json([
            'success' => true,
            'message' => "Queue successfully added to QA Team!"
        ], 200);
    }

    /**
     * It removes a queue from a QA team
     * 
     * @param Request request The request object
     */
    public function remove_queue_from_qa_team(Request $request)
    {
        $request->validate([
            "team_id" => "required|exists:q_a_teams,id",
            "queue_id" => "required",
            "queue_type" => "required|in:voice,chat"
        ]);

        if ($request->queue_type == "voice") {
            $request->validate([
                "queue_id" => "exists:queues,id"
            ]);
        } else {
            $request->validate([
                "queue_id" => "exists:chat_queues,id"
            ]);
        }

        $team = QATeam::find($request->team_id);
        if ($team->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "The QA team doesn't belong to your company!"], 401);
        }

        $isSupervisor = QATeamSupervisor::where([
            "team_id" => $request->team_id,
            "user_id" => Auth::user()->id
        ])->first();

        if (!$isSupervisor) {
            return response()->json(['success' => false, 'message' => 'User not assigned to this QA Team!'], 401);
        }

        $qa_team = QATeam::find($request->team_id);
        if ($qa_team->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "The QA team doesn't belong to your company!"], 401);
        }

        $qa_team_queue = QATeamQueue::where([
            "team_id" => $request->team_id,
            "queue_id" => $request->queue_id,
            "queue_type" => $request->queue_type,
        ])->first();

        if ($qa_team_queue) {
            $qa_team_queue->delete();

            return response()->json([
                'success' => true,
                'message' => "Queue removed from team!"
            ], 200);
        }
        return response()->json([
            'success' => false,
            'message' => "Queue not linked to team!"
        ], 200);
    }
}