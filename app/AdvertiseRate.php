<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdvertiseRate extends Model
{
    protected $table = 'advertise_rates';
	protected $primaryKey = 'advertise_rate_id';

	protected $fillable = [
				'media_id',
				'advertise_position_id',
				'advertise_size_id',
				'advertise_rate_code',
				'advertise_rate_normal',
				'advertise_rate_discount',
	];

	protected $hidden = [
				'active', 'created_by', 'created_at', 'updated_by', 'updated_at'
	];

	public function media()
	{
		return $this->belongsTo('App\Media','media_id');
	}

	public function advertisesize()
	{
		return $this->belongsTo('App\AdvertiseSize','advertise_size_id');
	}

	public function advertiseposition()
	{
		return $this->belongsTo('App\AdvertisePosition','advertise_position_id');
	}
}
