<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Role extends Model{
	protected $table = 'roles';
	protected $primaryKey = 'role_id';

	protected $fillable = [
				'role_name', 'role_desc'
	];

	protected $hidden = [
				'active', 'created_by', 'created_at', 'updated_by', 'updated_at'
	];

	public function users() {
        return $this->belongsToMany('App\User','users_roles');
    }

    public function getApiList($start = 0, $limit = 10, $search = '', $sort_name = 'role_id', $sort_type = 'asc')
    {
    	return DB::table('roles')->where('active','1')->where('role_name','like','%$search%')->skip($start)->take($limit)->get();
    }
}