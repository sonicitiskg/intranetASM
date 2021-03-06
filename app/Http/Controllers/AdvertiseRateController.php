<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;

use Gate;
use App\Http\Requests;
use App\AdvertisePosition;
use App\AdvertiseRate;
use App\AdvertiseSize;
use App\Media;
use App\Paper;

class AdvertiseRateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(Gate::denies('Advertise Rates Management-Read')) {
            abort(403, 'Unauthorized action.');
        }
        return view('vendor.material.master.advertise_rate.list');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if(Gate::denies('Advertise Rates Management-Create')) {
            abort(403, 'Unauthorized action.');
        }
        $data = array();
        $data['advertiseposition'] = AdvertisePosition::where('active','1')->orderBy('advertise_position_name')->get();
        $data['advertisesize'] = AdvertiseSize::with('unit')->where('active','1')->orderBy('advertise_size_name')->get();
        $data['media'] = Media::where('active','1')->orderBy('media_name')->get();
        $data['paper'] = Paper::with('unit')->where('active','1')->orderBy('paper_name')->get();
        return view('vendor.material.master.advertise_rate.create', $data);
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
            'media_id' => 'required',
            'advertise_position_id' => 'required',
            'advertise_size_id' => 'required',
            'paper_id' => 'required',
            'advertise_rate_code' => 'required|max:15|unique:advertise_rates,advertise_rate_code',
            'advertise_rate_startdate' => 'required|date_format:"d/m/Y"',
            'advertise_rate_enddate' => 'required|date_format:"d/m/Y"',
            'advertise_rate_normal' => 'required|numeric|between:0,999999999999',
            'advertise_rate_discount' => 'numeric|between:0,999999999999',
        ]);

        $obj = new AdvertiseRate;

        $obj->media_id = $request->input('media_id');
        $obj->advertise_position_id = $request->input('advertise_position_id');
        $obj->advertise_size_id = $request->input('advertise_size_id');
        $obj->paper_id = $request->input('paper_id');
        $obj->advertise_rate_code = $request->input('advertise_rate_code');
        $obj->advertise_rate_startdate = Carbon::createFromFormat('d/m/Y', $request->input('advertise_rate_startdate'))->toDateString();
        $obj->advertise_rate_enddate = Carbon::createFromFormat('d/m/Y', $request->input('advertise_rate_enddate'))->toDateString();
        $obj->advertise_rate_normal = $request->input('advertise_rate_normal');
        $obj->advertise_rate_discount = $request->input('advertise_rate_discount');
        $obj->active = '1';
        $obj->created_by = $request->user()->user_id;

        $obj->save();

        $request->session()->flash('status', 'Data has been saved!');

        return redirect('master/advertiserate');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(Gate::denies('Advertise Rates Management-Read')) {
            abort(403, 'Unauthorized action.');
        }
        $data = array();
        $data['advertiserate'] = AdvertiseRate::with('paper', 'advertisesize', 'advertiseposition', 'advertisesize.unit')->where('advertise_rates.active','1')->find($id);
        $advertise_rate_startdate = Carbon::createFromFormat('Y-m-d', ($data['advertiserate']->advertise_rate_startdate==null) ? date('Y-m-d') : $data['advertiserate']->advertise_rate_startdate);
        $advertise_rate_enddate = Carbon::createFromFormat('Y-m-d', ($data['advertiserate']->advertise_rate_enddate==null) ? date('Y-m-d') : $data['advertiserate']->advertise_rate_enddate);
        $data['advertise_rate_startdate'] = $advertise_rate_startdate->format('d/m/Y');
        $data['advertise_rate_enddate'] = $advertise_rate_enddate->format('d/m/Y');
        return view('vendor.material.master.advertise_rate.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if(Gate::denies('Advertise Rates Management-Update')) {
            abort(403, 'Unauthorized action.');
        }
        $data = array();
        $data['advertiseposition'] = AdvertisePosition::where('active','1')->orderBy('advertise_position_name')->get();
        $data['advertisesize'] = AdvertiseSize::with('unit')->where('active','1')->orderBy('advertise_size_name')->get();
        $data['media'] = Media::where('active','1')->orderBy('media_name')->get();
        $data['advertiserate'] = AdvertiseRate::with('paper', 'advertisesize', 'advertiseposition')->where('active','1')->find($id);
        $data['paper'] = Paper::with('unit')->where('active','1')->orderBy('paper_name')->get();

        $advertise_rate_startdate = Carbon::createFromFormat('Y-m-d', ($data['advertiserate']->advertise_rate_startdate==null) ? date('Y-m-d') : $data['advertiserate']->advertise_rate_startdate);
        $advertise_rate_enddate = Carbon::createFromFormat('Y-m-d', ($data['advertiserate']->advertise_rate_enddate==null) ? date('Y-m-d') : $data['advertiserate']->advertise_rate_enddate);
        $data['advertise_rate_startdate'] = $advertise_rate_startdate->format('d/m/Y');
        $data['advertise_rate_enddate'] = $advertise_rate_enddate->format('d/m/Y');
        
        return view('vendor.material.master.advertise_rate.edit', $data);
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
            'media_id' => 'required',
            'advertise_position_id' => 'required',
            'advertise_size_id' => 'required',
            'paper_id' => 'required',
            'advertise_rate_code' => 'required|max:15|unique:advertise_rates,advertise_rate_code,' . $id . ',advertise_rate_id',
            'advertise_rate_startdate' => 'required|date_format:"d/m/Y"',
            'advertise_rate_enddate' => 'required|date_format:"d/m/Y"',
            'advertise_rate_normal' => 'required|numeric|between:0,999999999999',
            'advertise_rate_discount' => 'numeric|between:0,999999999999',
        ]);

        $obj = AdvertiseRate::find($id);

        $obj->media_id = $request->input('media_id');
        $obj->advertise_position_id = $request->input('advertise_position_id');
        $obj->advertise_size_id = $request->input('advertise_size_id');
        $obj->paper_id = $request->input('paper_id');
        $obj->advertise_rate_code = $request->input('advertise_rate_code');
        $obj->advertise_rate_startdate = Carbon::createFromFormat('d/m/Y', $request->input('advertise_rate_startdate'))->toDateString();
        $obj->advertise_rate_enddate = Carbon::createFromFormat('d/m/Y', $request->input('advertise_rate_enddate'))->toDateString();
        $obj->advertise_rate_normal = $request->input('advertise_rate_normal');
        $obj->advertise_rate_discount = $request->input('advertise_rate_discount');
        $obj->updated_by = $request->user()->user_id;

        $obj->save();

        $request->session()->flash('status', 'Data has been updated!');

        return redirect('master/advertiserate');
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
        $rowCount = $request->input('rowCount') or 10;
        $skip = ($current==1) ? 0 : (($current - 1) * $rowCount);
        $searchPhrase = $request->input('searchPhrase') or '';
        
        $sort_column = 'advertise_rate_id';
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
        $data['rows'] = AdvertiseRate::join('advertise_positions','advertise_positions.advertise_position_id','=','advertise_rates.advertise_position_id')
                            ->join('advertise_sizes','advertise_sizes.advertise_size_id','=','advertise_rates.advertise_size_id')
                            ->join('medias','medias.media_id','=','advertise_rates.media_id')
                            ->join('papers','papers.paper_id','=','advertise_rates.paper_id')
                            ->where('advertise_rates.active','1')
                            ->where(function($query) use($searchPhrase) {
                                $query->where('advertise_position_name','like','%' . $searchPhrase . '%')
                                        ->orWhere('advertise_size_name','like','%' . $searchPhrase . '%')
                                        ->orWhere('paper_name','like','%' . $searchPhrase . '%')
                                        ->orWhere('media_name','like','%' . $searchPhrase . '%')
                                        ->orWhere('advertise_rate_code','like','%' . $searchPhrase . '%')
                                        ->orWhere('advertise_rate_normal','like','%' . $searchPhrase . '%')
                                        ->orWhere('advertise_rate_discount','like','%' . $searchPhrase . '%');
                            })
                            ->skip($skip)->take($rowCount)
                            ->orderBy($sort_column, $sort_type)->get();
        $data['total'] = AdvertiseRate::join('advertise_positions','advertise_positions.advertise_position_id','=','advertise_rates.advertise_position_id')
                            ->join('advertise_sizes','advertise_sizes.advertise_size_id','=','advertise_rates.advertise_size_id')
                            ->join('medias','medias.media_id','=','advertise_rates.media_id')
                            ->join('papers','papers.paper_id','=','advertise_rates.paper_id')
                            ->where('advertise_rates.active','1')
                            ->where(function($query) use($searchPhrase) {
                                $query->where('advertise_position_name','like','%' . $searchPhrase . '%')
                                        ->orWhere('advertise_size_name','like','%' . $searchPhrase . '%')
                                        ->orWhere('paper_name','like','%' . $searchPhrase . '%')
                                        ->orWhere('media_name','like','%' . $searchPhrase . '%')
                                        ->orWhere('advertise_rate_code','like','%' . $searchPhrase . '%')
                                        ->orWhere('advertise_rate_normal','like','%' . $searchPhrase . '%')
                                        ->orWhere('advertise_rate_discount','like','%' . $searchPhrase . '%');
                            })->count();

        return response()->json($data);
    }


    public function apiDelete(Request $request)
    {
        if(Gate::denies('Advertise Rates Management-Delete')) {
            abort(403, 'Unauthorized action.');
        }

        $id = $request->input('advertise_rate_id');

        $obj = AdvertiseRate::find($id);

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
