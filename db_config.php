<?php
class DatabasePerformance{
	private $host = "localhost";
	private $db_name = "db_performance";
	private $username = "root";
	private $password = "";
	public $connect;
	
	/**
	 *connecting to database
	 *
	 *@throws PDOException
	*/
	public function dbConnection(){
		$this->connect = null;
		
		try{
			$this->connect = new PDO("mysql:host=".$this->host.";dbname=".$this->db_name, $this->username, $this->password);
			$this->connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}catch(PDOException $exception){
			echo "DB Connection error: ".$exception->getMessage();
		}
		
		return $this->connect;
	}//~!
}
?>