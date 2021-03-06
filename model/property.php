<?php

/**
 * Класс реализующий методы для работы с доп. свойствами объектов
 *
 * @author Bad Wolf
 * @date ...
 * @version 20190220
 * @version 20190529
 * @version 20190711
 * Class Property
 */
class Property extends Property_Model
{
    /**
     * Поиск доп. свйоства по уникальному названию
     *
     * @param $tagName
     * @return Property|null
     */
    public function getByTagName($tagName)
    {
        return $this->queryBuilder()
            ->clearQuery()
            ->where('tag_name', '=', $tagName)
            ->find();
    }


    /**
     * Возвращает список значений свойства объекта
     *
     * @param $obj - объект, значения свойства которого будет возвращено
     * @return array
     */
	public function getValues($obj)
    {
        if (empty($this->id) || !$this->active()) {
            return [];
        }

        $tableName = 'Property_' . ucfirst($this->type());
        $modelName = $obj->getTableName();
        $EmptyValue = Core::factory($tableName)
            ->propertyId($this->id)
            ->modelName($modelName)
            ->value($this->defaultValue());

        if ($obj->getId() == 0) {
            return [$EmptyValue];
        }

        $EmptyValue->objectId($obj->getId());

        $PropertyValues = Core::factory($tableName)->queryBuilder()
            ->where('property_id', '=', $this->getId())
            ->where('model_name', '=', $modelName)
            ->where('object_id', '=', $obj->getId())
            ->findAll();

        if (count($PropertyValues) == 0) {
            return [$EmptyValue];
        } else {
            return $PropertyValues;
        }
    }


    /**
     * Аналог метода getValues для обратной совместимости
     *
     * @param $obj
     * @return array
     */
    public function getPropertyValues($obj)
    {
        return $this->getValues($obj);
    }


	/**
	 * Метод возвращает список свойств объекта
     *
	 * @param $obj - объект, свойства которого бутуд возвращены
	 * @return array
     * @deprecated
	 */
	public function getPropertiesList($obj)
	{
	    return self::getProperties($obj);
	}


    /**
     * Метод возвращает список свойств объекта
     *
     * @param $object - объект, свойства которого бутуд возвращены
     * @return array
     */
	public static function getProperties($object) : array
    {
        if (!is_object($object) || !method_exists($object, 'getId')) {
            return [];
        }

        $types = self::getPropertyTypes();
        $properties = [];

        foreach ($types as $type) {
            $tableName = 'Property_' . $type . '_Assigment';
            $typeProperties = Core::factory($tableName)
                ->queryBuilder()
                ->where('object_id', '=', $object->getId())
                ->where('model_name', '=', $object->getTableName())
                ->findAll();

            foreach ($typeProperties as $propertyAssignment) {
                $properties[] = Core::factory('Property', $propertyAssignment->property_id());
            }
        }

        return $properties;
    }


	/**
	 * Добавление свойства в список свойств объекта
     *
	 * @param $obj - объект, которому добавляется свойство
	 * @param $propertyId - id свойства, которое необходимо добавить в список свойств
	 * @return null|object
	 */
	public function addToPropertiesList($obj, $propertyId)
	{
        $Property = Core::factory('Property', $propertyId);
        if (is_null($Property)) {
            return null;
        }

        $tableName = 'Property_' . $Property->type() . '_Assigment';

        $obj->getId() == 0
            ?   $objectId = 0
            :   $objectId = $obj->getId();

        $Assignment = Core::factory($tableName)
            ->queryBuilder()
            ->where('object_id', '=', $objectId)
	        ->where('property_id', '=', $propertyId)
            ->where('model_name', '=', $obj->getTableName())
            ->find();

        if (!is_null($Assignment)) {
            return $Assignment;
        }

        $NewAssignment = Core::factory($tableName)
            ->propertyId($propertyId)
            ->objectId($obj->getId())
            ->modelName($obj->getTableName());
        $NewAssignment->save();
        return $NewAssignment;
	}


	/**
	 * Удаление свойства из списка
     *
	 * @param $obj - объект, у которого удаляется свойство
	 * @param $propertyId - id свойства, которое необходимо удалить из списка свойств
	 * @return null|Property
	 */
	public function deleteFromPropertiesList($obj, $propertyId)
	{
        $Property = Core::factory('Property', $propertyId);
        if (is_null($Property)) {
            return null;
        }

        $tableName = 'Property_' . $Property->type() . '_Assigment';

        $obj->getId() == 0
            ?   $objectId = 0
            :   $objectId = $obj->getId();

        $Assignment = Core::factory( $tableName)
            ->where('object_id', '=', $objectId)
            ->where('property_id', '=', $propertyId)
            ->where('model_name', '=', $obj->getTableName())
            ->find();

        if (!is_null($Assignment)) {
            $Assignment->delete();
        }
        return $this;
	}



	/**
	 * Добавление нового значения свойства
     *
	 * @param $obj - объект, для которого добавляется новое значение
	 * @param $val - значение добавляемого свойства к объекту
	 * @return null|object
	 */
	public function addNewValue($obj, $val)
	{
		if (!$this->active()) {
            return null;
        }

		$tableName = 'Property_' . ucfirst($this->type());

		$NewPropertyValue = Core::factory($tableName)
			->property_id($this->id)
			->model_name($obj->getTableName())
			->object_id($obj->getId())
			->value($val);
        $NewPropertyValue->save();
        return $NewPropertyValue;
	}


