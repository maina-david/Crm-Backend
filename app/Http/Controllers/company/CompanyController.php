<?php

namespace App\Http\Controllers\company;

use App\Http\Controllers\Controller;
use App\Models\AccessProfile;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyContact;
use App\Models\CompanyContactType;
use App\Models\Country;
use App\Models\LanguageList;
use App\Models\User;
use App\Models\UserAccessProfile;
use App\Services\CompanyService;
use Illuminate\Http\Request;

class CompanyController extends Controller
{

    public function get_country_code()
    {
        return Country::all("iso", "name");
    }

    public function get_languages()
    {
        return LanguageList::all("lang_code", "lang_name");
    }

    /**
     * create_compnay_one- will create a company with address and contact information
     * @request: an array of request with company, company address and contact data
     * Return: 200- for success
     * Return: 422- for error
     */
    public function create_compnay_once(Request $request)
    {
        $field_data_company = $request->validate([
            "company_name" => "required|string",
            "prefered_language" => "required|string|exists:language_lists,lang_code",
            "logo" => "required|url"
        ]);

        $request_address = new Request($request->address);
        $field_data_address = $request_address->validate([
            "country_code" => "required|string|exists:countries,iso",
            "phone" => "required",
            "email" => "required|email",
            "city" => "required|string",
            "office_number" => "required|string"
        ]);
        $request_contact = $request->contacts;
        // return $request_contact;
        foreach ($request_contact as $key => $contacts) {
            $conatc_request = new Request($request_contact[$key]);
            $field_data_contact = $conatc_request->validate([
                "company_contact_type_id" => "required|string|exists:company_contact_types,name",
                "phone_number" => "required",
                "email" => "required|email",
                "name" => "required|string",
            ]);
        }

        $company_service = new CompanyService();

        $company = $company_service->create_company($field_data_company["company_name"], $request->user()->id, $field_data_company["prefered_language"], $field_data_company["logo"]);
        $company_id = $company->id;
        $company_address_saved = $company_service->add_company_address($request_address, $company_id);

        foreach ($request->contacts as $key => $contacts) {
            $conatc_request = new Request($request->contacts[$key]);
            $company_contact_saved = $company_service->add_company_contact($conatc_request, $company_id);
        }

        $user = User::find($request->user()->id);
        $user_access_profile = UserAccessProfile::where('user_id', $request->user()->id)->first();
        $access_right = null;
        if ($user_access_profile)
            $access_right = AccessProfile::where('role_profile_id', $user_access_profile->access_profile_id)->get();
        $user->access_rights = $access_right;
        return response()->json([
            'message' => 'successfully created',
            'company' => $company,
            'user' => $user
        ], 200);
    }

    public function create_company(Request $request)
    {
        $field_data = $request->validate([
            "company_name" => "required|string",
            "prefered_language" => "required|string|exists:language_lists,lang_code",
            "logo" => "required|url"
        ]);

        $company_service = new CompanyService();
        $company_id = null;
        $company_address = null;
        $company = $company_service->create_company($field_data["company_name"], $request->user()->id, $field_data["prefered_language"], $field_data["logo"]);

        return response()->json([
            'message' => 'successfully created',
            'company' => $company
        ], 200);
    }

    public function edit_company(Request $request)
    {
        $field_data = $request->validate([
            "name" => "required|string",
            "prefered_language" => "required|string|exists:language_lists,lang_code"
        ]);

        $company_service = new CompanyService();
        $company = $company_service->edit_company($request);
        return response()->json([
            'message' => 'successfully updated',
            'company' => $company
        ], 200);
    }

    public function add_company_address(Request $request)
    {
        $field_data = $request->validate([
            "country_code" => "required|string|exists:countries,iso",
            "phone" => "required",
            "email" => "required|email",
            "city" => "required|string",
            "office_number" => "required|string"
        ]);
        $company_service = new CompanyService();
        $company = $company_service->add_company_address($request);

        return response()->json([
            'message' => 'successfully added',
            'company' => $company
        ], 200);
    }

    public function edit_company_address(Request $request)
    {
        $field_data = $request->validate([
            "country_code" => "required|string|exists:countries,iso",
            "phone" => "required",
            "email" => "required|email",
            "city" => "required|string",
            "office_number" => "required|string"
        ]);

        $company_service = new CompanyService();
        $company_address = $company_service->edit_company_address($request);

        return response()->json([
            'message' => 'successfully added',
            'company_address' => $company_address
        ], 200);
    }

    public function get_company_contact_type()
    {
        return CompanyContactType::get("name");
    }

    public function add_company_contact(Request $request)
    {

        $field_data = $request->validate([
            "company_contact_type_id" => "required|string|exists:company_contact_types,name",
            "phone_number" => "required",
            "email" => "required|email",
            "name" => "required|string",
        ]);

        $company_service = new CompanyService();
        $company = $company_service->add_company_contact($request);
        return response()->json([
            'message' => 'successfully updated',
            'company' => $company
        ], 200);
    }

    public function edit_company_contact(Request $request)
    {
        $field_data = $request->validate([
            "company_contact_type_id" => "required|string|exists:company_contact_types,name",
            "contact_id" => 'required|exists:company_contacts,id',
            "phone_number" => "required",
            "email" => "required|email",
            "name" => "required|string",
        ]);

        $company_service = new CompanyService();
        $company = $company_service->edit_company_contact($request);
        return response()->json([
            'message' => 'successfully updated',
            'company' => $company
        ], 200);
    }

    public function remove_company_contact(Request $request)
    {
        $field_data = $request->validate([
            "contact_id" => 'required|exists:company_contacts,id',
        ]);
        $company_service = new CompanyService();
        $company = $company_service->remove_company_contact($request);
        return response()->json([
            'message' => 'successfully removed',
        ], 200);
    }

    public function get_company_information()
    {
        $request = Request();
        $company_id = $request->user()->company_id;
        $company = Company::with('company_address', 'company_contacts')->find($company_id);
        return $company;
    }
}
