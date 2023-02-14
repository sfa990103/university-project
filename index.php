<?php
//check if xml is updated(<2hours)
function is_xmlupdate($xml){
	$flag=1;
	$xml = (array)$xml;
	$diff = time() - $xml['@attributes']['ts'];//get timestamp of the xml document
	if($diff/60/60 >= 2){
		$flag=0;
	}
	return $flag;
}
//update xml file
function xmlupdate($xml){
	ob_clean();//release xml elements
	$xmlfile = file_get_contents('currency.xml');
	$xml = new SimpleXMLElement($xmlfile);//Store simplexmlelemnet to xml variable
	$xml = $xml->xpath('currency');//choose child by xpath
	$data = newconverter($xml);//call newconverter to convert xml to array
	$data = $data[0];//store offset
	$json = file_get_contents("http://data.fixer.io/api/latest?access_key=d7934b368a70909d0a1cc921fab7d360");//pull json from currency api web
	$array = json_decode($json, true);//decode json type to array
	$ts = time();//get current time to update xml timestamp
	$b_rate = (float)$array['rates']['GBP'];//store rate of base rate
	foreach($data as $key=>$val){
		foreach((array)$val as $k=>$v){
			if((string)$k=='rates')
			$data[(string)$key][(string)$k] = (float)$array[(string)$k][(string)$key]/$b_rate;//change currencies' rate can be use when GBP rate = 1
			else if((string)$k=='time')
			$data[(string)$key][(string)$k] = $ts;	
			else
			$data[(string)$key][(string)$k] = $v;
		}
	}
	$data = array('currency'=>$data); //set array point to data
	$xmlstr = "<currencies></currencies>";
	ob_clean();
	$newsXML = new SimpleXMLElement($xmlstr);//create new simplexmlelement with <currencies></cureencies> as root
	$newsXML->addAttribute('ts', $ts);//add attribute 
	$newsXML->addAttribute('base', 'GBP');
	$currency = $newsXML->addChild('currency');//add child to root
	addxml($data['currency'],$currency);//call addxml to auto add child to child
	ob_clean();
	Header('Content-type: text/xml; charset=utf-8');//set header to print xml page
	$filepath='currency.xml';//set filepath
	file_put_contents( $filepath, $newsXML->asXML()); // save file to filepath
	return $newsXML->asXML(); 
}
//newconverter convert xml to array
function newconverter($xml){   
    $array=(array)$xml;
	if(count($array)===0)
        return (string)$xml;
    foreach ($array as $key => $val){
        if (!is_object($val) || strpos(get_class($val), 'SimpleXML') === false){
            continue;
        }
        $array[$key] = newconverter($val);
    }
    return $array;
}
//old xmlconverter
function convertxmltoarray($xml){
	foreach($xml as $key => $val){
		$xml[(string)$key] = (array)$val;
	}
	for($i=0;$i<count($xml);$i++){
		foreach($xml[$i] as $key => $val){
			$xml[$i][(string)$key] = (array)$val;
		}
		foreach($xml[$i][(string)$key] as $k => $v){
				$xml[$i][(string)$k] = (string)$v;
		}
	}
	$xml = $xml[0];
	return $xml;
}
//function to handle exchange and return array of exchanges' value
function exchange($from,$to,$amt,$data){
	$conv = array();
	if($data[$from]['time']>$data[$to]['time'])
		$conv['at']=gmdate("d M Y H:i:s", $data[$from]['time']);
	else
		$conv['at']=gmdate("d M Y H:i:s", $data[$to]['time']);
	$conv['rate']=(float)$data[$to]['rates']/(float)$data[$from]['rates'];
	$conv['from']['code']=$from;
	$conv['from']['curr']=$data[$from]['curr'];
	$conv['from']['loc']=$data[$from]['loc'];
	$conv['from']['amt']=$amt;
	$conv['to']['code']=$to;
	$conv['to']['curr']=$data[$to]['curr'];
	$conv['to']['loc']=$data[$to]['loc'];
	$conv['to']['amt']=$amt*$conv['rate'];
	echo '<br/><br/>';
	$conv=array('conv'=>$conv);
	return $conv;
}
function output_xml($data){
	$xmlstr = "<?xml version='1.0' encoding='UTF-8'?><conv></conv>";
	$newsXML = new SimpleXMLElement($xmlstr);
	//$newsXML->addChild('at',(string)$data['conv']['at']);
	addxml($data['conv'],$newsXML);
	ob_clean();
	Header('Content-type: text/xml; charset=utf-8');
	echo $newsXML->asXML();
}
//addchild function
function addchild($key, $data, $xml){
	return $xml->addChild($key,(string)$data);
}
//addxml function
function addxml($data,$xml){
	foreach($data as $key=>$val){//for every array key and val
		if(!is_array($val)){//check if val is array
			addchild($key, $val, $xml);//if value is not array, can be added to child with val
		}
		else{
			$child_xml = $xml->addChild($key);//else needed to added child only
			addxml($val,$child_xml);//And call addxml again to added child array or val to upper child
		}
	}
}
//function to easier set msg of error
function error1000($code){
	switch($code){
		case "1000":
			$msg="Required parameter is missing";
			break;
		case "1100":
			$msg="Parameter not recognized";
			break;
		case "1200":
			$msg="Currency type not recognized";
			break;
		case "1300":
			$msg="Currency amount must be a decimal number";
			break;
		case "1400":
			$msg="Format must be xml or json";
			break;
		case "1500":
			$msg="Error in service";
			break;
		default:
			$msg="Error in service";
			$code="1500";
			break;
	}
	$data = array('code'=>$code, 'msg'=>$msg);
	$data = array('error'=>$data);
	$data = array('conv'=>$data);
	return $data;
}
//check if is currency in our xml file
function is_currency($currency,$data){
	$flag = 0;
	foreach($data as $key=>$val){
		if($currency == (string)$key)
			$flag=1;
	}
	return $flag;
}
//check if value is decimal 
function is_decimal( $val ){
    return is_numeric( $val ) && floor( $val ) != $val;
}

//main
$from = (string)$_GET['from'];//get value
$to = (string)$_GET['to'];//get value
$amt = (float)$_GET['amnt'];//get value
$format = (string)$_GET['format'];//get value
if(!isset($from)||!isset($to)||!isset($amt)||!isset($format)){//check if is error:1000 type
	$data = error1000('1000');
}
else if(empty($from)||empty($to)||empty($amt)||empty($format)){//check if is error:1100 type
	$data = error1000('1100');
}
else if(!is_decimal($amt)){//check if is error:1300 type
	$data = error1000('1300');
}
else if($format!='xml'&&$format!='json'){//check if is error:1400 type
	$data = error1000('1400');
}
else if(!file_exists("currency.xml")){//check if is error:1500 type
	$data = error1000('1500');
}
else{
	$xmlfile = file_get_contents('currency.xml');
	$xml = new SimpleXMLElement($xmlfile);
	if(!is_xmlupdate($xml)){
		$xmlfile = xmlupdate($xml);
		$xml = new SimpleXMLElement($xmlfile);
	}
	$xml = $xml->xpath('currency');
}
	if(!is_currency($from, convertxmltoarray($xml)) || !is_currency($to, convertxmltoarray($xml))){
		$data = error1000('1200');
	}
	else{
		$data=exchange($from,$to,$amt,convertxmltoarray($xml));
	}	
	switch($format){
	case "json":
		echo json_encode($data);
	break;
	default:
		output_xml($data);
	break;
}
?>