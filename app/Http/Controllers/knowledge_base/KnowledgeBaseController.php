<?php

namespace App\Http\Controllers\knowledge_base;

use App\Helpers\AccessChecker;
use App\Http\Controllers\Controller;
use App\Http\Resources\KnowldgeBaseResource;
use App\Http\Resources\KnowldgeBaseStageResource;
use App\Models\KeyWord;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeBaseKeyWord;
use App\Models\KnowledgeBaseKeyWordStage;
use App\Models\KnowledgeBaseStage;
use App\Notifications\KnowledgeBaseNotification;
use Auth;
use Illuminate\Http\Request;
use Notification;

class KnowledgeBaseController extends Controller
{
    /**
     * It creates a new key word for the company
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with the message "Created successfully"
     */
    public function create_key_word(Request $request)
    {
        $request->validate([
            "key_word" => "required"
        ]);
        $check_duplicate = KeyWord::where([
            "key_word" => $request->key_word,
            "company_id" => Auth::user()->company_id
        ])->first();

        if ($check_duplicate) {
            return response()->json(["You have already created the key word!"], 422);
        }
        KeyWord::create([
            "key_word" => $request->key_word,
            "company_id" => Auth::user()->company_id
        ]);
        return response()->json(["Created successfully"], 200);
    }

    /**
     * It returns all the keywords for the company that the user is logged in to
     * 
     * @return A collection of KeyWord objects.
     */
    public function get_key_words()
    {
        return KeyWord::where("company_id", Auth::user()->company_id)->get();
    }

    /**
     * This function creates a new knowledge base article
     * 
     * @param Request request The request object.
     */
    public function create_knowledge_base(Request $request)
    {
        $request->validate([
            "title" => "required",
            "detail" => "required",
            "key_words" => "array|exists:key_words,id"
        ]);
        $approve_access = AccessChecker::has_KB_approve_access($request->user()->id);
        $create_access = AccessChecker::has_KB_create_access($request->user()->id);
        if (!$approve_access && !$create_access) {
            return response()->json(["You don't have the right to create knowldge base"], 401);
        }

        $check_duplicate = KnowledgeBase::where(["title" => $request->title, "company_id" => Auth::user()->company_id])->first();
        if ($check_duplicate) {
            return response()->json(["You have another article with the same name!"], 422);
        }

        if ($approve_access) {
            $knowledge_base = KnowledgeBase::create([
                "title" => $request->title,
                "detail" => $request->detail,
                "company_id" => Auth::user()->company_id
            ]);
            foreach ($request->key_words as $key_word) {
                KnowledgeBaseKeyWord::create([
                    "knowledge_base_id" => $knowledge_base->id,
                    "key_word_id" => $key_word
                ]);
            }
            return response()->json(["Successfully added"], 200);
        } else {
            /* This is the code that is executed when the user does not have the right to approve the
            knowledge base. */
            $check_duplicate_stage = KnowledgeBaseStage::where(["title" => $request->title, "company_id" => Auth::user()->company_id])->first();
            if ($check_duplicate_stage) {
                return response()->json(["You have another article with the same name!"], 422);
            }
            $knowledge_base_stage = KnowledgeBaseStage::create([
                "title" => $request->title,
                "detail" => $request->detail,
                "company_id" => Auth::user()->company_id,
                "type" => "CREATE",
                "key_words" => $request->key_words
            ]);
            foreach ($request->key_words as $key_word) {
                KnowledgeBaseKeyWordStage::create([
                    "knowledge_base_stage_id" => $knowledge_base_stage->id,
                    "key_word_id" => $key_word
                ]);
            }

            /* This is a notification system. It is sending a notification to the users who have the
           right to approve the knowledge base. */
            $user_to_notify = AccessChecker::get_users_with_this_access("knowledge_base_approve");
            foreach ($user_to_notify as $key => $user) {
                Notification::send($user->users, new KnowledgeBaseNotification("new knowledge base created", 'New knowldge base has been created, created by ' . Auth::user()->name . '. Please review and approve.'));
            }
            return response()->json(["Successfully added, pending approval"], 200);
        }
    }

