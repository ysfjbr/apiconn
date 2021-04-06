<?php

namespace Gnm\ApiConn;

class ApiModel{

    protected $entity;

    protected $primaryKey;

    protected $apiW;

    public function __construct($entity, $primaryKey = 'id')
    {
        $this->entity = $entity;
        $this->primaryKey = $primaryKey;
        $this->apiW = app(ApiWrapper::class);
    }

    public function all($params=[])
    {
        return $this->apiW->getData($this->entity,"GET", $params);
    }

    public function create($params=[])
    {
        return $this->apiW->getData($this->entity,"POST", $params);
    }

    public function update($params=[])
    {
        return $this->apiW->getData($this->entity,"PUT", $params);
    }

}
