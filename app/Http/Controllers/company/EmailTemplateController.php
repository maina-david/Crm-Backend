<?php

namespace App\Http\Controllers\company;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use DOMDocument;
use HTMLPurifier;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmailTemplateController extends Controller
{

    /**
     * It returns all the email templates in the database.
     * 
     * @return A JSON response with all the email templates.
     */
    public function index()
    {
        return response()->json(EmailTemplate::get(), 200);
    }

    /**
     * It validates the request, checks if the name is unique, validates the HTML, checks if the required
     * placeholders are present, and then saves the email template
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a success message.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:10',
            'type' => 'required|in:CONVERSATION',
            'body' => 'required'
        ]);

        $namecheck = EmailTemplate::where('name', $request->name)->first();

        if ($namecheck) {
            throw ValidationException::withMessages(['name' => 'Email template with same name exists!']);
        }

        $content = $request->body;

        // Validate the body is a valid HTML string
        $purifier = new HTMLPurifier();
        $validHtml = $purifier->purify($content);
        if ($validHtml != $content) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid HTML in body',
                'example' => '<html>
                <body>
                    Hello {{ name }},
                    <br><br>
                    {{ message }}
                    <br><br>
                    Best regards,
                    <br><br>
                    {{ valediction }}
                </body>
            </html>'
            ], 400);
        }

        $pattern = '/\{\{(.*?)\}\}/';
        preg_match_all($pattern, $content, $matches);

        $placeholders = $matches[1];
        $requiredPlaceholders = ['name', 'message', 'valediction'];
        $missingPlaceholders = array_diff($requiredPlaceholders, $placeholders);

        if (!empty($missingPlaceholders)) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required placeholders: ' . implode(', ', $missingPlaceholders),
                'example' => '<html>
                <body>
                    Hello {{ name }},
                    <br><br>
                    {{ message }}
                    <br><br>
                    Best regards,
                    <br><br>
                    {{ valediction }}
                </body>
            </html>'
            ], 400);
        }

        $emailtemplate = EmailTemplate::create([
            'type' => $request->type,
            'name' => $request->name,
            'body' => $request->body
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email template saved successfully!'
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EmailTemplate  $emailTemplate
     * @return \Illuminate\Http\Response
     */
    public function show(EmailTemplate $emailTemplate)
    {
        return response()->json($emailTemplate, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EmailTemplate  $emailTemplate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $request->validate([
            'type' => 'required|in:CONVERSATION',
            'name' => 'required|max:10',
            'body' => 'required'
        ]);

        $namecheck = EmailTemplate::where('name', $request->name)
            ->where('id', '!=', $emailTemplate->id)->first();

        if ($namecheck) {
            throw ValidationException::withMessages(['name' => 'Email template with same name exists!']);
        }

        $content = $request->body;

        $pattern = '/\{\{(.*?)\}\}/';
        preg_match_all($pattern, $content, $matches);

        $placeholders = $matches[1];
        $requiredPlaceholders = ['name', 'message'];
        $missingPlaceholders = array_diff($requiredPlaceholders, $placeholders);

        if (!empty($missingPlaceholders)) {
            throw ValidationException::withMessages(['body' => 'Missing required placeholders: ' . implode(', ', $missingPlaceholders)]);
        }

        $emailTemplate->update([
            'type' => $request->type,
            'name' => $request->name,
            'body' => $request->body
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email template updated successfully!'
        ], 200);
    }

    /**
     * It activates the email template
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a success message.
     */
    public function activateTemplate(Request $request)
    {
        $request->validate([
            'email_template_id' => 'required|exists:email_templates,id',
        ]);

        $template = EmailTemplate::find($request->email_template_id);

        $checkactive = EmailTemplate::where([
            'active' => true,
            'type' => $template->type
        ])->count();

        if ($checkactive > 0) {
            throw ValidationException::withMessages(['email_template' => 'There exists an active template of same type!']);
        }

        $template->active = true;

        $template->save();

        return response()->json([
            'success' => true,
            'message' => 'Email template activated successfully!'
        ], 200);
    }

    /**
     * It deactivates an email template
     * 
     * @param Request request The request object
     */
    public function deactivateTemplate(Request $request)
    {
        $request->validate([
            'email_template_id' => 'required|exists:email_templates,id',
        ]);

        $template = EmailTemplate::find($request->email_template_id);

        if ($template) {
            $template->active = false;

            $template->save();

            return response()->json([
                'success' => true,
                'message' => 'Email template deactivated successfully!'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error deactivating Email template!'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EmailTemplate  $emailTemplate
     * @return \Illuminate\Http\Response
     */
    public function destroy(EmailTemplate $emailTemplate)
    {
        if ($emailTemplate->delete()) {
            return response()->json([
                'success' => true,
                'message' => 'Email template deleted successfully!'
            ], 200);
        }
    }
}