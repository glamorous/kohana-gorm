<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Gorm_Model is the class all models must extend. It handles
 * relationships to other models.
 *
 * @package Gorm
 */
abstract class Gorm_Model
{
	/**
	 * @var  string  Model shortname
	 */
	protected $_model;

	/**
	 * @var  int  primary key
	 */
	protected $_primary_key = 'id';

	/**
	 * @var  bool  Loaded
	 */
	protected $_loaded = FALSE;
	/**
	 * @var  string  table name
	 */
	protected $_table;

	/**
	 * @var  object  Service for this model
	 */
	protected $_service;

	/**
	 * @var  array  Array with all properties for the object
	 */
	protected $_fields = array();

	/**
	 * @var  array  Array with all ignored properties for the service
	 */
	protected $_ignored = array();

	/**
	 * Constructor.
	 *
	 * If $id is passed and it is an array, it will be
	 * applied to the model as if it were a database result.
	 * The model is then considered to be loaded.
	 *
	 * @param  mixed  $id
	 */
	public function __construct($id = NULL)
	{
		// Set model shortname
		$this->_model = strtolower(substr(get_class($this), 6));
		$this->_table = Inflector::plural($this->getModel());

		// Set array with all properties without the ignored ones
		$properties = get_class_vars(get_class($this));

		foreach($properties as $property => $value)
		{
			$field = substr($property, 1);
			if(substr($property, 0, 1) == '_' AND ! in_array($field, $this->_fields) AND ! in_array($field, $this->_getIgnored_fields()))
			{
				$this->_fields[] = $field;
			}
		}

		// Load record if an id is provided
		if($id !== NULL)
		{
			$service = $this->_getService();
			$output = $service->select( (int) $id, FALSE);
			if( ! empty($output))
			{
				$this->set($output);
				$this->_loaded = TRUE;
			}
		}
	}

	/**
	 * Get or set field values
	 *
	 * @param   string  $name
	 * @return  mixed
	 */
	public function __call($name, $arguments)
	{
		$prefixes = array('get', 'set');
		$prefix = substr($name, 0, 3);
		// Check if we have a getter/setter
		if(in_array($prefix, $prefixes))
		{
			$name = strtolower(substr($name, 3));
			$field = '_'.$name;

			if(in_array($name, $this->_fields))
			{
				switch($prefix)
				{
					case 'get':
						return $this->$field;
						break;
					case 'set':
						$this->$field = $arguments[0];
						break;
				}
			}
			else
			{
				throw new Gorm_Exception('The field `:name` does\'t exists', array(':name' => $field));
			}
		}
		else
		{
			throw new Gorm_Exception('The method does\'t exists');
		}
	}

	/*
	 * Magic method __get
	 */
	public function __get($name)
	{
		$method = 'get'.ucFirst($name);
		$this->$method();
	}

	/*
	 * Magic method __set
	 */
	public function __set($name, $value)
	{
		$method = 'set'.ucFirst($name);
		$this->$method($value);
	}

	/**
	 * Returns an array of values in the fields.
	 *
	 * You can pass a variable number of field names
	 * to only retrieve those fields in the array:
	 *
	 *     $model->as_array('id', 'name', 'status');
	 *
	 * @param  string  $fields
	 * @param  ...
	 * @return array
	 */
	public function as_array($fields = NULL)
	{
		if(func_num_args() > 1)
		{
			$fields = func_get_args();
		}
		elseif( ! is_array($fields) OR empty($fields))
		{
			$fields = $this->getFields();
		}

		$fields = array_intersect($fields, $this->getFields());
		$result = array();

		foreach($fields as $field)
		{
			$method = 'get'.ucFirst($field);
			$result[$field] = $this->$method();
		}

		return $result;
	}

	/**
	 * Returns a JSON-object with the values in the fields.
	 *
	 * You can pass a variable number of field names
	 * to only retrieve those fields in the JSON-object:
	 *
	 *     $model->as_json('id', 'name', 'status');
	 *
	 * @param  string  $fields
	 * @param  ...
	 * @return json
	 */
	public function as_json($fields = NULL)
	{
		$fields = func_get_args();
		return json_encode($this->as_array($fields));
	}

	/*
	 * Set a model with an array with values
	 *
	 * @param   $values
	 * @return  $this
	 */
	public function set($values = array(), $loaded = FALSE)
	{
		$values = Arr::extract($values, $this->_fields);
		foreach($values as $field => $value)
		{
			$method = 'set'.ucFirst($field);
			$this->$method($value);
		}

		$this->_loaded = TRUE;

		return $this;
	}

	/**
	 * Creates or updates the current record/model.
	 *
	 * If an $id is passed the model will be assumed to exist
	 * and an update will be executed, even if the model isn't loaded().
	 *
	 * @return  $this
	 **/
	public function save()
	{
		$service = $this->_getService();
		return $service->save($this);
	}

	/**
	 * Deletes the current record/model.
	 *
	 * @return  bool
	 **/
	public function delete()
	{
		$id = (int) $this->getId();
		if($id > 0)
		{
			$service = $this->_getService();
			return (bool) $service->delete($id);
		}
		else
		{
			// Because the $id doesn't exists, it isn't available so already `deleted`
			return TRUE;
		}
	}

	/*
	 * Get the service for this model
	 *
	 * @return  object  Service
	 */
	protected function _getService()
	{
		if( ! $this->_service instanceof Service)
		{
			$class = 'Service_'.ucfirst($this->_model);
			$this->_service = (class_exists($class)) ? new $class : new Service($this->_model);
		}
		return $this->_service;
	}

	/*
	 * Get all ignored fields
	 *
	 * @return array
	 */
	protected function _getIgnored_fields()
	{
		$core_ignore = array(
			'fields',
			'service',
			'model',
			'table',
			'ignored',
			'loaded',
			'primary_key',
		);
		return Arr::merge($this->_ignored, $core_ignore);
	}

	/*
	 * Get model shortname
	 *
	 * @return string
	 */
	public function getModel()
	{
		return $this->_model;
	}

	/*
	 * Get model tablename
	 *
	 * @return string
	 */
	public function getTable()
	{
		return $this->_table;
	}

	/*
	 * Get model fields
	 *
	 * @return string
	 */
	public function getFields()
	{
		return $this->_fields;
	}
}