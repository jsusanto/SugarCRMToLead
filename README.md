SugarCRMToLead
==============

Class to add Lead when using SugarCRM

How to use it?

$objSugarCRMLead = new SugarCRMLead($firstname, $lastname, $emailaddress, $phonenumber, $campaign_id, $description, $opportunity_amount, $intendedcourse_c);
$session_id = $objSugarCRMLead->getUserSessionID();		
$objSugarCRMLead->createLead($session_id);