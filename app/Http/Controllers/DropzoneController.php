<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Input;
use Validator;
use Response;
use File;

class DropzoneController extends Controller
{
    public function uploadFiles(Request $request) {
    	$input = Input::all();

    	$rules = array(
    		/*'file' => 'image|max:10000'*/
    		'file' => 'max:10000'
    	);

    	$validation = Validator::make($input, $rules);

    	if($validation->fails()) {
    		return Response::make($validation->errors->first(), 400);
    	}

    	$destinationPath = 'uploads/tmp/' . $request->user()->user_id;
    	$extension = Input::file('file')->getClientOriginalExtension();
    	//$fileName = rand(1111111, 9999999) . '.' . $extension;
    	$fileName = Input::file('file')->getClientOriginalName();

    	//dd($fileName);

    	$upload_success = Input::file('file')->move($destinationPath, $fileName);

    	/*dd($upload_success);*/

    	if($upload_success) {
    		return Response::json('success', 200);
    	}else{
    		return Response::json('error', 400);
    	}
    }

    public function removeFile(Request $request) {
    	$destinationPath = 'uploads/tmp/' . $request->user()->user_id;

    	/*dd(File::delete($destinationPath . '/' . $request->filename);*/

    	if(File::delete($destinationPath . '/' . $request->filename)) {
    		return Response::json('success', 200);
    	}else{
    		return Response::json('error', 400);	
    	}
    }
}
