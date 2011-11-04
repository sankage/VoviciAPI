<?php
ini_set ('display_errors',1);
error_reporting (E_ALL & ~E_NOTICE);
$mtime = microtime(); 
$mtime = explode(" ",$mtime); 
$mtime = $mtime[1] + $mtime[0]; 
$starttime = $mtime;


include('vovici.api.class.php');

$vovici = new voviciAPI();

//$id = '954486179'; // BN1 - 2010Q4
//$id = '1774397173'; // TEST


/* This is a test of loopage * /
ini_set('max_execution_time',0);
$respondents = $vovici->getParticipantData($id);
foreach ($respondents as $key => $data) {
	$respondents[$key]['statusnew'] = $vovici->getCampaignStatus($id, $key);
}
echo '<pre>';
print_r($respondents);
echo '</pre>';
/* End Loopage test */

/* Use the following code to mass change an answer choice */
$id = '119536641'; // FORBA
$criteria = '<CriteriaCollection><Criterion leftparen="0" heading="Q48_1" expression="=" value="72" rightparen="0" rule="AND" /></CriteriaCollection>';
$values = $vovici->getCompleteArray($id, $criteria, null, 'recordid');
$datastring = "<Rows>";
foreach ($values as $recordid) {
	$datastring .= '<Row id="' . $recordid . '"><Field id="Q46_1">Small Smiles Family Dentistry of Muncie</Field></Row>';
}
$datastring .= '</Rows>';
$vovici->request('ChangeMultipleResponses', array('projectId' => $id, 'dataString' => $datastring));
/* End Mass-Change */

/*
echo '<pre>';
//print_r($vovici->getColumnList($id));
//print_r($call->request('GetDataMap', array('projectId' => $id)));
//print_r($vovici->getPreloadData($id, array(9)));
/*
$prepop = array('Q5' => 1, 'Q11' => 3);
$user = array('Email' => 'david.briggs@infosurv.com', 'Key 1' => '1112223333');
print_r($vovici->addParticipant($id, $user, $prepop));
* /
//print_r($vovici->getParticipantData($id));
print_r($vovici->getCampaignStatus($id, '634232022901336318'));//undeliverable
echo '</pre>';
*/
//$call->getCompleteCSV($id);
//$call->getCompleteCSV($id);

$mtime = microtime(); 
$mtime = explode(" ",$mtime); 
$mtime = $mtime[1] + $mtime[0]; 
$endtime = $mtime; 
$totaltime = ($endtime - $starttime); 
echo "This page was created in ".$totaltime." seconds";

?>