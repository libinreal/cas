<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/18
 * Time: 21:08
 */

namespace App\Repositories;

use App\Exceptions\UserException;
use App\Traits\ValidateInput;
use App\Models\Service;
use App\Models\ServiceHost;
use App\Models\ServiceApi;

class ServiceRepository
{
    use ValidateInput;

    /**
     * @var Service
     */
    protected $service;

    /**
     * @var ServiceHost;
     */
    protected $serviceHost;

    /**
     * @var ServiceApi;
     */
    protected $serviceApi;

    /**
     * ServiceRepository constructor.
     * @param Service     $service
     * @param ServiceHost $serviceHost
     */
    public function __construct(Service $service, ServiceHost $serviceHost, ServiceApi $serviceApi)
    {
        $this->service     = $service;
        $this->serviceHost = $serviceHost;
        $this->serviceApi = $serviceApi;
    }

    /**
     * @param $data
     * @throws UserException
     * @return Service
     */
    public function create($data)
    {
        $this->validate(
            $data,
            [
                'name'              => 'required|unique:cas_services',
                'hosts'             => 'array',
                'hosts.*'           => 'unique:cas_service_hosts,host',
                'api'               => 'array|different_in_array:name',
                'enabled'           => 'required|boolean',
                'allow_proxy'       => 'required|boolean',
            ]
        );

        \DB::beginTransaction();
        $service = $this->service->create(
            [
                'name'              => $data['name'],
                'enabled'           => $data['enabled'],                
                'allow_proxy'       => $data['allow_proxy'],
            ]
        );

        foreach ($data['hosts'] as $host) {
            $hostModel = $this->serviceHost->newInstance(['host' => $host]);
            $hostModel->service()->associate($service);
            $hostModel->save();
        }

        foreach ($data['api'] as $api) {
            $apiModel = $this->serviceApi->newInstance([
                'name'              => trim($api['name']),
                'url'               => trim($api['url']),
                'method'            => $api['method'],
                'fields'            => trim($api['fields']),
                'response_fields'   => trim($api['response_fields']),
                ]);
            $apiModel->service()->associate($service);
            $apiModel->save();
        }

        \DB::commit();

        return $service;
    }

    public function update($data, Service $service)
    {
        $data = array_only(
            $data,
            [
                'hosts',
                'enabled',
                'allow_proxy',
                'api',
            ]
        );

        \DB::beginTransaction();

        $service->hosts()->delete();
        $service->apis()->delete();

        $this->validate(
            $data,
            [
                'hosts'             => 'array',
                'api'               => 'array|different_in_array:name',
                'hosts.*'           => 'unique:cas_service_hosts,host',
                'enabled'           => 'boolean',
                'allow_proxy'       => 'boolean',
            ]
        );

        $hosts = array_get($data, 'hosts', []);
        $apis = array_get($data, 'api', []);

        unset($data['hosts'], $data['api']);

        $service->update($data);
        foreach ($hosts as $host) {
            $hostModel = $this->serviceHost->newInstance(['host' => $host]);
            $hostModel->service()->associate($service);
            $hostModel->save();
        }

        foreach ($apis as $api) {
            $apiModel = $this->serviceApi->newInstance([
                'name'              => trim($api['name']),
                'url'               => trim($api['url']),
                'method'            => $api['method'],
                'fields'            => trim($api['fields']),
                'response_fields'   => trim($api['response_fields']),
                ]);
            $apiModel->service()->associate($service);
            $apiModel->save();
        }
        \DB::commit();

        return $service;
    }

    /**
     * @param string $search
     * @param int    $page
     * @param int    $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getList($search, $page, $limit)
    {
        /* @var \Illuminate\Database\Query\Builder $query */
        $like = '%'.$search.'%';
        if (!empty($search)) {
            $query = $this->service->whereHas(
                'hosts',
                function ($query) use ($like) {
                    $query->where('host', 'like', $like);
                }
            )->orWhere('name', 'like', $like)->with('hosts')->with('apis');
        } else {
            $query = $this->service->with('hosts')->with('apis');
        }

        return $query->orderBy('id', 'desc')->paginate($limit, ['*'], 'page', $page);
    }

    public function dashboard()
    {
        return [
            'total'   => $this->service->count(),
            'enabled' => $this->service->where('enabled', true)->count(),
        ];
    }

    public function getServiceByUrl($url)
    {
        $host = parse_url($url, PHP_URL_HOST);

        $record = $this->serviceHost->where('host', $host)->first();
        if (!$record) {
            return null;
        }

        return $record->service;
    }

    /**
     * @param $url
     * @return bool
     */
    public function isUrlValid($url)
    {
        $service = $this->getServiceByUrl($url);

        return $service !== null && $service->enabled;
    }
}