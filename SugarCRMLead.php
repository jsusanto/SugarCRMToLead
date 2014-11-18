<?php
/*
    *@Author: Jeffry Susanto
	*June 1st 2014
	*SugarCRM to Lead class
	*October 9th 2014 : Get lead by custom fields
*/
define("SUGARCRM_URL", "http://[DOMAIN]/service/v4/rest.php");
define("SUGARCRM_USERNAME", "USERNAME");
define("SUGARCRM_PASSWD", "PASSWORD");
	
class SugarCRMLead {
	private $firstname;
	private $lastname;
	private $email;
	private $phonenumber;
	private $campaign_id;
	private $description;

    // Default constructor
	function __construct($firstname="", $lastname="", $email="", $phonenumber="", $campaign_id="", $description="", $opportunity_amount=0, $intendedcourse_c="") {
		$this->firstname = trim($firstname);
		$this->lastname = trim($lastname);
        $this->email = trim($email);
        $this->phonenumber = trim(str_replace(" ","",$phonenumber));
        $this->campaign_id = $campaign_id;		
		$this->description = $description;		
		$this->opportunity_amount = $opportunity_amount;
		$this->intendedcourse_c = $intendedcourse_c;
	}
	
	function getUserSessionID(){
		//login ---------------------------------
		/*
		Method [  public method login ] {
			Parameters [3] {
				Parameter #0 [  $user_auth ]
				Parameter #1 [  $application ]
				Parameter #2 [  $name_value_list = Array ]
			}
		}
		*/
		$login_parameters = array(
			 "user_auth"=>array(
				  "user_name"=>SUGARCRM_USERNAME,
				  "password"=>md5(SUGARCRM_PASSWD),
				  "version"=>"1"
			 ),
			 "application_name"=>"RestWebToLead",
			 "name_value_list"=>array(),
		);

		$login_result = self::call("login", $login_parameters, $url);

		//get session id
		$session_id = $login_result->id;
		
		return $session_id;
	}
	
	//function to make cURL request
    function call($method, $parameters)
    {
        ob_start();
        $curl_request = curl_init();

        curl_setopt($curl_request, CURLOPT_URL, SUGARCRM_URL);
        curl_setopt($curl_request, CURLOPT_POST, 1);
        curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl_request, CURLOPT_HEADER, 1);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

        $jsonEncodedData = json_encode($parameters);

        $post = array(
             "method" => $method,
             "input_type" => "JSON",
             "response_type" => "JSON",
             "rest_data" => $jsonEncodedData
        );

        curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post);
        $result = curl_exec($curl_request);
        curl_close($curl_request);

        $result = explode("\r\n\r\n", $result, 2);
        $response = json_decode($result[1]);
        ob_end_flush();

        return $response;
    }
	
	function createLead($session_id){
		//Jeffry Susanto, October 10th 2014 - Check whether lead is exist or not
		$whereClause = " TRIM(leads.first_name) = '". $this->firstname. "' AND TRIM(leads.last_name) = '". $this->lastname. "' ";
		$lead_result = self::getLeadByFields($session_id, $whereClause);
		$found = false;
		if(count($lead_result->entry_list) > 0){
			for($i=0; $i<count($lead_result->entry_list); $i++){
				$_email = strtolower(trim($lead_result->entry_list[$i]->name_value_list->email1->value));
				$_phone = strtolower(trim(str_replace(" ","",$lead_result->entry_list[$i]->name_value_list->phone_mobile->value)));
				if( $_email == strtolower($this->email) && $_phone == strtolower($this->phonenumber) ){
					$found = true;
					break;
				}
			}
		}
		
		if(!$found){
			$set_entry_params = array(
								array('name' => 'first_name', 'value' => $this->firstname),
								array('name' => 'last_name', 'value' => $this->lastname),
								array('name' => 'lead_source', 'value' => 'Other'),							
								array('name' => 'phone_mobile', 'value' => $this->phonenumber),
								array('name' => 'opportunity_amount', 'value' => number_format($this->opportunity_amount, 2, '.', '')),
								array('name' => 'phone_home', 'value' => ''),
								array('name' => 'email1', 'value' => $this->email),
								array('name' => 'lead_source', 'value' => 'Other'),
								array('name' => 'preferred_accred_date_c', 'value' => ''), 
								array('name' => 'preferred_accred_date_c_month', 'value' => date('d')),
								array('name' => 'preferred_accred_date_c_day', 'value' => date('m')),
								array('name' => 'preferred_accred_date_c_year', 'value' => date('Y')),
								array('name' => 'study_method_c', 'value' => ''),
								array('name' => 'study_goals_c', 'value' => ''),
								array('name' => 'primary_state_c', 'value' => ''),
								array('name' => 'intendedcourse_c', 'value' => $this->intendedcourse_c),
								array('name' => 'description', 'value' => $this->description),
								array('name' => 'campaign_id', 'value' => $this->campaign_id),
								array('name' => 'assigned_user_id', 'value' => '5431cae4-d89d-9019-6e97-4807e73f1a35'),
								array('name' => 'team_id', 'value' => '1'),
								array('name' => 'req_id', 'value' => 'last_name;lead_source;preferred_accred_date_c;study_method_c;study_goals_c;intendedcourse_c;primary_state_c;')
							);
		
			$result_parameters = array(
								"session_id" => $session_id,
								"module_name" => "Leads",
								"name_value_list" => $set_entry_params
							 );
									
			$result = self::call("set_entry", $result_parameters);
			return $result;
		}else{
			return true;
		}
	}
	
	//October 9th 2014 : Get lead by custom fields
	function getLeadByFields($session_id, $whereClause=""){
		//get list of records --------------------------------
		$get_entry_list_parameters = array(

			 //session id
			 'session' => $session_id,

			 //The name of the module from which to retrieve records
			 'module_name' => 'Leads',

			 //The SQL WHERE clause without the word "where".
			 'query' => $whereClause,

			 //The SQL ORDER BY clause without the phrase "order by".
			 'order_by' => "",

			 //The record offset from which to start.
			 'offset' => '0',

			 //Optional. A list of fields to include in the results.
			 'select_fields' => array(
				  'id',
				  'name',
				  'title',
				  'phone_mobile',
				  'email1',
			 ),

			 /*
			 A list of link names and the fields to be returned for each link name.
			 Example: 'link_name_to_fields_array' => array(array('name' => 'email_addresses', 'value' => array('id', 'email_address', 'opt_out', 'primary_address')))
			 */
			 'link_name_to_fields_array' => array(
			 ),

			 //The maximum number of results to return.
			 'max_results' => '',

			 //To exclude deleted records
			 'deleted' => '0',

			 //If only records marked as favorites should be returned.
			 'Favorites' => false,
		);
		
		$get_entry_list_result = self::call('get_entry_list', $get_entry_list_parameters);
		
		return $get_entry_list_result;
	}
}
?>