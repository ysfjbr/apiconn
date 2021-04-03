<?php

namespace Gnm\ApiConn;

class ApiModel{

    protected $entity;

    protected $primaryKey = 'id';

    protected $apiW;

    public function __construct($entity)
    {
        $this->entity = $entity;
        $this->apiW = app(ApiWrapper::class);
    }

    public function all($params=[])
    {
        return $this->apiW->getData($this->entity,"GET", $params);
    }


    public function save($params=[])
    {
        return $this->apiW->getData($this->entity,"POST", $params);
    }

    public function update($params=[])
    {
        return $this->apiW->getData($this->entity,"PUT", $params);
    }

}
