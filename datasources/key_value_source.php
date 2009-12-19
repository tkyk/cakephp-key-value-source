<?php
/**
 * Abstract DataSource class for key-value stores.
 * 
 * You should implement:
 * - connect ()
 * - get (&$model, $id, $query)
 * - set (&$model, $id, $data)
 * - del (&$model, $id)
 * - count (&$model, $id, $query)
 * 
 * You can also implement/override:
 * - close ()
 * - checkId ($id)
 * 
 * Notice:
 * You cannot use the value false as a regular value,
 * because false means 'not-found' or 'failed' in this class.
 * 
 */

class KeyValueSource extends DataSource
{
  /**
   * If you want to support these features,
   * turn them on explicitly.
   * 
   * @var array
   */
  var $_features = array('listSources' => false,
			 'describe'    => false);

  /**
   * Constructor.
   *
   * @param array $config
   */
  function __construct($config=array())
  {
    $this->debug     = Configure::read('debug') > 0;
    $this->fullDebug = Configure::read('debug') > 1;

    parent::__construct($config);
    $this->connect();
  }

  /**
   * [Abstract functions to-be-overridden in subclasses.]
   * 
   * Configurations passed from the database.php are
   * in the $this->config.
   * 
   * If an error occurs while connecting, call $this->cakeError().
   * Returned values are discarded.
   * 
   * @abstract
   */
  function connect(){}

  /**
   * [Abstract functions to-be-overridden in subclasses.]
   * 
   * Retrieves data associated with ID $id.
   * 
   * @abstract
   * @param object  Model
   * @param mixed   id
   * @param array   query-array passed from the Model::find
   * @return mixed or false
   */
  function get(&$model, $id, $query){}

  /**
   * [Abstract functions to-be-overridden in subclasses.]
   * 
   * Stores $data in whatever you want.
   * 
   * @abstract
   * @param object  Model
   * @param mixed   id
   * @param array   data-array passed from the Model::save
   * @return mixed or false
   */
  function set(&$model, $id, $data){}

  /**
   * [Abstract functions to-be-overridden in subclasses.]
   * 
   * Removes data associated with ID $id.
   * 
   * @abstract
   * @param object  Model
   * @param mixed   id
   * @return boolean
   */
  function del(&$model, $id){}

  /**
   * [Abstract functions to-be-overridden in subclasses.]
   *
   * Returns the number of items associated with ID $id.
   *
   * @param object $model
   * @param mixed  $id
   * @param array  $query
   * @return integer
   */
  function count(&$model, $id, $query){}

  /**
   * Checks if the $id is valid in this storage.
   * 
   * In default, integer and alpha-numeric string are allowed.
   * You can override this behavior.
   * 
   * @param mixed $id
   * @return boolean
   */
  function checkId($id)
  {
    return is_int($id) || (is_string($id) && preg_match('/^[0-9a-z]+$/i', $id));
  }

  /**
   * This method will be called in the __destruct
   * if and only if $connected is used.
   * 
   */
  function close(){}

  /**
   * @override
   */
  function isInterfaceSupported($interface)
  {
    switch(true) {
    case isset($this->_features[$interface]):
      return $this->_features[$interface];
    default:
      return parent::isInterfaceSupported($interface); 
    }
  }

  function create(&$model, $fields=null, $values=null)
  {
    $data = ($values != null) ? array_combine($fields, $values) : $fields;

    if(isset($data[$model->primaryKey])
       && $this->checkId($data[$model->primaryKey])
       && $this->set($model, $data[$model->primaryKey], $data)) {
      return true;
    } else {
      $model->onError();
      return false;
    }
  }

  function update(&$model, $fields = null, $values = null)
  {
    $data = ($values != null) ? array_combine($fields, $values) : $fields;

    if($this->checkId($model->id) &&
       $this->set($model, $model->id, $data)) {
      return true;
    } else {
      $model->onError();
      return false;
    }
  }

  function read(&$model, $query = array(), $recursive = null)
  {
    $query = am(array('conditions' => array(),
		      'fields' => array()), $query);

    $idRef = $model->alias .".". $model->primaryKey;

    if(empty($query['conditions'][$idRef])) {
      trigger_error(__CLASS__ ."::read(): primary key is not specified.",
		    E_USER_ERROR);
      $model->onError();
      return false;
    }

    $id = $query['conditions'][$idRef];

    if(!$this->checkId($id)) {
      return false;
    }

    if (!empty($query['fields']['count'])) {
      return a(a(aa('count', $this->count($model, $id, $query))));
    }

    if($data = $this->get($model, $id, $query)) {
      return a(aa($model->alias, $data));
    }
    return null;
  }

  function calculate()
  {
    return array('count' => true);
  }

  function delete(&$model, $conditions=null)
  {
    return $this->del($model, $model->id);
  }

}