    /**
     * Updating a knowledge base
     * 
     * @param Request request This is the request object that is sent to the server.
     * 
     * @return a response.
     */
    public function update_knowledge_base(Request $request)
    {
        $request->validate([
            "knowledge_base_id" => "required|exists:knowledge_bases,id",
            "title" => "required",
            "detail" => "required",
            "key_words" => "array|exists:key_words,id"
        ]);

        $approve_access = AccessChecker::has_KB_approve_access($request->user()->id);
        $create_access = AccessChecker::has_KB_create_access($request->user()->id);
        if (!$approve_access && !$create_access) {
            return response()->json(["You don't have the right to create knowldge base"], 401);
        }
        $check_duplicate = KnowledgeBase::where(["title" => $request->title, "company_id" => Auth::user()->company_id])
            ->where("id", "<>", $request->knowledge_base_id)->first();
        if ($check_duplicate) {
            return response()->json(["You have another article with the same name!"], 422);
        }
        $item_to_edit = KnowledgeBase::find($request->knowledge_base_id);
        if ($item_to_edit->company_id != Auth::user()->company_id) {
            return response()->json(["You don't have access to the resource!"], 401);
        }
        if ($approve_access) {
            $item_to_edit->update([
                "title" => $request->title,
                "detail" => $request->detail,
            ]);
            KnowledgeBaseKeyWord::where("knowledge_base_id", $item_to_edit->id)->delete();
            foreach ($request->key_words as $key_word) {
                KnowledgeBaseKeyWord::create([
                    "knowledge_base_id" => $item_to_edit->id,
                    "key_word_id" => $key_word
                ]);
            }
            return response()->json(["Successfully updated!"], 200);
        } else {
            /* This is the code that is executed when the user does not have the right to approve the
            knowledge base. */
            $knowledge_base_stage = KnowledgeBaseStage::create([
                "title" => $request->title,
                "knowledge_base_id" => $request->knowledge_base_id,
                "detail" => $request->detail,
                "company_id" => Auth::user()->company_id,
                "type" => "UPDATE"
            ]);
            foreach ($request->key_words as $key_word) {
                KnowledgeBaseKeyWordStage::create([
                    "knowledge_base_stage_id" => $knowledge_base_stage->id,
                    "key_word_id" => $key_word
                ]);
            }
            /* This is a notification system. It is sending a notification to the users who have the
           right to approve the knowledge base. */
            $user_to_notify = AccessChecker::get_users_with_this_access("knowledge_base_approve");
            foreach ($user_to_notify as $key => $user) {
                Notification::send($user->users, new KnowledgeBaseNotification("knowledge base updated", 'A knowldge base with title `' . $request->title . '` has been updated, updated by ' . Auth::user()->name . '. Please review and approve.'));
            }
            return response()->json(["Successfully added, pending approval"], 200);
        }
    }

    /**
     * It returns a collection of knowledge bases for the company that the user is logged in to
     * 
     * @return A collection of knowledge bases
     */
    public function get_knowledge_base_list()
    {
        $knowledge_bases = KnowledgeBase::where("company_id", Auth::user()->company_id)->paginate();
        return KnowldgeBaseResource::collection($knowledge_bases);
    }


    /**
     * It returns a paginated collection of knowledge base stages that are pending approval
     * 
     * @return A collection of knowledge base stages
     */
    public function get_knowledge_base_stage_list()
    {
        $knowledge_bases = KnowledgeBaseStage::where([
            "company_id" => Auth::user()->company_id,
            "status" => "PENDING"
        ])->paginate();
        return KnowldgeBaseStageResource::collection($knowledge_bases);
    }

