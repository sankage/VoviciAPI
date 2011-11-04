<?php
/**
 * Vovici API
 *
 * a PHP class to interact with the Vovici Web Services API
 *
 * @author				David Briggs
 * @copyright	Copyright (c) 2011 Infosurv, Inc.
 * @link				http://www.infosurv.com
 */
 
/**
 * voviciAPI
 *
 * Create our new class called "voviciAPI"
 */
class voviciAPI {
	private $api_url = 'http://efm.infosurv.vovici.net/ws/projectdata.asmx?wsdl';
	private $username = '';
	private $password = '';
	
	/**
	 * get_api_url()
	 *
	 * Get the API url
	 *
	 * @access		public
	 * @return		string
	 */
	public function get_api_url() {
		return $this->api_url;
	}
	/**
	 * set_api_url()
	 *
	 * Set the API url
	 *
	 * @access		public
	 * @param		string
	 * @return		void
	 */
	public function set_api_url($url) {
		$this->api_url = $url;
	}
	/**
	 * set_username()
	 *
	 * Set the Username
	 *
	 * @access		public
	 * @param		string
	 * @return		void
	 */
	public function set_username($username) {
		$this->username = $username;
	}
	/**
	 * set_password()
	 *
	 * Set the Password
	 *
	 * @access		public
	 * @param		string
	 * @return		void
	 */
	public function set_password($password) {
		$this->password = $password;
	}
	/**
	 * request()
	 *
	 * Request data from the API
	 *
	 * @access		public
	 * @param		string, array
	 * @return		xml
	 */
	public function request($func, $options) {
		ini_set('max_execution_time', 0);
		$soap = new SoapClient($this->api_url, array('trace'=>1,'cache_wsdl'=>WSDL_CACHE_NONE ));
		$params = array('userName' => $this->username, 'password' => $this->password );
		$soap->Login($params);
		
		try {
			$response = $soap->$func($options);
		} catch (Exception $e) {
			/*
			echo 'Caught: ' . $e->getMessage() . '<br><pre>';
			var_dump($e);
			echo '</pre>';
			die;
			*/
			return false;
		}
		
		$r = $func . 'Result';
		
		
		if (!$response) {
			return false;
		} else {
			return $response->$r;//->any;
		}
	}
	/**
	 * __call()
	 *
	 * Request data from the API
	 *
	 * @access		public
	 * @param		string
	 * @param		string, array
	 * @return		xml
	 */
	public function __call($method, $args) {
		return $this->request($method, $args);
	}
	
	/**
	 * XMLToArray()
	 *
	 * Returns an array of the XML
	 *
	 * @access		private
	 * @param		SimpleXMLElement
	 * @return		array
	 */
	private function XMLToArray($xml){
  		if ($xml instanceof SimpleXMLElement){
    		$children = $xml->children();
			$return = null;
		}
  		foreach ($children as $element => $value){ 
    		if ($value instanceof SimpleXMLElement){
      			$values = (array)$value->children();
      
      			if (count($values) > 0){
        			$return[$element] = $this->XMLToArray($value); 
      			} else { 
        			if (!isset($return[$element])) { 
          				$return[$element] = (string)$value; 
        			} else { 
          				if (!is_array($return[$element])) { 
            				$return[$element] = array($return[$element], (string)$value); 
          				} else { 
            				$return[$element][] = (string)$value; 
          				} 
        			} 
      			} 
    		} 
  		}
  		if (is_array($return)) { 
    		return $return; 
  		} else { 
    		return $false; 
  		} 
	}
	
