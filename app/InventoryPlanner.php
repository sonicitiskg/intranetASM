<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryPlanner extends Model
{
    protected $table = 'inventories_planner';
	protected $primaryKey = 'inventory_planner_id';

	protected $fillable = [
				'inventory_type_id', 
				'inventory_planner_title',
				'inventory_planner_deadline',
				'inventory_planner_desc',
				'flow_no',
				'revision_no',
				'current_user',
	];

	protected $hidden = [
				'active', 'created_by', 'created_at', 'updated_by', 'updated_at'
	];

	public function inventorytype()
	{
		return $this->belongsTo('App\InventoryType', 'inventory_type_id');
	}

	public function medias() 
	{
		return $this->belongsToMany('App\Media', 'inventory_planner_media');
	}

	public function uploadfiles()
	{
		return $this->belongsToMany('App\UploadFile', 'inventory_planner_upload_file');
	}

	public function actionplans()
	{
		return $this->belongsToMany('App\ActionPlan', 'inventory_planner_action_plan');
	}

	public function eventplans()
	{
		return $this->belongsToMany('App\EventPlan', 'inventory_planner_event_plan');
	}

	public function implementations()
	{
		return $this->belongsToMany('App\Implementation', 'inventory_planner_implementation');
	}

	public function inventoryplannerprices()
	{
		return $this->hasMany('App\InventoryPlannerPrice', 'inventory_planner_id');
	}

	public function inventoryhistories()
	{
		return $this->hasMany('App\InventoryPlannerHistory', 'inventory_planner_id');
	}
}