    /**
     * It gets a knowledge base by id
     * 
     * @param Request request The request object
     * 
     * @return A single knowledge base
     */
    public function get_knowledge_base(Request $request)
    {
        $request->validate([
            "knowledge_base_id" => "required|exists:knowledge_bases,id"
        ]);

        $knowledge_base = KnowledgeBase::find($request->knowledge_base_id);
        if ($knowledge_base->company_id != Auth::user()->company_id) {
            return response()->json(["The knowledge base doesn't belong to you!"], 200);
        }
        return new KnowldgeBaseResource($knowledge_base);
    }

    /**
     * It gets a knowledge base stage by id
     * 
     * @param Request request The request object
     * 
     * @return The knowledge base stage and the knowledge base it belongs to.
     */
    public function get_knowledge_base_stage(Request $request)
    {
        $request->validate([
            "knowledge_base_stage_id" => "required|exists:knowledge_base_stages,id"
        ]);
        $knowledge_baseStage = KnowledgeBaseStage::find($request->knowledge_base_stage_id);
        if ($knowledge_baseStage->company_id != Auth::user()->company_id) {
            return response()->json(["The knowledge base doesn't belong to you!"], 200);
        }
        return response()->json([
            "knowledge_base_stage" => new KnowldgeBaseStageResource($knowledge_baseStage),
            "knowledge_base" => ($knowledge_baseStage->knowledge_base_id != null) ? new KnowldgeBaseResource($knowledge_baseStage->knowledge_base) : null
        ], 200);
    }

    /**
     * It approves or declines a knowledge base change
     * 
     * @param Request request The request object.
     * 
     * @return The knowledge base is being returned.
     */
    public function approve_knowledge_base(Request $request)
    {
        $request->validate([
            "knowledge_base_stage_id" => "required|exists:knowledge_base_stages,id",
            "status" => "required|in:APPROVE,DECLINE"
        ]);

        $approve_access = AccessChecker::has_KB_approve_access($request->user()->id);
        if (!$approve_access) {
            return response()->json(["You don't have the right to approve knowldge base"], 401);
        }

        $knowledge_base_stage = KnowledgeBaseStage::find($request->knowledge_base_stage_id);

        if ($knowledge_base_stage->company_id != Auth::user()->company_id) {
            return response()->json(["The knowledge base doesn't belong to you!"], 200);
        }

        if ($knowledge_base_stage->status != "PENDING") {
            return response()->json(["The knowledge base has been revied by another user!"], 200);
        }

        if ($request->status == "DECLINE") {
            $knowledge_base_stage->update(["status" => "DECLINE"]);
            return response()->json(["knowledge base change discarded!"], 200);
        } else {
            if ($knowledge_base_stage->type == "CREATE") {
                $knowledge_base = KnowledgeBase::create([
                    "title" => $knowledge_base_stage->title,
                    "detail" => $knowledge_base_stage->detail,
                    "company_id" => $knowledge_base_stage->company_id
                ]);
                $key_words = $knowledge_base_stage->key_words->pluck("id");
                KnowledgeBaseKeyWord::where("knowledge_base_id", $knowledge_base->id)->delete();
                foreach ($knowledge_base_stage->knowledge_base_key_word as $key_word) {
                    KnowledgeBaseKeyWord::create([
                        "knowledge_base_id" => $knowledge_base->id,
                        "key_word_id" => $key_word->key_word_id
                    ]);
                }
            } else {
                $knowledge_base = KnowledgeBase::find($knowledge_base_stage->knowledge_base_id);
                $knowledge_base->update([
                    "title" => $knowledge_base_stage->title,
                    "detail" => $knowledge_base_stage->detail
                ]);
                $key_words = $knowledge_base_stage->key_words->pluck("id");
                $knowledge_base->key_words()->sync($key_words);
            }
            KnowledgeBaseKeyWord::where("knowledge_base_id", $knowledge_base->id)->delete();
            foreach ($knowledge_base_stage->knowledge_base_key_word as $key_word) {
                KnowledgeBaseKeyWord::create([
                    "knowledge_base_id" => $knowledge_base->id,
                    "key_word_id" => $key_word->key_word_id
                ]);
            }
            return response()->json(["knowledge base successfully approved!"], 200);
        }
    }
}
