<?php
//Include class DatabasePerformance
require_once("db_config.php");

class ClassAction{
	private $conn;
	
	/**
	 * constructor
	*/
	public function __construct(){
		$dbObj = new DatabasePerformance();
		$db = $dbObj->dbConnection();
		$this->conn = $db;
	}//~!
	
	/**
	 *get country iso based on current ip address
	 *geoplugin used detecting country
	 *if local processing iso is set to Serbia RS
	 *
	 *@access private
	 *@throws Exceptions
	 *@return string|array
	*/
	private function getIsoCountryCode(){
		//Init variables
		$result = null;
		$ip = null;
		try{
			//Set ip address
			if (isset($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			if (isset($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR'];
			
			if (empty($ip)) throw new Exception("Invalid IP");
			
			
			if ($ip != '::1'){ 
				//Validate ip with regular expression
				$validate_ip = preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $ip);
				if (!$validate_ip) throw new Exception("Invalid IP format");
				
				//Get iso with geoplugin
				$data = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));
				if(!$data || $data->geoplugin_countryCode == null) throw new Exception("Unknown Country");
			
				$result = $data->geoplugin_countryCode;
			}else{
				//If local server testing set iso to Serbia RS
				$result = 'RS';
			}
		}catch(Exception $e){
			$result['error'] = $e->getMessage();
		}
		
		return $result;
	}//~!
	
	/**
	 *processing form data, post request
	 *insert update db event records
	 *
	 *@access public
	 *@throws Exceptions
	 *@return array
	*/
	public function processPostData(){
		$result = null;
		try{
			//Check event
			if (!isset($_POST['event'])) throw new Exception("Invalid Post Request");
			$event = $_POST['event'];
			
			//Check if set country; if not set then get iso with getIsoCountryCode function
			if (isset($_POST['country'])){ 
				$country_code = $_POST['country'];
			}else{
				$country_code = $this->getIsoCountryCode();
				if (isset($country_code['error'])) throw new Exception($country_code['error']);
			}
			
			//get country id
			$country_id = $this->getCountryID($country_code);
			if (isset($country_id['error'])) throw new Exception($country_id['error']);
			//current date
			$date = date('Y-m-d');
			
			//check of exists event record for today
			$qry_exists = $this->conn->prepare("SELECT * FROM event_counters WHERE country_id=:country_id AND day=:day AND event=:event");
			$qry_exists->execute(array(":country_id"=>$country_id, ":day"=>$date, ":event"=>$event));
			
			//Init counter
			$counter = 1;
			if ($qry_exists->rowCount() == 1){
				$exist_event = $qry_exists->fetch(PDO::FETCH_ASSOC);
				//increment counter
				$counter = (int)$exist_event['counter'];
				$counter++;
				$modified = date('Y-m-d H:i:s');
				
				//Update event record
				$qry_update = $this->conn->prepare("UPDATE event_counters SET counter=:counter, modified=:modified WHERE id=:event_counter_id");
				$qry_update->bindparam(":counter", $counter);
				$qry_update->bindparam(":event_counter_id", $exist_event['id']);
				$qry_update->bindparam(":modified", $modified);
				$qry_update->execute();
			}else{
				$qry_insert = $this->conn->prepare("INSERT INTO event_counters(country_id, day, counter, event) VALUES (:country_id, :day, :counter, :event)");
				
				//set params
				$qry_insert->bindparam(":country_id", $country_id);
				$qry_insert->bindparam(":day", $date);
				$qry_insert->bindparam(":counter", $counter);
				$qry_insert->bindparam(":event", $event);
				
				//Insert new event record
				try{
				$qry_insert->execute();
				$result['success'] = $qry_insert;
				}catch(PDOException $ex){
					$result['result'] = $ex->getMessage(); 
				}
			}
			
		}catch(Exception $e){
			$result['error'] = $e->getMessage();
		}
		
		return $result;
	}//~!
	
	/**
	 *processing form data, get requests
	 *read from db event records
	 *3 types of returning data: csv, cml, json
	 *return data for last 7 days sum by event and country for top 5 countries of all times
	 *
	 *@param string
	 *@access public
	 *@throws Exceptions
	 *@return array
	*/
	public function processGetData($get_type){
		$result = null;
		
		try{
			//Check param
			if (empty($get_type)) throw new Exception("Invalid Request");
			if (!in_array($get_type, array('csv', 'xml', 'json'))) throw new Exception("Invalid Request");
			
			switch($get_type){
				case 'csv':
					$result = $this->generateCSV(); //Generate csv
				break;
				case 'xml':
					$result = $this->generateXML(); //Generate XML
				break;
				case 'json':
					$result = $this->generateJSON(); //Generate 
				break;
			}
			
		}catch(Exception $e){
			$result['error'] = $e->getMessage();
		}
		return $result;
	}//~!
	
	/**
	 *get country id 
	 *
	 *@param string
	 *@access private
	 *@throws exception
	 *@return string|array
	*/
	private function getCountryID($iso){
		try{
			//Prepare and execute query
			$query = $this->conn->prepare("SELECT id FROM countries WHERE code=:code");
			$query->execute(array(":code" => $iso));
			$country = $query->fetch(PDO::FETCH_ASSOC);
			if ($query->rowCount() != 1) throw new Exception("No Country");
			
			//Set country id
			$result = $country['id'];
		}catch(Exception $e){
			$result['error'] = $e->getMessage();
		}
		return $result;
	}//~!
	
	/**
	 *return data from database
	 *sum of each event over the last 7 days by country for the top 5 countries of all times
	 *
	 *@access private
	 *@throws exception
	 *@return array
	*/
	private function lastWeekQuery(){
		$result = null;
		try{
			$query = $this->conn->prepare("SELECT * FROM(
											SELECT
											c.name as country,
											ec.event as event,
											sum(ec.counter) as counter
											FROM event_counters ec
												JOIN countries c ON ec.country_id = c.id 
											WHERE ec.day BETWEEN DATE_FORMAT((ec.day-7), \"%Y-%m%-%d\") AND ec.day
												AND ec.country_id in (
													SELECT country_id FROM(
											SELECT
												ecc.country_id,
													SUM(ecc.counter) as suma
												FROM
													 event_counters ecc
													GROUP BY
													ecc.country_id
													ORDER BY
														suma DESC
													LIMIT 5)a
												)
											GROUP BY ec.country_id, ec.event) boss
											ORDER BY event, counter DESC");
			$query->execute();
			$result = $query->fetchAll(PDO::FETCH_ASSOC);
			
		}catch(Exception $e){
			$result['error'] = $e->getMessage();
		}
		return $result;	
	}//~!
	
	/**
	 *generate xml file based on data returned from lastWeekQuery function
	 *
	 *@access private
	*/	
	private function generateXML(){
		$fileName = "result_".date('YmdHis').".xml";
		
		$xml = new DOMDocument('1.0', 'UTF-8');
		$events = $xml->createElement('events');
		
		//Get data from db
		$db_results = $this->lastWeekQuery();
		
		foreach($db_results as $record){
			$country_name = $record['country'];
			$event_type = $record['event'];
			$count_number = $record['counter'];
			
			$event_record = $xml->createElement('event_record');
			
			//Set country
			$country = $xml->createElement('country', $country_name);
			$event_record->appendChild($country);
			
			//Set event
			$event = $xml->createElement('event', $event_type);
			$event_record->appendChild($event);
			
			//Set counter
			$counter = $xml->createElement('counter', $count_number);
			$event_record->appendChild($counter);
			
			$events->appendChild($event_record);
		}
		
		//Output file
		header('Content-Disposition: attachment;filename='.$fileName);
		header('Content-Type: text/xml');
		$xml->appendChild($events);
		echo $xml->save($fileName);
		exit();
	}//~!

	/**
	 *generate json data based on data returned from lastWeekQuery function
	 *
	 *@access private
	*/	
	private function generateJSON(){
		//Get data from db
		$db_results = $this->lastWeekQuery();
		
		//Print data
		echo json_encode($db_results);
		exit();
	}//~!

	/**
	 *generate csv file based on data returned from lastWeekQuery function
	 *
	 *@access private
	*/	
	private function generateCSV(){
		//Get events data
		$db_results = $this->lastWeekQuery();
		
		if (!empty($db_results)){
			$delimiter = ",";
			$fileName = "result_".date('YmdHis').".csv";
			//var_dump($fileName); exit();
			
			//Create file pointer
			$fo = fopen('php://memory', 'w');
			
			//Set column headers
			$fields = array('Country', 'Action', 'Counter');
			fputcsv($fo, $fields, $delimiter);
			
			//Loop through result array, format data for csv and write to file pointer
			foreach ($db_results as $row){
				$rowArray = array($row['country'], $row['event'], $row['counter']);
				fputcsv($fo, $rowArray, $delimiter);
			}
			
			//Move back to begining of file
			fseek($fo, 0);
			
			//Set headers to download csv file
			header('Content-Type: text/csv');
			header("Content-disposition: attachment; filename=".$fileName);
			
			//Output remaining data
			fpassthru($fo);
		}
		exit();
	}//~!
	
	/**
	 *get country list
	 *
	 *@access public
	 *@return array
	*/
	public function getCountryList(){
		$query = $this->conn->prepare("SELECT code, name FROM countries");
		$query->execute();
		return $query->fetchAll(PDO::FETCH_ASSOC);
	}//~!
}
?>