	/**
	 * getCompleteArray()
	 *
	 * Performs the "GetSurveyDataPaged" request and returns the completed data in a nicely formatted array
	 *
	 * @access		public
	 * @param		string
	 * @param		string
	 * @return		array
	 */
	public function getCompleteArray($pid, $criteria = null, $datamap = null, $fields = null) {
		$responseCount = $this->request('GetResponseCount', array('projectId' => $pid, 'completedOnly' => true));
		
		$last_iteration = $responseCount % 1000;
		if ($last_iteration == 0) {
			$last_iteration = 1000;
		}
		$num_of_iterations = ceil($responseCount / 1000);
		
		$prevRecordId = 0;
		$result = array();
		
		//test condition
		//$num_of_iterations = 1;
		
		while ($num_of_iterations > 0) {
			$recordCount = 1000;
			if ($num_of_iterations == 1) {
				$recordCount = $last_iteration;
			}
			$o = array(
				'projectId' 	=> $pid,
				'completedOnly' => true,
				'recordCount' 	=> $recordCount,
				'prevRecordId' 	=> $prevRecordId);
			if ($criteria) {
				$o['filterXml'] = $criteria;
			}
			if ($datamap) {
				$o['dataMapXml'] = $datamap;
			}
			$data = $this->request('GetSurveyDataPaged', $o);
			
			$xml = new SimpleXMLElement($data->any);
			unset($data); // for quicker garbage collection
			$xmlAsArray = $xml->NewDataSet;
			unset($xml);
			foreach ($xmlAsArray->Table1 as $record) {
				$test = $this->XMLToArray($record);
				// if $fields is an array, then we want to return each thing in the array
				if (is_array($fields)) {
					$tempArray = array();
					foreach ($fields as $field) {
						$tempArray[$field] = $test[$field];
					}
					$result[] = $tempArray;
				} elseif ($fields) {
					// if $fields is a single entry, then only return that
					$result[] = $test[$fields];
				} else {
					// if $fields is not set, then return the entire record
					$result[] = $test;
				}
				// set the last record
				$prevRecordId = $test['recordid'];
			}
			unset($xmlAsArray); // for quicker garbage collection
			
			// reduce the number of iterations
			$num_of_iterations--;
		}
		
		return $result;
	}
	
	/**
	 * getCompleteCSV()
	 *
	 * Performs the "GetSurveyDataEx" request and returns the completed data as a comma separated list
	 *
	 * @access		public
	 * @param		string
	 * @param		string
	 * @return		array
	 */
	public function getCompleteCSV($pid, $criteria = null) {
		$data = $this->getCompleteArray($pid, $criteria);
		
		if (!$data){
			return false;
		}
		
		$keys = array_keys($data[0]);
		array_unshift($data, $keys);
		
		$columns = $this->getColumnList($pid);
		
		
		
		$fp = fopen('completes.csv', 'w');
		foreach ($data as $record) {
			fputcsv($fp, $record);
		}
		fclose($fp);
	}
	
	public function getColumnList($pid) {
		$data = $this->request('GetColumnList', array('projectId'=>$pid));
		
		if (!$data){
			return false;
		}
		// parse the XML returned by request()
		$xml = new SimpleXMLElement($data->any);
		foreach ($xml as $field) {
			$result[(string) $field['id']] = (string) $field['type'];
		}
		return $result;
	}
	
	/**
	 * addParticipant()
	 * 
	 * adds a participant to the specified survey
	 * 
	 * @access	public
	 * @param	string
	 * @param	array
	 * @param 	array
	 * @return 	string
	 */
	public function addParticipant($pid, $user, $prepop = null) {
		// to prevent user-error in the naming of keys
		$mappings = array(
			'key1' => 'userkey1', 'Key1' => 'userkey1', 'Key 1' => 'userkey1', 'key 1' => 'userkey1',
			'userKey1' => 'userkey1', 'Userkey1' => 'userkey1', 'UserKey1' => 'userkey1',
			'key2' => 'userkey2', 'Key2' => 'userkey2', 'Key 2' => 'userkey2', 'key 2' => 'userkey2',
			'userKey2' => 'userkey2', 'Userkey2' => 'userkey2', 'UserKey2' => 'userkey2',
			'key3' => 'userkey3', 'Key3' => 'userkey3', 'Key 3' => 'userkey3', 'key 3' => 'userkey3',
			'userKey3' => 'userkey3', 'Userkey3' => 'userkey3', 'UserKey3' => 'userkey3',
			'e-mail' => 'email', 'eMail' => 'email', 'Email' => 'email', 'E-mail' => 'email', 'E-Mail' => 'email', 'e-Mail' => 'email',
			'Culture' => 'culture'
		);
		
		$parameters = array();
		$parameters['projectId'] = $pid;
		foreach ($user as $key => $value) {
			// change the key if its incorrect
			if (array_key_exists($key, $mappings)) {
				$key = $mappings[$key];
			}
			$parameters[$key] = $value;
		}
		
		$recordid = $this->request('AuthorizeParticipantForSurvey', $parameters);
		
		// if the request returned an error
		if (!$recordid) {
			return false;
		}
		
		if ($prepop) {
			// try to add the prepop, if it fails, return false
			if(!$this->addPrepop($pid, $recordid, $prepop)) {
				return false;
			};
		}
		
		return $recordid;
	}
	
