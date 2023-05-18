<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Models\HelpDeskTeamConfiguration;
use Illuminate\Http\Request;

class HelpDeskTeamConfigurationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'help_desk_team_id' => 'required|exists:help_desk_teams,id',
        ]);

        $configurations = HelpDeskTeamConfiguration::where('help_desk_team_id', $request->help_desk_team_id)->get();

        return response()->json($configurations, 200);
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
            'help_desk_team_id' => 'required|exists:help_desk_teams,id',
            'setting' => 'required|unique:help_desk_team_configurations,setting',
            'active' => 'required|boolean'
        ]);

        $configuration = HelpDeskTeamConfiguration::create([
            'help_desk_team_id' => $request->help_desk_team_id,
            'setting' => $request->setting,
            'active' => $request->active
        ]);

        if ($configuration) {
            return response()->json(['message' => 'Helpdesk team setting saved successfully!'], 200);
        }

        return response()->json(['message' => 'Error saving Helpdesk team setting'], 502);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\HelpDeskTeamConfiguration  $helpDeskTeamConfiguration
     * @return \Illuminate\Http\Response
     */
    public function show(HelpDeskTeamConfiguration $helpDeskTeamConfiguration)
    {
        return response()->json($helpDeskTeamConfiguration, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\HelpDeskTeamConfiguration  $helpDeskTeamConfiguration
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, HelpDeskTeamConfiguration $helpDeskTeamConfiguration)
    {
        $request->validate([
            'setting' => 'required|unique:help_desk_team_configurations,setting,' . $helpDeskTeamConfiguration->id,
            'active' => 'required|boolean'
        ]);
        
        $helpDeskTeamConfiguration->update([
            'setting' => $request->setting,
            'active' => $request->active
        ]);

        return response()->json(['message' => 'Helpdesk team setting saved successfully!'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\HelpDeskTeamConfiguration  $helpDeskTeamConfiguration
     * @return \Illuminate\Http\Response
     */
    public function destroy(HelpDeskTeamConfiguration $helpDeskTeamConfiguration)
    {
        $helpDeskTeamConfiguration->delete();

        return response()->json(['message' => 'Helpdesk team setting deleted successfully!'], 200);
    }
}