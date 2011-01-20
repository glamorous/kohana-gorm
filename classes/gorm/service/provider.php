<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Gorm_Service is the class all services must extend. It handles
 * database accessing and other services.
 *
 * @package Gorm
 */
abstract class Gorm_Service_Provider
{
	/**
	 * @var  object  Model shortname
	 */
	protected $_model;

	/*
	 * The constructor sets the model for all interactions with this instance
	 *
	 * @param  string $model
	 */
	public function __construct($model)
	{
		$model_class = 'Model_'.ucfirst($model);
		if(class_exists($model_class))
		{
			$this->_model = new $model_class;
		}
		else
		{
			throw new Gorm_Exception('The model you have provided doesn\'t exist');
		}
	}

	/*
	 * Select one or more models trough the service
	 *
	 * @param  mixed  $id
	 * @return  object  Model or Database_Query_Builder
	 */
	public abstract function select($id = NULL);

	/*
	 * Select one or more models trough the service
	 *
	 * @param  mixed  $id
	 * @return  boolean
	 */
	public abstract function delete($id = NULL);

	/*
	 * Save a model
	 *
	 *     Note: This method is necessary for $model->save() and should only be used in this context
	 *
	 * @param   object $model
	 * @return  mixed FALSE or $id;
	 */
	public abstract function save(Gorm_Model $model);
}