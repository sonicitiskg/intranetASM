<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Gate;
use App\Http\Requests;
use App\AdvertiseRateType;

class AdvertiseRateTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(Gate::denies('Advertise Rate Types Management-Read')) {
            abort(403, 'Unauthorized action.');
        }

        return view('vendor.material.master.advertiseratetype.list');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if(Gate::denies('Advertise Rate Types Management-Create')) {
            abort(403, 'Unauthorized action.');
        }
           
        return view('vendor.material.master.advertiseratetype.create');
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
            'advertise_rate_type_name' => 'required|max:255',
        ]);

        $obj = new AdvertiseRateType;

        $obj->advertise_rate_type_name = $request->input('advertise_rate_type_name');
        $obj->advertise_rate_type_desc = $request->input('advertise_rate_type_desc');
        $obj->active = '1';
        $obj->created_by = $request->user()->user_id;

        $obj->save();

        $request->session()->flash('status', 'Data has been saved!');

        return redirect('master/advertiseratetype');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(Gate::denies('Advertise Rate Types Management-Read')) {
            abort(403, 'Unauthorized action.');
        }

        $data = array();
        $data['advertiseratetype'] = AdvertiseRateType::where('active','1')->find($id);
        return view('vendor.material.master.advertiseratetype.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if(Gate::denies('Advertise Rate Types Management-Update')) {
            abort(403, 'Unauthorized action.');
        }

        $data = array();
        $data['advertiseratetype'] = AdvertiseRateType::where('active','1')->find($id);
        return view('vendor.material.master.advertiseratetype.edit', $data);
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
            'advertise_rate_type_name' => 'required|max:100',
        ]);

        $obj = AdvertiseRateType::find($id);

        $obj->advertise_rate_type_name = $request->input('advertise_rate_type_name');
        $obj->advertise_rate_type_desc = $request->input('advertise_rate_type_desc');
        $obj->updated_by = $request->user()->user_id;

        $obj->save();

        $request->session()->flash('status', 'Data has been updated!');

        return redirect('master/advertiseratetype');
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
        
        $sort_column = 'advertise_rate_type_id';
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
        $data['rows'] = AdvertiseRateType::where('active','1')
                            ->where(function($query) use($searchPhrase) {
                                $query->orWhere('advertise_rate_type_name','like','%' . $searchPhrase . '%')
                                        ->orWhere('advertise_rate_type_desc','like','%' . $searchPhrase . '%');
                            })
                            ->skip($skip)->take($rowCount)
                            ->orderBy($sort_column, $sort_type)->get();
        $data['total'] = AdvertiseRateType::where('active','1')
                                ->where(function($query) use($searchPhrase) {
                                    $query->orWhere('advertise_rate_type_name','like','%' . $searchPhrase . '%')
                                        ->orWhere('advertise_rate_type_desc','like','%' . $searchPhrase . '%');
                                })->count();

        return response()->json($data);
    }


    public function apiDelete(Request $request)
    {
        if(Gate::denies('Advertise Rate Types Management-Delete')) {
            abort(403, 'Unauthorized action.');
        }

        $id = $request->input('advertise_rate_type_id');

        $obj = AdvertiseRateType::find($id);

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
