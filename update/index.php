<?php
//update currency.xml of post
function post_xml($curr, $xml, $ts){
	$result = $xml->xpath("//*[starts-with(local-name(), '".$curr."')]");//store point to cur_code that we needed to use  
	$rate = updaterate($curr);//call update to updaterate the rate
	$result[0]->rates = $rate;//update current xml elements value 
	$result[0]->time = $time;//update current xml elements value
	ob_clean();
	Header('Content-type: text/xml; charset=utf-8');//set header
	$filepath='../currency.xml';//set filepath
	file_put_contents( $filepath, $xml->asXML());//save file to filepath
	return $rate;//retutrn rate
}
//handle of post event
function post($curr, $xmlfile){
	$ts = time();//store current posting time
	$xml = simplexml_load_string($xmlfile);//load xml to string
	$result = $xml->xpath("//*[starts-with(local-name(), '".$curr."')]");//store point to cur_code that we needed to use  
	$old_rate = $result[0]->rates;//Get the old rate first
	$old_rate = (string)$old_rate;
	$rate = post_xml($curr, $xml, $ts);//call post_xml function to update currency.xml
	$xmlstr = "<action></action>";
	ob_clean();
	$newsXML = new SimpleXMLElement($xmlstr);//create simplexmlelement with root <action></action>
	$newsXML->addAttribute('type', 'post');
	$newsXML->addChild('at', gmdate("d M Y H:i:s", $ts));
	$newsXML->addChild('rate', $rate);
	$newsXML->addChild('old_rate', $old_rate);
	$currd = $newsXML->addChild('curr');
	$currd->addChild('code', $curr);
	$currd->addChild('name', $result[0]->curr);
	$currd->addChild('loc', $result[0]->loc);
	ob_clean();
	Header('Content-type: text/xml; charset=utf-8');
	echo $newsXML->asXML();//print xml of post event
}
//function to update currency that passed
function updaterate($curr){
	$json = file_get_contents("http://data.fixer.io/api/latest?access_key=d7934b368a70909d0a1cc921fab7d360");
	$array = json_decode($json, true);
	$b_rate = (float)$array['rates']['GBP'];
	return (float)$array['rates'][(string)$curr]/$b_rate;
}
//Getting details of curr needed to use for put
function find_details($curr){
	$xmlfile = file_get_contents('https://www.currency-iso.org/dam/downloads/lists/list_one.xml');
	$xml = new SimpleXMLElement($xmlfile);
	$result = $xml->xpath("//CcyNtry[Ccy='".$curr."']");
	$flag = 0;
	for($i=0;$i<count($result);$i++){
		$tloc = (string)$result[$i]->CtryNm;
		if($flag)
			$loc = $loc.", ".$tloc;
		else
			$loc = $tloc;
		$currd = (string)$result[$i]->CcyNm;
		$flag=1;
	}
	return $array = array('curr'=>$currd,'loc'=>$loc);
}
//update currency.xml of putting
function put_xml($curr, $xml, $ts){
	$result = $xml-> currency;
	$currd = $result -> addChild((string)$curr);
	$detail = find_details($curr);
	$currd -> addChild('curr',$detail['curr']);
	$currd -> addChild('time',$ts);
	$currd -> addChild('rates',updaterate($curr));
	$currd -> addChild('loc',$detail['loc']);
	ob_clean();
	Header('Content-type: text/xml; charset=utf-8');
	$filepath='../currency.xml';
	file_put_contents( $filepath, $xml->asXML());
	return $detail;
}
//Put event handler
function put($curr,$xmlfile){
	$xml = simplexml_load_string($xmlfile);
	$ts = time();
	$rate = updaterate($curr);
	$detail = put_xml($curr, $xml, $ts);
	$xmlstr = "<action></action>";
	ob_clean();
	$newsXML = new SimpleXMLElement($xmlstr);
	$newsXML->addAttribute('type', 'put');
	$newsXML->addChild('at', gmdate("d M Y H:i:s", $ts));
	$newsXML->addChild('rate', $rate);
	$currd = $newsXML->addChild('curr');
	$currd->addChild('code', $curr);
	$currd->addChild('name', $detail['curr']);
	$currd->addChild('loc', $detail['loc']);
	ob_clean();
	Header('Content-type: text/xml; charset=utf-8');
	echo $newsXML->asXML();
}
//update currency.xml of deleting
function delete_xml($curr,$xmlfile){
	$xml = simplexml_load_string($xmlfile);
	$result = $xml -> currency;
	foreach($result as $key=>$val){
		if((string)$key == (string)$curr)
			unset($key);
	}
	ob_clean();
	Header('Content-type: text/xml; charset=utf-8');
	$filepath='../currency.xml';
	file_put_contents( $filepath, $xml->asXML());
}
//delete event handler
function delete($curr,$xmlfile){
	$ts = time();
	delete_xml($curr,$xmlfile);
	$xmlstr = "<action></action>";
	ob_clean();
	$newsXML = new SimpleXMLElement($xmlstr);
	$newsXML->addAttribute('type', 'del');
	$newsXML->addChild('at', gmdate("d M Y H:i:s", $ts));
	$newsXML->addChild('code', $curr);
	ob_clean();
	Header('Content-type: text/xml; charset=utf-8');
	echo $newsXML->asXML();
}
//function to easier store msg due to error code
function error2000($code){
	switch($code){
		case "2000":
			$msg="Action not recognized or is missing";
			break;
		case "2100":
			$msg="Currency code in wrong format or is missing";
			break;
		case "2200":
			$msg="Currency code not found for update";
			break;
		case "2300":
			$msg="No rate listed for this currency";
			break;
		case "2400":
			$msg="Cannot update base currency";
			break;
		case "2500":
			$msg="Error in service";
			break;
		default:
			$msg="Error in service";
			$code="2500";
			break;
	}
	$data = array('code'=>$code, 'msg'=>$msg);
	return $data;
}
//check if cur_code contain in current xml file
function is_curcontain($cur,$xmlfile){
	$flag=0;
	$xml = simplexml_load_string($xmlfile);
	$result = $xml -> currency;
	foreach($result as $key=>$val){
		if((string)$key == (string)$curr)
			$flag=1;
	}
	return $flag;
}
//check if currency api on web have the rate of this cur_code
function is_rate($cur){
	$flag=0;
	$json = file_get_contents("http://data.fixer.io/api/latest?access_key=d7934b368a70909d0a1cc921fab7d360");
	$array = json_decode($json, true);
	foreach($array as $key=>$val){
		if((string)$key==$cur){
			$flag=1;
		}
	}
	return $flag;
}
//check 2000-2400 type error
function is_error($cur,$action, $xmlfile){
	$flag=0;
	if(!isset($action)||empty($action)){
		$flag='2000';
	}
	else if(!isset($cur)||strlen($cur)>3||strlen($cur)<3||!ctype_upper($cur)){
		$flag='2100';
	}
	else if(!is_curcontain($cur, $xmlfile)){
		$flag='2200';
	}
	else if(!is_rate($cur)){
		$flag='2300';
	}
	else if($cur=='GBP'){
		$flag='2400';
	}
	return $flag;
}
//error event handler
function output_error($data,$action){
	$xmlstr = "<action></action>";
	ob_clean();
	$newsXML = new SimpleXMLElement($xmlstr);
	$newsXML->addAttribute('type', (string)$action);
	$error=$newsXML->addChild('error');
	$data = error2000($data);
	$error->addChild('code',(string)$data['code']);
	$error->addChild('msg',(string)$data['msg']);
	ob_clean();
	Header('Content-type: text/xml; charset=utf-8');
	echo $newsXML->asXML();
}
//main
$curr = (string)$_GET['cur'];//get the parameter that pass in
$action = (string)$_GET['action'];//get the parameter that pass in
if(!file_exists("../currency.xml")){//check if currency.xml exist
	output_error(error2000('2500'),$action);//if not 2500 error
}
else{
	$xmlfile = file_get_contents('../currency.xml');//get xml file
	if(is_error($curr,$action,$xmlfile)){//check if there is error with parameters that passed 
		$data = is_error($curr,$action,$xmlfile);//get the error code if there is an error
		output_error($data,$action);//call error event handler
	}
	else{
		if($action=="post"){//if action = post, call post event handler
			post($curr,$xmlfile);
		}
		else if($action=="put"){//if action = put, call put event handler
			put($curr,$xmlfile);
		}
		else if($action=="del"){//if action = del, call delete event handler
			delete($curr,$xmlfile);
		}
	}
}
?>