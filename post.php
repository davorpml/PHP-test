<?php
//Include ClassAction
require_once("ClassAction.php");

//Create instance of class
$ca = new ClassAction();

//Process data from form
$result = $ca->processPostData();

//Set return data
if(isset($result['error'])) $data['error'] = $result['error'];
else $data['success'] = 'Saved';

//Output json data response
echo json_encode($data);
?>