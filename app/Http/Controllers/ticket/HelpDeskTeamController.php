<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Http\Resources\HelpDeskTeamResource;
use App\Http\Resources\HelpDeskUsersResource;
use App\Models\HelpDeskTeam;
use App\Models\HelpDeskTeamUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HelpDeskTeamController extends Controller
{

    /**
     * This function returns all the help desk teams in the database
     * 
     * @return A collection of all the help desk teams for the company.
     */
    public function index()
    {
        $helpDeskTeams = HelpDeskTeam::with('users', 'teamLeader')->where('company_id', Auth::user()->company_id)->get();

        return response()->json($helpDeskTeams, 200);
    }

    /**
     * It returns all the users in a help desk team
     * 
     * @param Request request The request object
     * 
     * @return The users of the helpdesk team.
     */
    public function team_users(Request $request)
    {
        $request->validate([
            'helpdesk_id' => 'required|exists:help_desk_teams,id'
        ]);

        $helpDeskTeam = HelpDeskTeam::find($request->helpdesk_id);

        if ($helpDeskTeam->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "Helpdesk team does not belong to your company!"], 401);
        }

        return response()->json([
            'success' => true,
            'users' => HelpDeskUsersResource::collection($helpDeskTeam->users)
        ], 200);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:help_desk_teams,name',
            'description' => 'required'
        ]);

        $helpDeskTeam = HelpDeskTeam::create([
            'team_leader_id' => Auth::user()->id,
            'name' => $request->name,
            'description' => $request->description,
            'company_id' => Auth::user()->company_id
        ]);

        return new HelpDeskTeamResource($helpDeskTeam);
    }

    /**
     * Add user to the helpdesk team.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function viewUserTeams()
    {
        $teams = HelpDeskTeam::where('team_leader_id', Auth::user()->id)->get();

        if ($teams) {
            return response()->json($teams, 200);
        }

        return response()->json(['message' => 'You do not have any Helpdesk team'], 200);
    }

    /**
     * It adds a user to a helpdesk team
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a message and a status code.
     */
    public function addUserToTeam(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'help_desk_team_id' => 'required|exists:help_desk_teams,id',
        ], [
            'user_id.exists' => 'The selected user does not exist!',
            'help_desk_team_id.exists' => 'The Help desk team does not exist!',
            'user_id.required' => 'User is required!',
            'help_desk_team_id.required' => 'The Help desk team is required!'
        ]);

        $team = HelpDeskTeam::find($request->help_desk_team_id);

        if ($team->team_leader_id != Auth::user()->id) {
            return response()->json(['message' => "You don't own this Helpdesk team!"], 401);
        }

        $teamUser = HelpDeskTeamUsers::create([
            'user_id' => $request->user_id,
            'help_desk_team_id' => $request->help_desk_team_id
        ]);

        if ($teamUser) {
            return response()->json(['message' => 'User added to Helpdesk Team successfully!'], 200);
        }

        return response()->json(['message' => 'Error adding User to Helpdesk Team!'], 502);
    }

    /**
     * It removes a user from a helpdesk team
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a message and a status code.
     */
    public function removeUserFromTeam(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:help_desk_team_users,user_id',
            'help_desk_team_id' => 'required|exists:help_desk_team_users,help_desk_team_id',
        ], [
            'user_id.exists' => 'The selected user does not exist!',
            'help_desk_team_id.exists' => 'The Help desk team does not exist!',
            'user_id.required' => 'User is required!',
            'help_desk_team_id.required' => 'The Help desk team is required!'
        ]);

        $team = HelpDeskTeam::find($request->help_desk_team_id);

        if ($team->team_leader_id != Auth::user()->id) {
            return response()->json(['message' => "You don't own this Helpdesk team!"], 401);
        }

        $teamUser = HelpDeskTeamUsers::where([
            'user_id' => $request->user_id,
            'help_desk_team_id'  => $request->help_desk_team_id,
        ])->first();

        if ($teamUser) {
            if ($teamUser->delete()) {
                return response()->json(['message' => 'User removed from Helpdesk Team successfully!'], 200);
            }
            return response()->json(['message' => 'Error removing User from Helpdesk Team!'], 502);
        }
    }

    /**
     * It activates a helpdesk team
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a message and a status code.
     */
    public function activateTeam(Request $request)
    {
        $request->validate([
            'help_desk_team_id' => 'required|exists:help_desk_teams,id',
        ]);

        $team = HelpDeskTeam::find($request->help_desk_team_id);

        if ($team->team_leader_id != Auth::user()->id) {
            return response()->json(['message' => "You don't own this Helpdesk team!"], 401);
        }

        $team->active = true;

        $team->save();

        return response()->json(['message' => 'Helpdesk team activated successfully!'], 200);
    }

    /**
     * It deactivates a helpdesk team
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a message and a status code.
     */
    public function deactivateTeam(Request $request)
    {
        $request->validate([
            'help_desk_team_id' => 'required|exists:help_desk_teams,id',
        ]);

        $team = HelpDeskTeam::find($request->help_desk_team_id);

        if ($team->team_leader_id != Auth::user()->id) {
            return response()->json(['message' => "You don't own this Helpdesk team!"], 401);
        }

        $team->status = false;

        $team->save();

        return response()->json(['message' => 'Helpdesk team deactivated successfully!'], 200);
    }

    /**
     * It returns the Helpdesk team if the user is the team leader
     * 
     * @param HelpDeskTeam helpDeskTeam This is the model that we're using.
     * 
     * @return A HelpDeskTeamResource
     */
    public function show(HelpDeskTeam $helpDeskTeam)
    {

        if ($helpDeskTeam->team_leader_id != Auth::user()->id) {
            return response()->json(['message' => "You don't own this Helpdesk team!"], 401);
        }

        return new HelpDeskTeamResource($helpDeskTeam);
    }

    /**
     * It updates the help desk team
     * 
     * @param Request request The request object.
     * @param HelpDeskTeam helpDeskTeam This is the HelpDeskTeam model that we are updating.
     * 
     * @return A HelpDeskTeamResource
     */
    public function update(Request $request, HelpDeskTeam $helpDeskTeam)
    {
        $request->validate([
            'name' => 'required|unique:help_desk_teams,name',
            'description' => 'required'
        ], [
            'name.required' => 'Help desk team name is required!',
            'name.unique' => 'Help desk team name should be unique! ',
            'description.required' => 'Help desk team description is required!'
        ]);

        if ($helpDeskTeam->team_leader_id != Auth::user()->id) {
            return response()->json(['message' => "You don't own this Helpdesk team!"], 401);
        }

        $helpDeskTeam->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return new HelpDeskTeamResource($helpDeskTeam);
    }

    /**
     * It checks if the user is authorized to delete the help desk team, and if so, it returns a response
     * saying that help desk team deletion is disabled
     * 
     * @param HelpDeskTeam helpDeskTeam This is the model that we are using for this API.
     * 
     * @return The response is being returned in JSON format.
     */
    public function destroy(HelpDeskTeam $helpDeskTeam)
    {
        if ($helpDeskTeam->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized to perform this action!'], 401);
        }

        return response()->json(['message' => 'Help desk team deletion is disabled!'], 200);
    }
}