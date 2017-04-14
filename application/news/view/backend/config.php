<?php

return array (
  'module' => 'news',
  'menu' => 
  array (
    0 => 'add',
    1 => 'forbid',
    2 => 'resume',
    3 => 'delete',
    4 => 'recyclebin',
    5 => 'saveorder',
  ),
  'create_config' => true,
  'controller' => 'Backend',
  'title' => '新闻后台',
  'form' => 
  array (
    1 => 
    array (
      'title' => 'ID',
      'name' => 'id',
      'type' => 'checkbox',
      'option' => '',
      'default' => '',
      'search_type' => 'text',
      'validate' => 
      array (
        'datatype' => '',
        'nullmsg' => '',
        'errormsg' => '',
      ),
    ),
    2 => 
    array (
      'title' => '分类ID',
      'name' => 'cid',
      'type' => 'number',
      'option' => '',
      'default' => '',
      'search_type' => 'select',
      'validate' => 
      array (
        'datatype' => '',
        'nullmsg' => '',
        'errormsg' => '',
      ),
    ),
    3 => 
    array (
      'title' => '标题',
      'name' => 'title',
      'type' => 'text',
      'option' => '',
      'default' => '',
      'search' => '1',
      'search_type' => 'text',
      'require' => '1',
      'validate' => 
      array (
        'datatype' => '*',
        'nullmsg' => '',
        'errormsg' => '',
      ),
    ),
    4 => 
    array (
      'title' => '内容',
      'name' => 'content',
      'type' => 'text',
      'option' => '',
      'default' => '',
      'search' => '1',
      'search_type' => 'text',
      'require' => '1',
      'validate' => 
      array (
        'datatype' => '*',
        'nullmsg' => '',
        'errormsg' => '',
      ),
    ),
    5 => 
    array (
      'title' => '排序',
      'name' => 'sort',
      'type' => 'number',
      'option' => '',
      'default' => '',
      'search_type' => 'text',
      'validate' => 
      array (
        'datatype' => '',
        'nullmsg' => '',
        'errormsg' => '',
      ),
    ),
    6 => 
    array (
      'title' => '状态',
      'name' => 'state',
      'type' => 'number',
      'option' => '',
      'default' => '',
      'search_type' => 'text',
      'validate' => 
      array (
        'datatype' => '',
        'nullmsg' => '',
        'errormsg' => '',
      ),
    ),
    7 => 
    array (
      'title' => '创建时间',
      'name' => 'create_time',
      'type' => 'date',
      'option' => '',
      'default' => '',
      'sort' => '1',
      'search' => '1',
      'search_type' => 'date',
      'validate' => 
      array (
        'datatype' => '',
        'nullmsg' => '',
        'errormsg' => '',
      ),
    ),
    8 => 
    array (
      'title' => '修改时间',
      'name' => 'update_time',
      'type' => 'date',
      'option' => '',
      'default' => '',
      'sort' => '1',
      'search' => '1',
      'search_type' => 'date',
      'validate' => 
      array (
        'datatype' => '',
        'nullmsg' => '',
        'errormsg' => '',
      ),
    ),
    9 => 
    array (
      'title' => '删除时间',
      'name' => 'delete_time',
      'type' => 'date',
      'option' => '',
      'default' => '',
      'search_type' => 'text',
      'validate' => 
      array (
        'datatype' => '',
        'nullmsg' => '',
        'errormsg' => '',
      ),
    ),
    10 => 
    array (
      'title' => '管理员ID',
      'name' => 'admin_id',
      'type' => 'text',
      'option' => '',
      'default' => '',
      'search_type' => 'text',
      'validate' => 
      array (
        'datatype' => '',
        'nullmsg' => '',
        'errormsg' => '',
      ),
    ),
  ),
  'model' => '1',
  'validate' => '1',
);
