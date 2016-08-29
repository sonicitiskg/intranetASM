<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActionPlan extends Model
{
    protected $table = 'action_plans';
	protected $primaryKey = 'action_plan_id';

	protected $fillable = [
				'action_type_id', 
				'action_plan_title', 
				'action_plan_desc', 
				'action_plan_startdate',
				'action_plan_enddate'
	];

	protected $hidden = [
				'active', 'created_by', 'created_at', 'updated_by', 'updated_at'
	];

	public function actiontype() {
		return $this->belongsTo('App\ActionType', 'action_type_id');
	}

	public function media() 
	{
		return $this->belongsToMany('App\Media', 'action_plan_media');
	}

	public function mediaedition()
	{
		return $this->belongsToMany('App\MediaEdition', 'action_plan_media_edition');
	}

	public function uploadfile() 
	{
		return $this->belongsToMany('App\UploadFile', 'action_plan_upload_file');
	}
}