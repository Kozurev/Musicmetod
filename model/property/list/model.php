<?php

class Property_List_Model extends Entity 
{
	protected $id;
	protected $property_id;
	protected $model_name;
	protected $object_id;
	protected $value_id;


	public function __construct()
	{
		
	}


	public function getId(){
		return $this->id;}


	public function property_id($val = null)
	{
		if(is_null($val))	return $this->property_id;
		$this->property_id = intval($val);
		return $this;
	}


	public function model_name($val = null)
	{
		if(is_null($val)) 	return $this->model_name();
		if(strlen($val) > 100)
            die(Core::getMessage("TOO_LARGE_VALUE", array("model_name", "Property_List", 100)));

		$this->model_name = $val;
		return $this;
	}


	public function object_id($val = null)
	{
		if(is_null($val))	return $this->object_id;
		$this->object_id = intval($val);
		return $this;
	}


	public function value($val = null)
	{
		if(is_null($val))	return $this->value_id;
		$this->value_id = intval($val);
		return $this;
	}


}