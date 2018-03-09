<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 15:17
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CasServiceApi
 * @package app\Models
 *
 * @property integer $service_id
 * @property Service $service
 */
class ServiceApi extends Model
{
    protected $table = 'cas_service_apis';
    public $timestamps = false;
    protected $fillable = ['name', 'url', 'method', 'fields', 'response_fields'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function hosts()
    {
    	return $this->belongsToMany(ServiceHost::class, 'cas_service', 'service_id', 'service_id');
    }
}
