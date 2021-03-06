@extends('vendor.material.layouts.app')

@section('vendorcss')
<link href="{{ url('css/chosen.css') }}" rel="stylesheet">
<link href="{{ url('css/bootstrap-select.min.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="card">
        <div class="card-header"><h2>Action Plans<small>View Action Plan</small></h2></div>
        <div class="card-body card-padding">
        	<form class="form-horizontal" role="form">
        		{{ csrf_field() }}
	            <div class="form-group">
	                <label for="action_plan_title" class="col-sm-2 control-label">Title</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
	                        <input type="text" class="form-control input-sm" name="action_plan_title" id="action_plan_title" placeholder="Action Plan Title" readonly="true" maxlength="100" value="{{ $actionplan->action_plan_title }}">
	                    </div>
	                </div>
	            </div>
	            <div class="form-group">
	                <label for="action_plan_desc" class="col-sm-2 control-label">Theme Description</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
	                        {!! $actionplan->action_plan_desc !!}
	                    </div>
	                </div>
	            </div>
	            <div class="form-group">
	                <label for="action_plan_rubric_desc" class="col-sm-2 control-label">Rubric Description</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
	                        {!! $actionplan->action_plan_rubric_desc !!}
	                    </div>
	                </div>
	            </div>
	            <div class="form-group">
	                <label for="media_group_id" class="col-sm-2 control-label">Media Group</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
                            @foreach ($mediagroups as $row)
                            	@foreach ($actionplan->mediagroups as $mediagroup)
                            		@if($mediagroup->media_group_id==$row->media_group_id)
                            			<span class="badge">{{ $row->media_group_name }}</span>
                            		@endif
                            	@endforeach
							@endforeach
	                    </div>
	                </div>
	            </div>
	            <div class="form-group">
	                <label for="media_id" class="col-sm-2 control-label">Media</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
                            @foreach ($medias as $row)
                            	@foreach ($actionplan->medias as $media)
                            		@if($media->media_id==$row->media_id)
                            			<span class="badge">{{ $row->media_name }}</span>
                            		@endif
                            	@endforeach
							@endforeach
	                    </div>
	                </div>
	            </div>
	            @if($printmedia==true)
	            <div class="form-group">
	                <label for="media_edition_id" class="col-sm-2 control-label">Media Edition</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
                            @foreach ($mediaeditions as $row)
                            	@foreach ($actionplan->mediaeditions as $mediaedition)
                            		@if($mediaedition->media_edition_id==$row->media_edition_id)
                            			<span class="badge">{{ $row->media->media_name .' - '. $row->media_edition_no }}</span>
                            		@endif
                            	@endforeach
							@endforeach
	                    </div>
	                </div>
	            </div>
	            <div class="form-group">
	                <label for="action_plan_pages" class="col-sm-2 control-label">Total Pages</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
	                        <input type="text" class="form-control input-sm" name="action_plan_pages" id="action_plan_pages" placeholder="Total Pages" required="true" maxlength="20" value="{{ $actionplan->action_plan_pages }}" readonly="true">
	                    </div>
	                </div>
	            </div>
	            @endif
	            @if($digitalmedia==true)
	            <div class="form-group">
	                <label for="action_plan_startdate" class="col-sm-2 control-label">Start Date</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
	                        <input type="text" class="form-control input-sm input-mask" name="action_plan_startdate" id="action_plan_startdate" placeholder="e.g 17/08/1945" readonly="true" maxlength="10" value="{{ $startdate }}" autocomplete="off" data-mask="00/00/0000">
	                    </div>
	                </div>
	            </div>
	            <div class="form-group">
	                <label for="action_plan_views" class="col-sm-2 control-label">Total Views</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
	                        <input type="text" class="form-control input-sm" name="action_plan_views" id="action_plan_views" placeholder="Total Views" required="true" maxlength="20" value="{{ $actionplan->action_plan_views }}" readonly="true">
	                    </div>
	                </div>
	            </div>
	            @endif
	            <div class="form-group">
	                <label for="upload_file" class="col-sm-2 control-label">Uploaded File(s)</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
	                        <div class="row">
	                        	@foreach ($uploadedfiles as $uploadedfile)
	                        	<div class="col-sm-6 col-md-3">
	                        		<div class="thumbnail">
	                        			@if($uploadedfile->upload_file_type=='jpg' || $uploadedfile->upload_file_type=='png' || $uploadedfile->upload_file_type=='gif' || $uploadedfile->upload_file_type=='jpeg')
	                        			<img src="{{ url($uploadedfile->upload_file_path . '/' . $uploadedfile->upload_file_name) }}" alt="{{ $uploadedfile->upload_file_name }}">
	                        			@else
	                        			<img src="{{ url('img/filetypes/' . $uploadedfile->upload_file_type . '.png') }}" alt="">
	                        			@endif
	                        			<div class="caption">
	                        				<h4>{{ $uploadedfile->upload_file_name }}</h4>
	                        				<p>{{ $uploadedfile->upload_file_desc }}</p>
	                        				<div class="m-b-5">
	                        					<!-- <a class="btn btn-sm btn-primary waves-effect" href="{{ url($uploadedfile->upload_file_path . '/' . $uploadedfile->upload_file_name) }}" role="button">Download File</a> -->
	                        					@can('Action Plan-Download')
	                        					<a class="btn btn-sm btn-primary waves-effect" href="{{ url('download/file/' . $uploadedfile->upload_file_id) }}" role="button">Download File</a>
	                        					@endcan
	                        				</div>
	                        			</div>
	                        		</div>
	                        	</div>
	                        	@endforeach
	                        </div>
	                    </div>
	                </div>
	            </div>
	            <div class="form-group">
	                <label for="created_by" class="col-sm-2 control-label">Created By</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
	                        <input type="text" class="form-control input-sm" placeholder="Created By" readonly="true" maxlength="100" value="{{ $actionplan->created_by->user_firstname . ' ' . $actionplan->created_by->user_lastname }}">
	                    </div>
	                </div>
	            </div>
	            <!-- <div class="form-group">
	                <label for="created_at" class="col-sm-2 control-label">Created Time</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
	                        <input type="text" class="form-control input-sm" placeholder="Created Time" readonly="true" maxlength="100" value="{{ $actionplan->created_at }}">
	                    </div>
	                </div>
	            </div>
	            <div class="form-group">
	                <label for="updated_by" class="col-sm-2 control-label">Updated By</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
	                        <input type="text" class="form-control input-sm" placeholder="Updated By" readonly="true" maxlength="100" value="">
	                    </div>
	                </div>
	            </div>
	            <div class="form-group">
	                <label for="updated_time" class="col-sm-2 control-label">Updated Time</label>
	                <div class="col-sm-10">
	                    <div class="fg-line">
	                        <input type="text" class="form-control input-sm" placeholder="Updated Time" readonly="true" maxlength="100" value="{{ $actionplan->updated_at }}">
	                    </div>
	                </div>
	            </div> -->
	            <div class="form-group">
	                <label for="history" class="col-sm-2 control-label">History</label>
	                <div class="col-sm-10">
	                    <div class="timeline">
                        @foreach($actionplan->actionplanhistories as $key => $value)
                        	<div class="t-view" data-tv-type="text">
                                <div class="tv-header media">
                                    <a href="#" class="tvh-user pull-left">
                                        <img class="img-responsive" src="{{ url('img/avatar/' . $value->created_by->user_avatar) }}" alt="$value->created_by->user_avatar">
                                    </a>
                                    <div class="media-body p-t-5">
                                        <strong class="d-block">{{ $value->created_by->user_firstname . ' ' . $value->created_by->user_lastname }}</strong>
                                        <small class="c-gray">{{ $value->created_at }}</small>
                                    </div>
                                </div>
                                <div class="tv-body">
									<p>
										{!! $value->action_plan_history_text !!}
									</p>
									<div class="clearfix"></div>
									<ul class="tvb-stats">
										<li class="tvbs-likes">{{ $value->approvaltype->approval_type_name }}</li>
									</ul>
                                </div>
                            </div>
                        @endforeach
	                    </div>
	                </div>
	            </div>
	            <div class="form-group">
	                <div class="col-sm-offset-2 col-sm-10">
	                    <a href="{{ url('plan/actionplan') }}" class="btn btn-danger btn-sm">Back</a>
	                </div>
	            </div>
	        </form>
        </div>
    </div>
@endsection

@section('vendorjs')
<script src="{{ url('js/chosen.jquery.js') }}"></script>
<script src="{{ url('js/bootstrap-select.min.js') }}"></script>
@endsection

@section('customjs')

@endsection