<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Service_Provider_Database is the default class for all services.
 * It handles database accessing.
 *
 * @package Gorm
 */
class Service_Provider_Database extends Service_Provider
{
	/*
	 * The constructor sets the model for all interactions with this instance
	 *
	 * @param string $model
	 */
	public function __construct($model)
	{
		parent::__construct($model);
	}

	/*
	 * Select one or more models trough the service
	 *
	 * @param  array  $id
	 * @return Gorm_Model When $id is given and the model(s) is found
	 * @return FALSE When $id is given but the model(s) isn't found
	 * @return Database_Query_Builder When no $id is given
	 */
	public function select($id = array())
	{
		$table = $this->_model->getTable();
		$column_aliases = $this->_getColumns($table);
		$builder = DB::select_array($column_aliases)->from($table);

		if(empty($id))
		{
			return $builder;
		}
		else
		{
			$db_result = (count($id) == 1) ? $builder->where('id', '=', $id[0])->execute() : $builder->where('id', 'IN', implode(',',$id))->execute();
			return $db_result->as_array();
		}
	}

	/*
	 * Select one or more models trough the service
	 *
	 * @param  array $id
	 * @return int   Number of affected rows
	 * @return Database_Query_Builder
	 */
	public function delete($id = array())
	{
		$table = $this->_model->getTable();
		$builder = DB::delete($table);
		if(empty($id))
		{
			return $builder;
		}
		elseif(count($id) == 1)
		{
			return $builder->where('id', '=', $id[0])->execute();
		}
		else
		{
			return $builder->where('id', 'IN', implode(',',$id))->execute();
		}
	}

	/*
	 * Save the model trough the service
	 *
	 * @param   object $model
	 * @return  mixed FALSE or $id;
	 */
	public function save(Gorm_Model $model)
	{
		$table = $this->_model->getTable();
		$id = $model->getId();
		// Check if it's a new record (no id provided)
		if(empty($id))
		{
			$model_data = $model->as_array();
			list($insert_id) = DB::insert($table, array_keys($model_data))->values(array_values($model_data))->execute();
			return $insert_id;
		}
		else
		{
			return DB::update($table)->set($model->as_array())->where('id', '=', $model->getId())->execute();
		}
	}

	/*
	 * Create array with for each column an array(column, alias)
	 *
	 * @param   string  $table
	 * @return  array
	 */
	protected function _getColumns($table)
	{
		$column_aliases = array();

		foreach($this->_model->getFields() as $field)
		{
			$column_aliases[] = $field;
		}

		return $column_aliases;
	}
}