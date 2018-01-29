<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\HasCompositePrimaryKey;

class CasServiceUser extends Model
{
	use HasCompositePrimaryKey;

	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'service_id',
        'user_name',
        'token',
    ];

    protected $table = 'cas_service_users';
    protected $primaryKey = ['service_id', 'user_name'];

    /**
     * get the user in the current service
     * @return \App\Models\CasUser
     */
    public function user()
    {
        return $this->belongsTo('App\Models\CasUser', 'token', 'token');
    }
}
