<?php
//Include ClassAction
require_once("ClassAction.php");

//Create instance of class
$ca = new ClassAction();

//Proccess data from form if submited
if (isset($_GET['get_type'])) $result = $ca->processGetData($_GET['get_type']);

//Redirect to previous page
header("Location:action.php");
?>