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
	 * Constructor.
	 *
	 * @param  mixed  $model
	 */
	public function __construct($model = NULL)
	{
		// Set model
		$class = ($model !== NULL) ? 'Model_'.ucfirst($model) : 'Model_'.strtolower(substr(get_class($this), 8));

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
		if($this->_default_provider === NULL)
		{
			$this->_default_provider = 'database';
		}

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

	/*
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
			if($convert === TRUE)
			{
				$models = $this->convert_results($results);

				return (count($models) == 1) ? $models[0] : $models;
			}
			else
			{
				return (count($results) == 1) ? $results[0] : $results;
			}
		}
	}

	/*
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

	/*
	 * Save the model trough the service
	 *
	 * @param  mixed  $model
	 * @return int    insert id for creating a model
	 * @return int    number of affected rows;
	 */
	public function save(Gorm_Model $model)
	{
		return $this->_providers[$this->_default_provider]->save($model);
	}

	/*
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

	/*
	 * Convert a Result to models, Results should be handled like an array
	 *
	 * @param  mixed  $results
	 * @return array
	 */
	public function convert_results($results)
	{
		$models = array();
		$class = 'Model_'.ucfirst($this->_model);
		$model = new $class();

		foreach($results as $model_array)
		{
			$models[] = $model->set($model_array, TRUE);
		}

		return $models;
	}
}