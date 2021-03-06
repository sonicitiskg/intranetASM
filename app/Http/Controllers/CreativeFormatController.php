<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Gate;
use App\Http\Requests;
use App\CreativeFormat;

class CreativeFormatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(Gate::denies('Creative Formats Management-Read')) {
            abort(403, 'Unauthorized action.');
        }
        return view('vendor.material.master.creativeformat.list');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if(Gate::denies('Creative Formats Management-Create')) {
            abort(403, 'Unauthorized action.');
        }
        return view('vendor.material.master.creativeformat.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'creative_format_name' => 'required|max:100',
        ]);

        $obj = new creativeformat;

        $obj->creative_format_name = $request->input('creative_format_name');
        $obj->creative_format_desc = $request->input('creative_format_desc');
        $obj->active = '1';
        $obj->created_by = $request->user()->user_id;

        $obj->save();

        $request->session()->flash('status', 'Data has been saved!');

        return redirect('master/creativeformat');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(Gate::denies('Creative Formats Management-Read')) {
            abort(403, 'Unauthorized action.');
        }
        $data = array();
        $data['creativeformat'] = CreativeFormat::where('active','1')->find($id);
        return view('vendor.material.master.creativeformat.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if(Gate::denies('Creative Formats Management-Update')) {
            abort(403, 'Unauthorized action.');
        }

        $data = array();
        $data['creativeformat'] = CreativeFormat::where('active','1')->find($id);
        return view('vendor.material.master.creativeformat.edit', $data);
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
        $this->validate($request, [
            'creative_format_name' => 'required|max:100',
        ]);

        $obj = CreativeFormat::find($id);

        $obj->creative_format_name = $request->input('creative_format_name');
        $obj->creative_format_desc = $request->input('creative_format_desc');
        $obj->updated_by = $request->user()->user_id;

        $obj->save();

        $request->session()->flash('status', 'Data has been updated!');

        return redirect('master/creativeformat');
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
        $searchPhrase = $request->input('searchPhrase') or '';
        
        $sort_column = 'creative_format_id';
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
        $data['searchPhrase'] = $searchPhrase;
        $data['rows'] = CreativeFormat::where('active','1')
                            ->where(function($query) use($searchPhrase) {
                                $query->orWhere('creative_format_name','like','%' . $searchPhrase . '%')
                                        ->orWhere('creative_format_desc','like','%' . $searchPhrase . '%');
                            })
                            ->skip($skip)->take($rowCount)
                            ->orderBy($sort_column, $sort_type)->get();
        $data['total'] = CreativeFormat::where('active','1')
                                ->where(function($query) use($searchPhrase) {
                                    $query->orWhere('creative_format_name','like','%' . $searchPhrase . '%')
                                        ->orWhere('creative_format_desc','like','%' . $searchPhrase . '%');
                                })->count();

        return response()->json($data);
    }


    public function apiDelete(Request $request)
    {
        if(Gate::denies('Creative Formats Management-Delete')) {
            abort(403, 'Unauthorized action.');
        }

        $id = $request->input('creative_format_id');

        $obj = CreativeFormat::find($id);

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
