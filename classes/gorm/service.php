<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Gorm_Service is the class all services must extend. It handles
 * database accessing and other services.
 *
 * @package Gorm
 */
abstract class Gorm_Service
{
	/**
	 * @var  object  Model shortname
	 */
	protected $_model;

	/**
	 * @var  array  Gorm_Providers
	 */
	protected $_providers = array();

	/**
	 * @var  object  default Service_Provider
	 */
	protected $_default_provider;

	/**
	 * @var  object  custom Service
	 */
	protected $_custom_service;

	/**
	 * Constructor.
	 *
	 * @param  mixed  $model
	 */
	public function __construct($model = NULL, $default_provider = 'database')
	{
		$service_class = 'Service_'.ucfirst($model);

		// Check if a custom service exists and set it
		if(class_exists($service_class))
		{
			$this->_custom_service = new $service_class;
		}
		// Set model
		$model = ($model === NULL) ? strtolower(substr(get_class($this), 8)) : $model;
		$class = 'Model_'.ucfirst($model);

		// Check if the model exists
		if(class_exists($class))
		{
			$this->_model = $model;
		}
		else
		{
			throw new Gorm_Exception('The model `:name` you have provided doesn\'t exist', array(':name' => $model));
		}

		// Set default provider to DB if it isn't set
		$this->_default_provider = $default_provider;

		$provider = 'Service_Provider_'.ucfirst($this->_default_provider);

		// Check if the default provider exists
		if(class_exists($provider))
		{
			$this->_providers[$this->_default_provider] = new $provider($this->_model);
		}
		else
		{
			throw new Gorm_Exception('Default provider can not be set to `:name` because the class doesn\'t exist', array(':name' => $provider));
		}
	}

	/**
	 * Select one or more models trough the service
	 *		$id can be different values:
	 *			- one ID as integer
	 *			- array with ID's
	 *			- one Model
	 *			- array with all the models
	 *			- empty so an query builder can be returned
	 *
	 * @param  mixed  $id
	 * @param  bool   $convert Convert the result to a Model or not
	 * @return Gorm_Model  When $id is given
	 * @return array with Gorm_Model
	 * @return Database_Query_Builder When no $id is given
	 */
	public function select($id = NULL, $convert = TRUE)
	{
		$results = $this->_providers[$this->_default_provider]->select($this->_getIds($id));

		if($id === NULL)
		{
			return $results;
		}
		else
		{
			$one_item = (is_array($id)) ? FALSE : TRUE;
			$models = ($convert === TRUE) ? $this->convert_results($results) : $results;

			return ($one_item === TRUE AND count($models) == 1) ? $models[0] : $models;
		}
	}

	/**
	 * Select one or more models trough the service
	 *		$id can be different values:
	 *			- one ID as integer
	 *			- array with ID's
	 *			- one Model
	 *			- array with all the models
	 *			- empty so an query builder can be returned
	 *
	 * @param  mixed    $id
	 * @return boolean  When $id is given
	 * @return Database_Query_Builder When no $id is given
	 */
	public function delete($id = NULL)
	{
		return $this->_providers[$this->_default_provider]->delete($this->_getIds($id));
	}

	/**
	 * Save the model trough the service
	 *
	 * @param  mixed  $model
	 * @param  bool   $force  Force to save NULL values when property from a field isn't set
	 * @return int    insert id for creating a model
	 * @return int    number of affected rows;
	 */
	public function save(Gorm_Model $model, $force = FALSE)
	{
		try
		{
			return $this->_providers[$this->_default_provider]->save($model, $force);
		}
		catch(Database_Exception $ex)
		{
			throw new Gorm_Exception('Unable to save the model');
		}
	}

	/**
	 * Check if $id is a Model, array, integer, ... and returns an array with the id's
	 *
	 * @param  mixed  $id
	 * @return array
	 */
	protected function _getIds($id = NULL)
	{
		$model_ids = array();

		// Test if $id are more then one id or model
		if(is_array($id) AND ! empty($id))
		{
			foreach($id as $model)
			{
				if($model instanceof Gorm_Model)
				{
					$model_ids[] = $model->getId();
				}
				else
				{
					$model_ids[] = (int) $model;
				}
			}
		}
		elseif($id !== NULL)
		{
			if($id instanceof Gorm_Model)
			{
				$model_ids[] = $id->getId();
			}
			else
			{
				$model_ids[] = (int) $id;
			}
		}

		return $model_ids;
	}

	/**
	 * Convert a Result to models, Results should be handled like an array
	 *
	 * @param  mixed  $results
	 * @return array
	 */
	public function convert_results($results)
	{
		$models = array();
		$class = 'Model_'.ucfirst($this->_model);

		foreach($results as $model_array)
		{
			$model = new $class();
			$models[] = $model->set($model_array, TRUE);
		}

		return $models;
	}

	/**
	 * Magic method __call()
	 *
	 * When calling a method that doesn't exist, the Service class will try
	 * to call the method from the custom service
	 *
	 * @param   string  $name
	 * @return  mixed
	 */
	public function __call($name, $arguments)
	{
		// Check if a custom service exists
		if($this->_custom_service instanceof Service)
		{
			return $this->_custom_service->$name($arguments);
		}
		else
		{
			throw new Gorm_Exception('The method `:name` isn\'t supported in the Service', array(':name' => $name));
		}
	}
}