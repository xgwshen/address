<?php 
header("content-type:text/html;charset=utf-8");

require './config.php';
require './Model.class.php';

//接收查询条件
$upid = empty($_GET['upid'])?0:$_GET['upid'];

$model = new Model('lamp_address');
$data = $model->where('upid='.$upid)->select();

echo json_encode($data);



