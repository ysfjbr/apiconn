<?php

namespace Gnm\ApiConn;

class ApiModel{

    protected $entity;

    protected $primaryKey;

    public $apiW;

    public function __construct($wrapper, $entity, $primaryKey = 'id')
    {
        $this->entity = $entity;
        $this->primaryKey = $primaryKey;
        $this->apiW = $wrapper;
    }

    public function all($params=[])
    {
        return $this->apiW->getData($this->entity,"GET", $params);
    }

    public function show($id, $params=[])
    {
        return $this->apiW->getData($this->entity.'/'.$id,"GET", $params);
    }

    public function create($params=[])
    {
        return $this->apiW->getData($this->entity,"POST", $params);
    }

    public function update($params=[])
    {
        return $this->apiW->getData($this->entity,"PUT", $params);
    }

    public function delete($params=[])
    {
        return $this->apiW->getData($this->entity,"DELETE", $params);
    }

    public function request($requsetURL="",$method = 'GET', $params=[])
    {
        return $this->apiW->getData($this->entity,$method, $params, $requsetURL);
    }
}
