<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;

use File;
use Gate;
use App\Http\Requests;
use App\Project;
use App\ProjectTask;
use App\ProjectTaskHistory;
use App\ProjectTaskType;
use App\UploadFile;
use App\User;

use App\Ibrol\Libraries\FlowLibrary;
use App\Ibrol\Libraries\NotificationLibrary;
use App\Ibrol\Libraries\UserLibrary;

class ProjectTaskController extends Controller
{
    private $flows;
    private $flow_group_id;
    private $uri = '/grid/projecttask';
    private $notif;

    public function __construct() {
        $flow = new FlowLibrary;
        $this->flows = $flow->getCurrentFlows($this->uri);
        $this->flow_group_id = $this->flows[0]->flow_group_id;

        $this->notif = new NotificationLibrary;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(Gate::denies('Project Task-Read')) {
            abort(403, 'Unauthorized action.');
        }

        $data = array();

        return view('vendor.material.grid.projecttask.list', $data);
    }

    public function create(Request $request)
    {
		if(Gate::denies('Project Task-Create')) {
            abort(403, 'Unauthorized action.');
        }

        $data = array();

        $data['project_task_types'] = ProjectTaskType::with('user')->where('active', '1')->orderBy('project_task_type_name')->get();

     	return view('vendor.material.grid.projecttask.create', $data);   
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
        	'project_task_type_id' => 'required',
            'project_task_name' => 'required|max:100',
            'project_task_deadline' => 'required|date_format:"d/m/Y"',
            'project_task_desc' => 'required',
            'project_id' => 'required'
        ]);

        $projecttasktype = ProjectTaskType::find($request->input('project_task_type_id'));

        $flow = new FlowLibrary;
        $nextFlow = $flow->getNextFlow($this->flow_group_id, 1, $request->user()->user_id, '', '', $projecttasktype->user_id);

        $obj = new ProjectTask;
        $obj->project_task_type_id = $request->input('project_task_type_id');
        $obj->project_task_name = $request->input('project_task_name');
        $obj->project_task_deadline = Carbon::createFromFormat('d/m/Y', $request->input('project_task_deadline'))->toDateString();
        $obj->project_task_desc = $request->input('project_task_desc');
        $obj->project_id = $request->input('project_id');
        $obj->flow_no = $nextFlow['flow_no'];
        $obj->current_user = $nextFlow['current_user'];
        $obj->revision_no = 0;
        $obj->active = '1';
        $obj->created_by = $request->user()->user_id;

        $obj->save();

        //file saving
        $fileArray = array();

        $tmpPath = 'uploads/tmp/' . $request->user()->user_id;
        $files = File::files($tmpPath);
        foreach($files as $key => $value) {
            $oldfile = pathinfo($value);
            $newfile = 'uploads/files/' . $oldfile['basename'];
            if(File::exists($newfile)) {
                $rand = rand(1, 100);
                $newfile = 'uploads/files/' . $oldfile['filename'] . $rand . '.' . $oldfile['extension'];
            }

            if(File::move($value, $newfile)) {
                $file = pathinfo($newfile);
                $filesize = File::size($newfile);

                $upl = new UploadFile;
                $upl->upload_file_type = $file['extension'];
                $upl->upload_file_name = $file['basename'];
                $upl->upload_file_path = $file['dirname'];
                $upl->upload_file_size = $filesize;
                $upl->upload_file_revision = 0;
                $upl->upload_file_desc = '';
                $upl->active = '1';
                $upl->created_by = $request->user()->user_id;

                $upl->save();

                array_push($fileArray, $upl->upload_file_id);
                $fileArray[$upl->upload_file_id] = [ 'revision_no' => 0 ];
            }
        }

        if(!empty($fileArray)) {
            ProjectTask::find($obj->project_task_id)->uploadfiles()->sync($fileArray);    
        }

        $his = new ProjectTaskHistory;
        $his->project_task_id = $obj->project_task_id;
        $his->approval_type_id = 1;
        $his->project_task_history_text = $request->input('project_task_desc');
        $his->active = '1';
        $his->created_by = $request->user()->user_id;

        $his->save();

        $this->notif->generate($request->user()->user_id, $nextFlow['current_user'], 'projecttaskapproval', 'Please check Project Task "' . $obj->project_task_name . '"', $obj->project_task_id);

        $request->session()->flash('status', 'Data has been saved!');

        return redirect('grid/projecttask');
    }

    public function show(Request $request, $id)
    {
    	if(Gate::denies('Project Task-Read')) {
            abort(403, 'Unauthorized action.');
        }

        $data = array();

        $data['projecttask'] = ProjectTask::with(
											'project', 
											'projecttasktype',
											'uploadfiles',
											'projecttaskhistories',
											'projecttaskhistories.approvaltype'
											)->find($id);
        $data['deadline'] = Carbon::createFromFormat('Y-m-d', ($data['projecttask']->project_task_deadline==null) ? date('Y-m-d') : $data['projecttask']->project_task_deadline)->format('d/m/Y');

        return view('vendor.material.grid.projecttask.show', $data);
    }

    public function edit(Request $request, $id)
    {
        if(Gate::denies('Project Task-Update')) {
            abort(403, 'Unauthorized action.');
        }

        $data['projecttask'] = ProjectTask::with(
                                            'project', 
                                            'projecttasktype',
                                            'uploadfiles',
                                            'projecttaskhistories',
                                            'projecttaskhistories.approvaltype'
                                            )->find($id);
        $data['project_task_types'] = ProjectTaskType::with('user')->where('active', '1')->orderBy('project_task_type_name')->get();
        $data['deadline'] = Carbon::createFromFormat('Y-m-d', ($data['projecttask']->project_task_deadline==null) ? date('Y-m-d') : $data['projecttask']->project_task_deadline)->format('d/m/Y');

        return view('vendor.material.grid.projecttask.edit', $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'project_task_type_id' => 'required',
            'project_task_name' => 'required|max:100',
            'project_task_deadline' => 'required|date_format:"d/m/Y"',
            'project_task_desc' => 'required',
            'project_id' => 'required'
        ]);

        $projecttasktype = ProjectTaskType::find($request->input('project_task_type_id'));

        $flow = new FlowLibrary;
        $nextFlow = $flow->getNextFlow($this->flow_group_id, 1, $request->user()->user_id, '', '', $projecttasktype->user_id);

        $obj = ProjectTask::find($id);
        $obj->project_task_type_id = $request->input('project_task_type_id');
        $obj->project_task_name = $request->input('project_task_name');
        $obj->project_task_deadline = Carbon::createFromFormat('d/m/Y', $request->input('project_task_deadline'))->toDateString();
        $obj->project_task_desc = $request->input('project_task_desc');
        $obj->project_id = $request->input('project_id');
        $obj->flow_no = $nextFlow['flow_no'];
        $obj->current_user = $nextFlow['current_user'];
        $obj->updated_by = $request->user()->user_id;

        $obj->save();


        //file saving
        $fileArray = array();

        $tmpPath = 'uploads/tmp/' . $request->user()->user_id;
        $files = File::files($tmpPath);
        foreach($files as $key => $value) {
            $oldfile = pathinfo($value);
            $newfile = 'uploads/files/' . $oldfile['basename'];
            if(File::exists($newfile)) {
                $rand = rand(1, 100);
                $newfile = 'uploads/files/' . $oldfile['filename'] . $rand . '.' . $oldfile['extension'];
            }

            if(File::move($value, $newfile)) {
                $file = pathinfo($newfile);
                $filesize = File::size($newfile);

                $upl = new UploadFile;
                $upl->upload_file_type = $file['extension'];
                $upl->upload_file_name = $file['basename'];
                $upl->upload_file_path = $file['dirname'];
                $upl->upload_file_size = $filesize;
                $upl->upload_file_revision = $obj->revision_no;
                $upl->upload_file_desc = '';
                $upl->active = '1';
                $upl->created_by = $request->user()->user_id;

                $upl->save();

                array_push($fileArray, $upl->upload_file_id);
                $fileArray[$upl->upload_file_id] = [ 'revision_no' => $obj->revision_no ];
            }
        }

        if(!empty($fileArray)) {
            ProjectTask::find($obj->project_task_id)->uploadfiles()->syncWithoutDetaching($fileArray);    
        }

        $his = new ProjectTaskHistory;
        $his->project_task_id = $obj->project_task_id;
        $his->approval_type_id = 1;
        $his->project_task_history_text = $request->input('project_task_desc');
        $his->active = '1';
        $his->created_by = $request->user()->user_id;

        $his->save();

        $this->notif->remove($request->user()->user_id, 'projecttaskreject', $obj->project_task_id);
        $this->notif->generate($request->user()->user_id, $nextFlow['current_user'], 'projecttaskapproval', 'Please check Project Task "' . $obj->project_task_name . '"', $obj->project_task_id);

        $request->session()->flash('status', 'Data has been saved!');

        return redirect('grid/projecttask');
    }

    public function apiList($listtype, Request $request)
    {
        $u = new UserLibrary;
        $subordinate = $u->getSubOrdinateArrayID($request->user()->user_id);

        $current = $request->input('current') or 1;
        $rowCount = $request->input('rowCount') or 10;
        $skip = ($current==1) ? 0 : (($current - 1) * $rowCount);
        $searchPhrase = $request->input('searchPhrase') or '';
        
        $sort_column = 'project_task_id';
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

        if($listtype == 'onprocess') {
            $data['rows'] = ProjectTask::join('projects', 'projects.project_id', '=', 'project_tasks.project_id')
                                ->join('project_task_types', 'project_task_types.project_task_type_id', '=', 'project_tasks.project_task_type_id')
                                ->join('users','users.user_id', '=', 'project_tasks.current_user')
                                ->where('project_tasks.flow_no','<>','98')
                                ->where('project_tasks.active', '=', '1')
                                ->where('project_tasks.current_user', '<>' , $request->user()->user_id)
                                ->where(function($query) use($request, $subordinate){
                                    $query->where('project_tasks.created_by', '=' , $request->user()->user_id)
                                            ->orWhereIn('project_tasks.created_by', $subordinate)
                                            ->orWhereIn('project_tasks.pic', $subordinate);
                                })
                                ->where(function($query) use($searchPhrase) {
                                    $query->orWhere('project_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_type_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_deadline','like','%' . $searchPhrase . '%')
                                            ->orWhere('user_firstname','like','%' . $searchPhrase . '%');
                                })
                                ->skip($skip)->take($rowCount)
                                ->orderBy($sort_column, $sort_type)->get();
            $data['total'] = ProjectTask::join('projects', 'projects.project_id', '=', 'project_tasks.project_id')
                                ->join('project_task_types', 'project_task_types.project_task_type_id', '=', 'project_tasks.project_task_type_id')
                                ->join('users','users.user_id', '=', 'project_tasks.current_user')
                                ->where('project_tasks.flow_no','<>','98')
                                ->where('project_tasks.active', '=', '1')
                                ->where('project_tasks.current_user', '<>' , $request->user()->user_id)
                                ->where(function($query) use($request, $subordinate){
                                    $query->where('project_tasks.created_by', '=' , $request->user()->user_id)
                                            ->orWhereIn('project_tasks.created_by', $subordinate)
                                            ->orWhereIn('project_tasks.pic', $subordinate);
                                })
                                ->where(function($query) use($searchPhrase) {
                                    $query->orWhere('project_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_type_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_deadline','like','%' . $searchPhrase . '%')
                                            ->orWhere('user_firstname','like','%' . $searchPhrase . '%');
                                })->count();    
        }elseif($listtype == 'needchecking') {
            $data['rows'] = ProjectTask::join('projects', 'projects.project_id', '=', 'project_tasks.project_id')
                                ->join('project_task_types', 'project_task_types.project_task_type_id', '=', 'project_tasks.project_task_type_id')
                                ->join('users','users.user_id', '=', 'project_tasks.created_by')
                                ->where('project_tasks.active','1')
                                ->where('project_tasks.flow_no','<>','98')
                                ->where('project_tasks.flow_no','<>','99')
                                ->where('project_tasks.current_user', '=' , $request->user()->user_id)
                                ->where(function($query) use($searchPhrase) {
                                    $query->orWhere('project_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_type_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_deadline','like','%' . $searchPhrase . '%')
                                            ->orWhere('user_firstname','like','%' . $searchPhrase . '%');
                                })
                                ->skip($skip)->take($rowCount)
                                ->orderBy($sort_column, $sort_type)->get();
            $data['total'] = ProjectTask::join('projects', 'projects.project_id', '=', 'project_tasks.project_id')
                                ->join('project_task_types', 'project_task_types.project_task_type_id', '=', 'project_tasks.project_task_type_id')
                                ->join('users','users.user_id', '=', 'project_tasks.created_by')
                                ->where('project_tasks.active','1')
                                ->where('project_tasks.flow_no','<>','98')
                                ->where('project_tasks.flow_no','<>','99')
                                ->where('project_tasks.current_user', '=' , $request->user()->user_id)
                                ->where(function($query) use($searchPhrase) {
                                    $query->orWhere('project_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_type_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_deadline','like','%' . $searchPhrase . '%')
                                            ->orWhere('user_firstname','like','%' . $searchPhrase . '%');
                                })->count();
        }elseif($listtype == 'finished') {
            $data['rows'] = ProjectTask::join('projects', 'projects.project_id', '=', 'project_tasks.project_id')
                                ->join('project_task_types', 'project_task_types.project_task_type_id', '=', 'project_tasks.project_task_type_id')
                                ->join('users','users.user_id', '=', 'project_tasks.created_by')
                                ->where('project_tasks.active','1')
                                ->where('project_tasks.flow_no','=','98')
                                ->where(function($query) use($request, $subordinate){
                                    $query->where('project_tasks.created_by', '=' , $request->user()->user_id)
                                            ->orWhereIn('project_tasks.created_by', $subordinate);
                                })
                                ->where(function($query) use($searchPhrase) {
                                    $query->orWhere('project_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_type_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_deadline','like','%' . $searchPhrase . '%')
                                            ->orWhere('user_firstname','like','%' . $searchPhrase . '%');
                                })
                                ->skip($skip)->take($rowCount)
                                ->orderBy($sort_column, $sort_type)->get();
            $data['total'] = ProjectTask::join('projects', 'projects.project_id', '=', 'project_tasks.project_id')
                                ->join('project_task_types', 'project_task_types.project_task_type_id', '=', 'project_tasks.project_task_type_id')
                                ->join('users','users.user_id', '=', 'project_tasks.created_by')
                                ->where('project_tasks.active','1')
                                ->where('project_tasks.flow_no','=','98')
                                ->where(function($query) use($request, $subordinate){
                                    $query->where('project_tasks.created_by', '=' , $request->user()->user_id)
                                            ->orWhereIn('project_tasks.created_by', $subordinate);
                                })
                                ->where(function($query) use($searchPhrase) {
                                    $query->orWhere('project_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_type_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_deadline','like','%' . $searchPhrase . '%')
                                            ->orWhere('user_firstname','like','%' . $searchPhrase . '%');
                                })->count();
        }elseif($listtype == 'canceled') {
            $data['rows'] = ProjectTask::join('projects', 'projects.project_id', '=', 'project_tasks.project_id')
                                ->join('project_task_types', 'project_task_types.project_task_type_id', '=', 'project_tasks.project_task_type_id')
                                ->join('users','users.user_id', '=', 'project_tasks.created_by')
                                ->where('project_tasks.active','0')
                                ->where(function($query) use($request, $subordinate){
                                    $query->where('project_tasks.created_by', '=' , $request->user()->user_id)
                                            ->orWhereIn('project_tasks.created_by', $subordinate);
                                })
                                ->where(function($query) use($searchPhrase) {
                                    $query->orWhere('project_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_type_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_deadline','like','%' . $searchPhrase . '%')
                                            ->orWhere('user_firstname','like','%' . $searchPhrase . '%');
                                })
                                ->skip($skip)->take($rowCount)
                                ->orderBy($sort_column, $sort_type)->get();
            $data['total'] = ProjectTask::join('projects', 'projects.project_id', '=', 'project_tasks.project_id')
                                ->join('project_task_types', 'project_task_types.project_task_type_id', '=', 'project_tasks.project_task_type_id')
                                ->join('users','users.user_id', '=', 'project_tasks.created_by')
                                ->where('project_tasks.active','0')
                                ->where(function($query) use($request, $subordinate){
                                    $query->where('project_tasks.created_by', '=' , $request->user()->user_id)
                                            ->orWhereIn('project_tasks.created_by', $subordinate);
                                })
                                ->where(function($query) use($searchPhrase) {
                                    $query->orWhere('project_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_type_name','like','%' . $searchPhrase . '%')
                                            ->orWhere('project_task_deadline','like','%' . $searchPhrase . '%')
                                            ->orWhere('user_firstname','like','%' . $searchPhrase . '%');
                                })->count();
        }

        

        return response()->json($data);
    }
    

    public function approve(Request $request, $flow_no, $id)
    {
        if($flow_no == 1) {
            return $this->approveFlowNo1($request, $id);
        }elseif($flow_no == 2) {
            return $this->approveFlowNo2($request, $id);
        }elseif($flow_no == 3) {
            return $this->approveFlowNo3($request, $id);
        }elseif($flow_no == 4) {
            return $this->approveFlowNo4($request, $id);
        }elseif($flow_no == 5) {
            return $this->approveFlowNo5($request, $id);
        }elseif($flow_no == 6) {
            return $this->approveFlowNo5($request, $id);
        }
    }

    public function postApprove(Request $request, $flow_no, $id)
    {
        if($flow_no == 1) {
            $this->postApproveFlowNo1($request, $id);
        }elseif($flow_no == 2) {
            $this->postApproveFlowNo2($request, $id);
        }elseif($flow_no == 3) {
            $this->postApproveFlowNo3($request, $id);
        }elseif($flow_no == 4) {
            $this->postApproveFlowNo4($request, $id);
        }elseif($flow_no == 5) {
            $this->postApproveFlowNo5($request, $id);
        }elseif($flow_no == 6) {
            $this->postApproveFlowNo6($request, $id);
        }

        return redirect('grid/projecttask');
    }

    private function approveFlowNo2(Request $request, $id)
    {
        if(Gate::denies('Project Task-Approval')) {
            abort(403, 'Unauthorized action.');
        }

        $data = array();

        $u = new UserLibrary;
        $subordinate = $u->getSubOrdinateArrayID($request->user()->user_id);

        $data['projecttask'] = ProjectTask::with(
                                            'project', 
                                            'projecttasktype',
                                            'uploadfiles',
                                            'projecttaskhistories',
                                            'projecttaskhistories.approvaltype'
                                            )->find($id);
        $data['deadline'] = Carbon::createFromFormat('Y-m-d', ($data['projecttask']->project_task_deadline==null) ? date('Y-m-d') : $data['projecttask']->project_task_deadline)->format('d/m/Y');

        $data['subordinate'] = User::whereIn('user_id',$subordinate)->get();

        return view('vendor.material.grid.projecttask.selectpic', $data);
    }

    private function postApproveFlowNo2(Request $request, $id)
    {
        if(Gate::denies('Project Task-Approval')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate($request, [
            'pic' => 'required',
            'approval' => 'required',
            'comment' => 'required',
        ]);

        if($request->input('approval') == '1') 
        {
            //approve
            $projecttask = ProjectTask::find($id);

            $flow = new FlowLibrary;
            $nextFlow = $flow->getNextFlow($this->flow_group_id, $projecttask->flow_no, $request->user()->user_id, $request->input('pic'), $projecttask->created_by);

            $projecttask->pic = $request->input('pic');
            $projecttask->flow_no = $nextFlow['flow_no'];
            $projecttask->current_user = $nextFlow['current_user'];
            $projecttask->updated_by = $request->user()->user_id;
            $projecttask->save();

            $his = new ProjectTaskHistory;
            $his->project_task_id = $id;
            $his->approval_type_id = 2;
            $his->project_task_history_text = $request->input('comment');
            $his->active = '1';
            $his->created_by = $request->user()->user_id;

            $his->save();

            $this->notif->remove($request->user()->user_id, 'projecttaskapproval', $id);
            $this->notif->remove($request->user()->user_id, 'projecttaskreject', $id);
            $this->notif->generate($request->user()->user_id, $nextFlow['current_user'], 'projecttaskapproval', 'Project Task "' . $projecttask->project_task_name . '" need approval.', $id);

            $request->session()->flash('status', 'Data has been saved!');

        }else{
            //reject
            $projecttask = ProjectTask::find($id);

            $flow = new FlowLibrary;
            $prevFlow = $flow->getPreviousFlow($this->flow_group_id, $projecttask->flow_no, $request->user()->user_id, '', $projecttask->created_by);

            $projecttask->flow_no = $prevFlow['flow_no'];
            $projecttask->revision_no = $projecttask->revision_no + 1;
            $projecttask->current_user = $prevFlow['current_user'];
            $projecttask->updated_by = $request->user()->user_id;
            $projecttask->save();

            $his = new ProjectTaskHistory;
            $his->project_task_id = $id;
            $his->approval_type_id = 3;
            $his->project_task_history_text = $request->input('comment');
            $his->active = '1';
            $his->created_by = $request->user()->user_id;

            $his->save();

            $this->notif->remove($request->user()->user_id, 'projecttaskapproval', $id);
            $this->notif->remove($request->user()->user_id, 'projecttaskreject', $id);
            $this->notif->generate($request->user()->user_id, $prevFlow['current_user'], 'projecttaskreject', 'Project Task "' . $projecttask->project_task_name . '" rejected.', $id);

            $request->session()->flash('status', 'Data has been saved!');
        }

    }

    private function approveFlowNo3(Request $request, $id)
    {
        if(Gate::denies('Project Task-Approval')) {
            abort(403, 'Unauthorized action.');
        }

        $data = array();

        $data['projecttask'] = ProjectTask::with(
                                            'project', 
                                            'projecttasktype',
                                            'uploadfiles',
                                            'projecttaskhistories',
                                            'projecttaskhistories.approvaltype'
                                            )->find($id);
        $data['deadline'] = Carbon::createFromFormat('Y-m-d', ($data['projecttask']->project_task_deadline==null) ? date('Y-m-d') : $data['projecttask']->project_task_deadline)->format('d/m/Y');
        $data['url'] = 'grid/projecttask/approve/' . $data['projecttask']->flow_no . '/' . $id;

        return view('vendor.material.grid.projecttask.approve', $data);
    }

    private function postApproveFlowNo3(Request $request, $id)
    {
        if(Gate::denies('Project Task-Approval')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate($request, [
            'approval' => 'required',
            'comment' => 'required',
        ]);

        if($request->input('approval') == '1') 
        {
            //approve
            $projecttask = ProjectTask::find($id);

            $flow = new FlowLibrary;
            $nextFlow = $flow->getNextFlow($this->flow_group_id, $projecttask->flow_no, $request->user()->user_id, $projecttask->pic, $projecttask->created_by);

            $projecttask->flow_no = $nextFlow['flow_no'];
            $projecttask->current_user = $nextFlow['current_user'];
            $projecttask->updated_by = $request->user()->user_id;
            $projecttask->save();

            $his = new ProjectTaskHistory;
            $his->project_task_id = $id;
            $his->approval_type_id = 2;
            $his->project_task_history_text = $request->input('comment');
            $his->active = '1';
            $his->created_by = $request->user()->user_id;

            $his->save();

            $this->notif->remove($request->user()->user_id, 'projecttaskapproval', $id);
            $this->notif->remove($request->user()->user_id, 'projecttaskreject', $id);
            $this->notif->generate($request->user()->user_id, $nextFlow['current_user'], 'projecttaskapproval', 'Project Task "' . $projecttask->project_task_name . '" need approval.', $id);

            $request->session()->flash('status', 'Data has been saved!');

        }else{
            //reject
            $projecttask = ProjectTask::with('projecttasktype')->find($id);

            $flow = new FlowLibrary;
            $prevFlow = $flow->getPreviousFlow($this->flow_group_id, $projecttask->flow_no, $request->user()->user_id, $projecttask->pic, $projecttask->created_by, $projecttask->projecttasktype->user_id);

            $projecttask->pic = 0;
            $projecttask->flow_no = $prevFlow['flow_no'];
            $projecttask->revision_no = $projecttask->revision_no + 1;
            $projecttask->current_user = $prevFlow['current_user'];
            $projecttask->updated_by = $request->user()->user_id;
            $projecttask->save();

            $his = new ProjectTaskHistory;
            $his->project_task_id = $id;
            $his->approval_type_id = 3;
            $his->project_task_history_text = $request->input('comment');
            $his->active = '1';
            $his->created_by = $request->user()->user_id;

            $his->save();

            $this->notif->remove($request->user()->user_id, 'projecttaskapproval', $id);
            $this->notif->remove($request->user()->user_id, 'projecttaskreject', $id);
            $this->notif->generate($request->user()->user_id, $prevFlow['current_user'], 'projecttaskreject', 'Project Task "' . $projecttask->project_task_name . '" rejected.', $id);

            $request->session()->flash('status', 'Data has been saved!');
        }

    }

    private function approveFlowNo4(Request $request, $id)
    {
        if(Gate::denies('Project Task-Approval')) {
            abort(403, 'Unauthorized action.');
        }

        $data = array();

        $data['projecttask'] = ProjectTask::with(
                                            'project', 
                                            'projecttasktype',
                                            'uploadfiles',
                                            'projecttaskhistories',
                                            'projecttaskhistories.approvaltype'
                                            )->find($id);
        $data['deadline'] = Carbon::createFromFormat('Y-m-d', ($data['projecttask']->project_task_deadline==null) ? date('Y-m-d') : $data['projecttask']->project_task_deadline)->format('d/m/Y');
        $data['url'] = 'grid/projecttask/approve/' . $data['projecttask']->flow_no . '/' . $id;

        return view('vendor.material.grid.projecttask.picform', $data);
    }

    private function postApproveFlowNo4(Request $request, $id)
    {
        if(Gate::denies('Project Task-Approval')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate($request, [
            'comment' => 'required',
        ]);

        $projecttask = ProjectTask::find($id);

        $flow = new FlowLibrary;
        $nextFlow = $flow->getNextFlow($this->flow_group_id, $projecttask->flow_no, $request->user()->user_id, $projecttask->pic, $projecttask->created_by);

        $projecttask->flow_no = $nextFlow['flow_no'];
        $projecttask->current_user = $nextFlow['current_user'];
        $projecttask->project_task_ready_date = Carbon::now()->toDateTimeString();
        $projecttask->updated_by = $request->user()->user_id;
        $projecttask->save();

        //file saving
        $fileArray = array();

        $tmpPath = 'uploads/tmp/' . $request->user()->user_id;
        $files = File::files($tmpPath);
        foreach($files as $key => $value) {
            $oldfile = pathinfo($value);
            $newfile = 'uploads/files/' . $oldfile['basename'];
            if(File::exists($newfile)) {
                $rand = rand(1, 100);
                $newfile = 'uploads/files/' . $oldfile['filename'] . $rand . '.' . $oldfile['extension'];
            }

            if(File::move($value, $newfile)) {
                $file = pathinfo($newfile);
                $filesize = File::size($newfile);

                $upl = new UploadFile;
                $upl->upload_file_type = $file['extension'];
                $upl->upload_file_name = $file['basename'];
                $upl->upload_file_path = $file['dirname'];
                $upl->upload_file_size = $filesize;
                $upl->upload_file_revision = $projecttask->revision_no;
                $upl->upload_file_desc = '';
                $upl->active = '1';
                $upl->created_by = $request->user()->user_id;

                $upl->save();

                array_push($fileArray, $upl->upload_file_id);
                $fileArray[$upl->upload_file_id] = [ 'revision_no' => $projecttask->revision_no ];
            }
        }

        if(!empty($fileArray)) {
            ProjectTask::find($projecttask->project_task_id)->uploadfiles()->syncWithoutDetaching($fileArray);    
        }

        $his = new ProjectTaskHistory;
        $his->project_task_id = $id;
        $his->approval_type_id = 1;
        $his->project_task_history_text = $request->input('comment');
        $his->active = '1';
        $his->created_by = $request->user()->user_id;

        $his->save();

        $this->notif->remove($request->user()->user_id, 'projecttaskapproval', $id);
        $this->notif->remove($request->user()->user_id, 'projecttaskreject', $id);
        $this->notif->generate($request->user()->user_id, $nextFlow['current_user'], 'projecttaskapproval', 'Project Task "' . $projecttask->project_task_name . '" need approval.', $id);

        $request->session()->flash('status', 'Data has been saved!');

    }

    private function approveFlowNo5(Request $request, $id)
    {
        if(Gate::denies('Project Task-Approval')) {
            abort(403, 'Unauthorized action.');
        }

        $data = array();

        $data['projecttask'] = ProjectTask::with(
                                            'project', 
                                            'projecttasktype',
                                            'uploadfiles',
                                            'projecttaskhistories',
                                            'projecttaskhistories.approvaltype'
                                            )->find($id);
        $data['deadline'] = Carbon::createFromFormat('Y-m-d', ($data['projecttask']->project_task_deadline==null) ? date('Y-m-d') : $data['projecttask']->project_task_deadline)->format('d/m/Y');
        $data['url'] = 'grid/projecttask/approve/' . $data['projecttask']->flow_no . '/' . $id;

        return view('vendor.material.grid.projecttask.approve', $data);
    }

    private function postApproveFlowNo5(Request $request, $id)
    {
        if(Gate::denies('Project Task-Approval')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate($request, [
            'approval' => 'required',
            'comment' => 'required',
        ]);

        if($request->input('approval') == '1') 
        {
            //approve
            $projecttask = ProjectTask::find($id);

            $flow = new FlowLibrary;
            $nextFlow = $flow->getNextFlow($this->flow_group_id, $projecttask->flow_no, $request->user()->user_id, $projecttask->pic, $projecttask->created_by);

            $projecttask->flow_no = $nextFlow['flow_no'];
            $projecttask->current_user = $nextFlow['current_user'];
            $projecttask->project_task_delivery_date = Carbon::now()->toDateTimeString();
            $projecttask->updated_by = $request->user()->user_id;
            $projecttask->save();

            $his = new ProjectTaskHistory;
            $his->project_task_id = $id;
            $his->approval_type_id = 2;
            $his->project_task_history_text = $request->input('comment');
            $his->active = '1';
            $his->created_by = $request->user()->user_id;

            $his->save();

            $this->notif->remove($request->user()->user_id, 'projecttaskapproval', $id);
            $this->notif->remove($request->user()->user_id, 'projecttaskreject', $id);
            $this->notif->generate($request->user()->user_id, $nextFlow['current_user'], 'projecttaskapproval', 'Project Task "' . $projecttask->project_task_name . '" need approval.', $id);

            $request->session()->flash('status', 'Data has been saved!');

        }else{
            //reject
            $projecttask = ProjectTask::with('projecttasktype')->find($id);

            $flow = new FlowLibrary;
            $prevFlow = $flow->getPreviousFlow($this->flow_group_id, $projecttask->flow_no, $request->user()->user_id, $projecttask->pic, $projecttask->created_by, $projecttask->projecttasktype->user_id);

            $projecttask->flow_no = $prevFlow['flow_no'];
            $projecttask->revision_no = $projecttask->revision_no + 1;
            $projecttask->current_user = $prevFlow['current_user'];
            $projecttask->updated_by = $request->user()->user_id;
            $projecttask->save();

            $his = new ProjectTaskHistory;
            $his->project_task_id = $id;
            $his->approval_type_id = 3;
            $his->project_task_history_text = $request->input('comment');
            $his->active = '1';
            $his->created_by = $request->user()->user_id;

            $his->save();

            $this->notif->remove($request->user()->user_id, 'projecttaskapproval', $id);
            $this->notif->remove($request->user()->user_id, 'projecttaskreject', $id);
            $this->notif->generate($request->user()->user_id, $prevFlow['current_user'], 'projecttaskreject', 'Project Task "' . $projecttask->project_task_name . '" rejected.', $id);

            $request->session()->flash('status', 'Data has been saved!');
        }

    }

    private function postApproveFlowNo6(Request $request, $id)
    {
        if(Gate::denies('Project Task-Approval')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate($request, [
            'approval' => 'required',
            'comment' => 'required',
        ]);

        if($request->input('approval') == '1') 
        {
            //approve
            $projecttask = ProjectTask::find($id);

            $flow = new FlowLibrary;
            $nextFlow = $flow->getNextFlow($this->flow_group_id, $projecttask->flow_no, $request->user()->user_id, $projecttask->pic, $projecttask->created_by);

            $projecttask->flow_no = $nextFlow['flow_no'];
            $projecttask->current_user = $nextFlow['current_user'];
            $projecttask->updated_by = $request->user()->user_id;
            $projecttask->save();

            $his = new ProjectTaskHistory;
            $his->project_task_id = $id;
            $his->approval_type_id = 4;
            $his->project_task_history_text = $request->input('comment');
            $his->active = '1';
            $his->created_by = $request->user()->user_id;

            $his->save();

            $this->notif->remove($request->user()->user_id, 'projecttaskapproval', $id);
            $this->notif->remove($request->user()->user_id, 'projecttaskreject', $id);
            $this->notif->generate($request->user()->user_id, $nextFlow['current_user'], 'projecttaskfinish', 'Project Task "' . $projecttask->project_task_name . '" has been finished.', $id);

            $request->session()->flash('status', 'Data has been saved!');

        }else{
            //reject
            $projecttask = ProjectTask::with('projecttasktype')->find($id);

            $flow = new FlowLibrary;
            $prevFlow = $flow->getPreviousFlow($this->flow_group_id, $projecttask->flow_no, $request->user()->user_id, $projecttask->pic, $projecttask->created_by, $projecttask->projecttasktype->user_id);

            $projecttask->flow_no = $prevFlow['flow_no'];
            $projecttask->revision_no = $projecttask->revision_no + 1;
            $projecttask->current_user = $prevFlow['current_user'];
            $projecttask->updated_by = $request->user()->user_id;
            $projecttask->save();

            $his = new ProjectTaskHistory;
            $his->project_task_id = $id;
            $his->approval_type_id = 3;
            $his->project_task_history_text = $request->input('comment');
            $his->active = '1';
            $his->created_by = $request->user()->user_id;

            $his->save();

            $this->notif->remove($request->user()->user_id, 'projecttaskapproval', $id);
            $this->notif->remove($request->user()->user_id, 'projecttaskreject', $id);
            $this->notif->generate($request->user()->user_id, $prevFlow['current_user'], 'projecttaskreject', 'Project Task "' . $projecttask->project_task_name . '" rejected.', $id);

            $request->session()->flash('status', 'Data has been saved!');
        }

    }
}