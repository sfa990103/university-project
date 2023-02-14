<?php
//Currencies api
//Create by Yiu Lam Cheng(20034649)
//Store SimpleXML element to array
$xmlfile = file_get_contents('https://www.currency-iso.org/dam/downloads/lists/list_one.xml');//Get Iso xml file from loaction
$xml = new SimpleXMLElement($xmlfile);//Create new simpleXMLElement and store the file to it
$result = $xml->xpath("CcyTbl/CcyNtry");//Using xpath to get xml that we needed to use
//convert SimpleXML object to normal array & string
foreach($result as $key => $val){
	$result[(string)$key] = (array)$val;
}
//Get rates from fixco to array
$json = file_get_contents("http://data.fixer.io/api/latest?access_key=d7934b368a70909d0a1cc921fab7d360");//Get Json from location api
$array = json_decode($json, true);//Put Json to array
//Get rates update timestamp
$ts = time();
//Get base rate
$b_rate = (float)$array['rates']['GBP'];
$iso = array('AUD'=>'0','BRL'=>'0','CAD'=>'0','CHF'=>'0','CNY'=>'0','DKK'=>'0','EUR'=>'0','GBP'=>'0','HKD'=>'0','HUF'=>'0','INR'=>'0','JPY'=>'0','MXN'=>'0','MYR'=>'0','NOK'=>'0','NZD'=>'0','PHP'=>'0','RUB'=>'0','SEK'=>'0','SGD'=>'0','THB'=>'0','TRY'=>'0','USD'=>'0','ZAR'=>'0');
//Check & Add currency that rate list dont have
foreach($iso as $k=>$v){
	$flag=0;
	foreach($array['rates'] as $key => $val){
		if((string)$key==(string)$k){
			$iso[$k]=(float)$val;
			$flag=1;
		}
	}
}
$array['rates']=$iso;
//Check rate list & iso list get location to currency
foreach($array['rates'] as $key => $val){
	$loc='';
	$flag=0;
	for($i=0;$i<count($result);$i++){
		if(isset($result[$i]['Ccy'])){
		if((string)$key==(string)$result[$i]['Ccy']){
			$tloc = (string)$result[$i]['CtryNm'];
			if($flag==1)
			$loc = $loc.', '.$tloc;
			else
			$loc = $tloc;	
			$rates=(float)$array['rates'][$key]/$b_rate;
			$curr=(string)$result[$i]['CcyNm'];
			$time=$ts;
			$flag=1;
		}
	}
	}
	if($flag==1){
	$check[$key]['curr']=$curr;
	$check[$key]['time']=$time;	
	$check[$key]['rates']=$rates;	
	$check[$key]['loc']=$loc;
	}
}
//create xml and output
$newsXML = new SimpleXMLElement("<currencies></currencies>");
$newsXML->addAttribute('ts', $ts);
$newsXML->addAttribute('base', 'GBP');
$currency = $newsXML->addChild('currency');
foreach($check as $key=>$val){
	$code = $currency->addChild($key);
	$code->addChild('curr',$check[$key]['curr']);
	$code->addChild('time',$check[$key]['time']);
	$code->addChild('rates',$check[$key]['rates']);
	$code->addChild('loc',$check[$key]['loc']);
}
Header('Content-type: text/xml');
echo $newsXML->asXML();
$filepath='currency.xml';
file_put_contents( $filepath, $newsXML->asXML());
?>