	/**
	 * addPrepop()
	 *
	 * adds prepop to the desired participant
	 *
	 * @param string
	 * @param string
	 * @param array
	 */
	public function addPrepop($pid, $rid, $pp) {
		// get the types list
		$types = $this->getColumnList($pid);
		if (!$types) {
			return false;
		}
		
		// @note: the $pp array will need to be setup as array(db_heading => value, ...)
		
		$datastring = '<Rows><Row id="' . $rid . '">';
		foreach ($pp as $ppkey => $ppvalue) {
			$datastring .= '<Field id="' . $ppkey . '" type="' . $types[$ppkey] . '">' . $ppvalue . '</Field>';
		}
		$datastring .= '</Row></Rows>';
		
		$response = $this->request('SetPreloadData', array('projectId' => $pid, 'dataString' => $datastring));
		// if response has a value, then the request failed
		if ($response) {
			return false;
		}
		
		return true;
	}
	
	public function getParticipantData($pid, $status = null) {
		$participantCount = $this->request('GetAuthorizedParticipantCount', array('projectId' => $pid));
		
		$last_iteration = $participantCount % 1000;
		if ($last_iteration == 0) {
			$last_iteration = 1000;
		}
		$num_of_iterations = ceil($participantCount / 1000);
		$startRecordId = 0;

		//test condition
		//$num_of_iterations = 1;

		$records = array();
		while ($num_of_iterations > 0) {
			$recordCount = 1000;
			if ($num_of_iterations == 1) {
				$recordCount = $last_iteration;
			}
			$o = array(
				'projectId' 	=> $pid,
				'recordCount' 	=> $recordCount,
				'surveyStatus'	=> $status ? $status : 'Any',
				'startRecordId'	=> $startRecordId);
			$data = $this->request('GetParticipantDataPaged', $o);
			try {
				$xml = new SimpleXMLElement($data->any);
			} catch (Exception $e) {
				$records[] = array('ERROR' => $e->getMessage(), 'data' => $data->any);
				return $records;
			}
			
			unset($data); // for quicker garbage collection
			
			foreach ($xml->children() as $record) {
				$test = $record->attributes();
				$record = (string) $test['recordid'];
				// store the recordid
				$records[$record] = array(
					'key1' => (string) $test['user_key1'],
					'email' => (string) $test['email'], 
					'status' => (int) $test['invite_status'], 
					'completed' => ($test['completed'] != "") ? 1 : 0,
					'culture' => (string) $test['culture']
				);
				
				$startRecordId = $record;
			}
			unset($xml); // for quicker garbage collection
			
			$num_of_iterations--;
		}
		
		return $records;
	}
	
	/**
	 * getPreloadData()
	 *
	 * Performs the "GetSurveyDataPaged" request and returns the completed data in a nicely formatted array
	 *
	 * @access		public
	 * @param		string
	 * @param		string
	 * @return		array
	 */
	public function getPreloadData($pid, $fields = null) {
		$records = $this->getParticipantData($pid);

		// Take the arrays and 
		foreach ($records as $recordid => $record) {
			$data = $this->request('GetPreloadData', array('projectId' => $pid, 'recordId' => $recordid));
			$xml = new SimpleXMLElement($data->any);
			if (is_array($fields)) {
				foreach ($fields as $field) {
					$result[$field] = (string) $xml->Field[$field];
				}
			} else {
				$result[$fields][] = (string) $xml->Field[$fields];
			}
		}
		return $result;
	}
	
	public function getCampaignStatus($pid, $rid) {
		$data = $this->request('GetCampaignHistory', array('projectId' => $pid, 'participantId' => $rid));
		$xml = new SimpleXMLElement($data->any);
		// possible undeliverable error codes
		$undes = array(30,31,32,33,34,40,41,42,43,44,45,46);
		// possible unsubscribe error codes
		$unsubs = array(90);
		foreach ($xml->children() as $child) {
			$atts = $child->attributes();
			if (in_array($atts['status'], $undes)) {
				return 50;
			}
			if (in_array($atts['status'], $unsubs)) {
				return 51;
			}
		}
		return 1;
	}
}
?>