@extends('vendor.material.layouts.app')

@section('content')
    <div class="card">
        <div class="card-header"><h2>Advertise Rate Type Management<small>View Advertise Rate Type</small></h2></div>
        <div class="card-body card-padding">
        	<form class="form-horizontal" role="form">
	            <div class="form-group">
	                <label for="advertise_rate_type_name" class="col-sm-2 control-label">Name</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
	                        <input type="text" class="form-control input-sm" name="advertise_rate_type_name" id="advertise_rate_type_name" placeholder="Advertise Rate Type Name" required="true" maxlength="100" value="{{ $advertiseratetype->advertise_rate_type_name }}" readonly="true">
	                    </div>
	                </div>
	            </div>
	            <div class="form-group">
	                <label for="advertise_rate_type_desc" class="col-sm-2 control-label">Description</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
	                        {!! $advertiseratetype->advertise_rate_type_desc !!}
	                    </div>
	                </div>
	            </div>
	            <div class="form-group">
	                <div class="col-sm-offset-2 col-sm-10">
	                    <a href="{{ url('master/advertiseratetype') }}" class="btn btn-danger btn-sm">Back</a>
	                </div>
	            </div>
	        </form>
        </div>
    </div>
@endsection