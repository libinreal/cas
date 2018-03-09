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
        'user_name',
        'service_id',
    ];
    
    protected $table = 'cas_service_users';
    protected $primaryKey = ['service_id', 'cas_user_id'];

    /**
     * get the user data of the current service user
     * @return Relation
     */
    public function casUser()
    {
        return $this->belongsTo(CasUser::class);
    }

    /**
     * get the service data of the current service user
     * @return Relation
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Update the creation and update timestamps.
     *
     * @return void
     */
    protected function updateTimestamps()
    {
        $time = $this->freshTimestamp();

        if (! $this->isDirty(static::UPDATED_AT)) {
            $this->setUpdatedAt($time);
        }
    }
}
