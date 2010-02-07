<?php
/**
 * KeyValueLooseSchemaBehavior
 * 
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * Copyright (c) 2010 Takayuki Miwa <i@tkyk.name>
 */

class KeyValueLooseSchemaBehavior extends ModelBehavior
{
  var $_schemalessType = 'schemaless';

  var $_defaultSettings = array('schemalessField' => '_data');

  function setup(&$model, $settings=array())
  {
    $settings = array_merge($this->_defaultSettings,
			    $settings);

    $this->settings[$model->alias] = $settings;
  }

  function getSchemalessField(&$model)
  {
    if($model->getColumnType($this->settings[$model->alias]['schemalessField'])
       == $this->_schemalessType) {
      return $this->settings[$model->alias]['schemalessField'];
    }
    return null;
  }

  function beforeSave(&$model, $options)
  {
    if(!($schemalessField = $model->getSchemalessField())) {
      return true;
    }

    $keys = array_diff(array_keys($model->data[$model->alias]),
		       array_keys($model->schema()));
    if(!empty($model->whitelist)) {
      if(!in_array($schemalessField, $model->whitelist)) {
	$model->whitelist[] = $schemalessField;
      }
      $keys = array_intersect($keys, $model->whitelist);
    }

    $schemaless = array();
    foreach($keys as $key) {
      $schemaless[$key] = $model->data[$model->alias][$key];
      unset($model->data[$model->alias][$key]);
    }
    $model->data[$model->alias][$schemalessField] = $schemaless;
    return true;
  }

  function getSchemalessData(&$model, $data)
  {
    if(($schemalessField = $model->getSchemalessField()) &&
       isset($data[$schemalessField])) {
      $schemaless = $data[$schemalessField];
      unset($data[$schemalessField]);
      return $data + $schemaless;
    }
    return $data;
  }
}
