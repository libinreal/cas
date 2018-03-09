<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 15:06
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Service
 * @package app\Models
 *
 * @property string  $name
 * @property boolean $allow_proxy
 * @property boolean $enabled
 */
class Service extends Model
{
    protected $table = 'cas_services';
    protected $fillable = ['name', 'enabled', 'allow_proxy', 'api'];
    protected $casts = [
        'enabled'           => 'boolean',
        'allow_proxy'       => 'boolean',
    ];

    public function hosts()
    {
        return $this->hasMany(ServiceHost::class);
    }

    public function apis()
    {
        return $this->hasMany(ServiceApi::class);
    }

    /**
     * Get multi cas users who using the service
     * @return Relation
     */
    public function casUsers()
    {
        return $this->belongsToMany(CasUser::class, 'cas_service_users', 'service_id', 'cas_user_id');
    }
}
