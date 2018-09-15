<?php
//Include ClassAction
require_once("ClassAction.php");

//Create instance of class
$ca = new ClassAction();

//Process data from form
$result = $ca->processPostData();

//Redirect to previous page
header("Location:action.php");
?>