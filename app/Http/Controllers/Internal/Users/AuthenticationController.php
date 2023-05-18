<?php

namespace App\Http\Controllers\Internal\Users;

use App\Http\Controllers\Controller;
use App\Mail\StaffRegistered;
use App\Models\Internal\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthenticationController extends Controller
{
    /**
     * It validates the request, checks if the user exists and if the password is correct, and if so,
     * returns the user and a token
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response containing the staff object and a token.
     */
    public function staffLogin(Request $request)
    {
        $request->validate(
            [
                'email' => 'required|exists:staff,email',
                'password' => 'required'
            ],
            ['email.exists' => 'Staff is not registered in the system!']
        );

        $staff = Staff::where('email', $request->email)->first();

        if (!$staff || !Hash::check($request->password, $staff->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        return response()->json([
            'staff' => $staff,
            'token' => $staff->createToken('Internal', ['role:staff'])->plainTextToken
        ]);
    }

    /**
     * It registers a new staff member
     * 
     * @param Request request The request object
     * 
     * @return A JSON response
     */
    public function registerStaff(Request $request)
    {
        $request->validate([
            'role' => 'required|in:BILLING,SALES,SUPPORT,ADMIN',
            'name' => 'required|max:255',
            'email' => 'unique:staff,email|required|email',
            'phone' => 'required|unique:staff,phone'
        ]);

        if (auth()->user()->role !== 'ADMIN') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action!'
            ], 401);
        }

        $password = Str::random(10);

        $staff = Staff::create([
            'role' => $request->role,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($password)
        ]);

        if ($staff) {
            Mail::to($staff->email)->send(new StaffRegistered($staff, $password));

            event(new Registered($staff));

            return response()->json([
                'success' => true,
                'message' => 'Staff registered successfully!',
                'data' => $staff
            ], 200);
        }
        return response()->json([
            'success' => false,
            'message' => 'Error registering staff'
        ], 500);
    }
}