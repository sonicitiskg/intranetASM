<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Requests;
use App\Religion;

class ReligionController extends Controller
{
    protected $searchPhrase = '';
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return view('vendor.material.master.religion.list');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('vendor.material.master.religion.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $this->validate($request, [
            'religion_name' => 'required|max:100'
        ]);

        $obj = new Religion;

        $obj->religion_name = $request->input('religion_name');
        $obj->active = '1';
        $obj->created_by = $request->user()->user_id;

        $obj->save();

        $request->session()->flash('status', 'Data has been saved!');

        return redirect('master/religion');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $data = array();
        $data['religion'] = Religion::where('active','1')->find($id);
        return view('vendor.material.master.religion.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $data = array();
        $data['religion'] = Religion::where('active','1')->find($id);
        return view('vendor.material.master.religion.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $this->validate($request, [
            'religion_name' => 'required|max:100',
        ]);

        $obj = Religion::find($id);

        $obj->religion_name = $request->input('religion_name');
        $obj->updated_by = $request->user()->user_id;

        $obj->save();

        $request->session()->flash('status', 'Data has been updated!');

        return redirect('master/religion');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    public function apiList(Request $request)
    {
        $current = $request->input('current') or 1;
        $rowCount = $request->input('rowCount') or 5;
        $skip = ($current==1) ? 0 : (($current - 1) * $rowCount);
        $this->searchPhrase = $request->input('searchPhrase') or '';
        
        $sort_column = 'religion_id';
        $sort_type = 'asc';

        if(is_array($request->input('sort'))) {
            foreach($request->input('sort') as $key => $value)
            {
                $sort_column = $key;
                $sort_type = $value;
            }
        }

        $data = array();
        $data['current'] = intval($current);
        $data['rowCount'] = $rowCount;
        $data['searchPhrase'] = $this->searchPhrase;
        $data['rows'] = Religion::where('active','1')
                            ->where(function($query) {
                                $query->where('religion_name','like','%' . $this->searchPhrase . '%');
                            })
                            ->skip($skip)->take($rowCount)
                            ->orderBy($sort_column, $sort_type)->get();
        $data['total'] = Religion::where('active','1')
                                ->where(function($query) {
                                    $query->where('religion_name','like','%' . $this->searchPhrase . '%');
                                })->count();

        return response()->json($data);
    }


    public function apiEdit(Request $request)
    {
        $id = $request->input('religion_id');

        $obj = Religion::find($id);

        $obj->active = '0';
        $obj->updated_by = $request->user()->user_id;

        if($obj->save())
        {
            return response()->json(100); //success
        }else{
            return response()->json(200); //failed
        }
    }
}