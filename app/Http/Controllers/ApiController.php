<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CommonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Routine;
use App\Models\Subject;
use App\Models\ClassRoom;
use App\Models\Session;
use App\Models\DailyAttendances;

class ApiController extends Controller
{

	//student login function
	public function login(Request $request) {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // Check email
        $user = User::where('email', $fields['email'])->first();

        // Check password
        if(!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => 'Invalid credentials!'
            ], 401);
        } else if($user->role_id == 7) {
        	$token = $user->createToken('auth-token')->plainTextToken;

	        $response = [
	        	'message' => 'Login successful',
	            'user' => $user,
	            'token' => $token
	        ];

	        return response($response, 201);

        } else {

        	//user not authorized
            return response()->json([
                'message' => 'User not authorized!',
            ], 400);
        }
    }


    //student logout function
    public function logout(Request $request)
    {
    	auth()->user()->tokens()->delete;

    	return [
    		'message' => 'Logged out successfully.'
    	];
    }


    //student details
    public function userDetails(Request $request)
    {
    	$student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);

    	if($student_data) {
    		return response($student_data, 201);
    	} else {
    		return response()->json([
                'message' => 'User information not found!',
            ], 400);
    	}
    }


    //student class routine get
    public function routine(Request $request)
    {
    	$student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);
        $class_id = $student_data['class_id'];
        $class_name = (new CommonController)->getClassDetails($class_id)->name;
        $section_id = $student_data['section_id'];
        $section_name = (new CommonController)->getSectionDetails($section_id)->name;
        $school_id = $student_data['school_id'];
        $session_id = $student_data['running_session'];


        $routines = Routine::where(['class_id' => $class_id, 'section_id' => $section_id, 'session_id' => $session_id, 'school_id' => $school_id])->get();

        if($routines) {

        	foreach($routines as $key => $routine) {
        		$res[$key]['id'] = $routine->id;
        		$res[$key]['subject_id'] = $routine->subject_id;
        		$res[$key]['subject_name'] = (new CommonController)->getSubjectDetails($routine->subject_id)->name;
        		$res[$key]['starting_time'] = $routine->starting_hour.':'.$routine->starting_minute;
        		$res[$key]['ending_time'] = $routine->ending_hour.':'.$routine->ending_minute;
        		$res[$key]['day'] = $routine->day;
        		$res[$key]['teacher_id'] = $routine->teacher_id;
        		$res[$key]['teacher_name'] = (new CommonController)->idWiseUserName($routine->teacher_id);
        	}

        	$response = [
	        	'class_id' => $class_id,
	        	'class_name' => $class_name,
	        	'section_id' => $section_id,
	        	'section_name' => $section_name,
	        	'school_id' => $school_id,
	        	'session_id' => $session_id,
	            'routines' => $res
	        ];

	    	return response($response, 201);
        } else {
        	return response()->json([
                'message' => 'No routine found!',
            ], 400);
        }
    }


    //student attendance get
    public function attendanceReport(Request $request)
    {
    	$student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);
        $class_id = $student_data['class_id'];
        $class_name = (new CommonController)->getClassDetails($class_id)->name;
        $section_id = $student_data['section_id'];
        $section_name = (new CommonController)->getSectionDetails($section_id)->name;
        $school_id = $student_data['school_id'];
        $session_id = $student_data['running_session'];


        $attendance_of_student = DailyAttendances::where(['class_id' => $student_data['class_id'], 'section_id' => $student_data['section_id'], 'student_id' => auth('sanctum')->user()->id, 'school_id' => $school_id, 'session_id' => $session_id])->get();

        if($attendance_of_student) {

        	foreach($attendance_of_student as $key => $data) {
        		$res[$key]['id'] = $data->id;
        		$res[$key]['status'] = $data->status;
        		$res[$key]['timestamp'] = $data->timestamp;
        	}

        	$response = [
	        	'class_id' => $class_id,
	        	'class_name' => $class_name,
	        	'section_id' => $section_id,
	        	'section_name' => $section_name,
	        	'school_id' => $school_id,
	        	'session_id' => $session_id,
	            'attedances' => $res
	        ];

	    	return response($response, 201);
        } else {
        	return response()->json([
                'message' => 'Attendance report not found!',
            ], 400);
        }
    }
}