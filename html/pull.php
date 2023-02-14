<?php
$xmlfile = file_get_contents('../currency.xml');
$xml = simplexml_load_string($xmlfile);
$result = $xml -> currency;
$result = $result[0];
$data = array();
foreach($result as $key=>$val){
	array_push($data,(string)$key);
}
echo json_encode($data);
?>