	/**
	 * Возвращает значения свойства типа "список" (list)
     *
     * @return array of objects
	 */
	private function getPropertyListValues($obj)
	{
		if ($this->type != 'list') {
            return [];
        }

		$PropertyListValues = Core::factory('Property_List')
		    ->queryBuilder()
			->where('property_id', '=', $this->id)
			->where('object_id', '=', $obj->getId())
            ->where('model_name', '=', $obj->getTableName())
			->findAll();

		if (count($PropertyListValues) == 0 && $this->default_value != '') {
            $PropertyListValues[] = Core::factory('Property_List')
                ->property_id($this->id)
                ->object_id($obj->getId())
                ->model_name($obj->getTableName())
                ->value($this->default_value);
        }

		$ListItems = [];
		foreach ($PropertyListValues as $ListValue) {
		    if ($ListValue->value() == 0) {
                continue;
            }

			$ListItems[] = Core::factory('Property_List_Values')
                ->queryBuilder()
				->where('property_id', '=', $this->id)
				->where('id', '=', $ListValue->value())
				->find();
		}

		return $ListItems;
	}


    public function delete($obj = null)
    {
        $tableName = 'Property_' . ucfirst($this->type());

        $Assignments = Core::factory($tableName . '_Assigment')
            ->queryBuilder()
            ->where('property_id', '=', $this->id)
            ->findAll();
        foreach ($Assignments as $Assignment) {
            $Assignment->delete();
        }

        $Values = Core::factory($tableName)
            ->queryBuilder()
            ->where('property_id', '=', $this->id)
            ->findAll();
        foreach ($Values as $Value) {
            $Value->delete();
        }

        parent::delete();
    }


    /**
     * @param $obj
     * @return void
     */
    public function clearForObject($obj) : void
    {
        self::purgeForObject($obj);
    }

    /**
     * @param $object
     */
    public static function purgeForObject($object) : void
    {
        if (!is_object($object) || !method_exists($object, 'getId')) {
            return;
        }

        foreach (self::getProperties($object) as $prop) {
            $values = $prop->getPropertyValues($object);
            foreach ($values as $value) {
                $value->delete();
            }
        }

        foreach (self::getPropertyTypes() as $type) {
            $tableName = 'Property_' . $type . '_Assigment';
            $assignments = Core::factory($tableName)
                ->queryBuilder()
                ->where('model_name', '=', $object->getTableName())
                ->where('object_id', '=', $object->getId())
                ->findAll();
            foreach ($assignments as $assignment) {
                $assignment->delete();
            }
        }
    }


    /**
     * Поиск всех доп. свйоств связанны с объектом включая его "родителя"
     *
     * @param $obj
     * @return array
     */
    public function getAllPropertiesList($obj)
    {
        $types = $this->getPropertyTypes();
        $Properties = [];

        empty($obj->getId())
            ?   $objectId = 0
            :   $objectId = $obj->getId();

        foreach ($types as $type) {
            $Assignments = Core::factory('Property_' . $type . '_Assigment')
                ->queryBuilder()
                ->where('model_name', '=', $obj->getTableName())
                ->open()
                    ->where('object_id', '=', $objectId)
                    ->orWhere('object_id', '=', 0)
                ->close()
                ->findAll();
            foreach ($Assignments as $Assignment) {
                $Properties[] = Core::factory('Property', $Assignment->property_id());
            }
        }

        if (method_exists($obj, 'getParent')) {
            $Parent = $obj->getParent();
            if (!empty($Parent->getId())) {
                $ParentProperties = Core::factory('Property')->getAllPropertiesList($Parent);
                $Properties = array_merge($ParentProperties, $Properties);
            }
        }

        //Отсеивание повторяющихся свойств
        $propertiesIds = [];
        $returnsProperties = [];
        foreach ($Properties as $Property) {
            $propertiesIds[$Property->getId()] = 1;
        }
        foreach ($Properties as $Property) {
            if (Core_Array::getValue($propertiesIds, $Property->getId(), null) == 1) {
                $returnsProperties[] = clone $Property;
                $propertiesIds[$Property->getId()] = 0;
            }
        }
        return $returnsProperties;
    }


    /**
     * Поиск элементов списка дополнительного свойства
     *
     * @param bool $isSubordinate
     * @return array
     */
    public function getList($isSubordinate = true)
    {
        if ($this->type != 'list' || $this->getId() == 0) {
            return [];
        }

        $List = Core::factory('Property_List_Values');

        if ($isSubordinate === true) {
            $User = User_Auth::current();
            if (is_null($User)) {
                return [];
            }
            $List->queryBuilder()
                ->where('subordinated', '=', $User->getDirector()->getId());
        }

        return $List->queryBuilder()
            ->where('property_id', '=', $this->id)
            ->orderBy('sorting')
            ->orderBy('id', 'DESC')
            ->findAll();
    }


    /**
     * Создание объекта значения доп. свйоства со значением по умолчанию
     *
     * @param $obj
     * @return mixed
     */
    public function makeDefaultValue($obj)
    {
        return Core::factory('Property_' . $this->type())
            ->modelName(get_class($obj))
            ->objectId($obj->getId())
            ->propertyId($this->getId())
            ->value($this->default_value);
    }

}