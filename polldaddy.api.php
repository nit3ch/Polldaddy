<?php
//3498bef2-fe4f-bfa9-eb6e-0000519e06d5

function polldaddy_send_request($xml){
  $fp = fsockopen('api.polldaddy.com', 80,
                  $err_num, $err_str, 3);
  if(!$fp) {
    $errors[-1] = "Can't connect";
    return false;
  }

  if(function_exists('stream_set_timeout')){
    stream_set_timeout($fp, 3);
  }

  $request  = "POST / HTTP/1.0\r\n";
  $request .= "Host: api.polldaddy.com\r\n";
  $request .= "User-agent: PollDaddy PHP Client/0.1\r\n";
  $request .= "Content-Type: text/xml; charset=utf-8\r\n";
  $request .= 'Content-Length: ' . strlen($xml) . "\r\n";

  fwrite($fp, "$request\r\n$xml");

  $response = '';
  while (!feof($fp)){
    $response .= fread($fp, 4096);
  }
  fclose($fp);



  if(!$response){
    $errors[-2] = 'No Data';
  }

  return $response;
}

function polldaddy_get_usercode($APIKey){
  $xml = '<?xml version="1.0" encoding="utf-8" ?>
	<pd:pdAccess partnerGUID="'.$APIKey.'" partnerUserID="0" xmlns:pd="http://api.polldaddy.com/pdapi.xsd">
	    <pd:demands>
	        <pd:demand id="GetUserCode"/>
	    </pd:demands>
	</pd:pdAccess>';
  $response = polldaddy_send_request($xml);
  $response = polldaddy_clear_request($response);
  $parsed = polldaddy_parse_response($response);
  $usercode = '';
  for($i = 0; $i < 6; $i++){
    if($parsed[$i]['tag'] == 'PD:USERCODE'){
      $usercode = $parsed[$i]['value'];
    }
  }
  return $usercode;
}

function polldaddy_parse_response($response){
  $xml_parser = xml_parser_create();
  $data = array();
  xml_parse_into_struct($xml_parser, $response, $data);
  return $data;
}

function polldaddy_clear_request($response){
  return preg_replace("/[ a-zA-Z0-9:;\n\r\-=\/,\.]*</",'<',$response,1);
}

function polldaddy_get_polls(){
  $settings = variable_get('polldaddy_settings', array());
  $xml = '<?xml version="1.0" encoding="utf-8" ?>
  <pd:pdRequest xmlns:pd="http://api.polldaddy.com/pdapi.xsd" partnerGUID="'.$settings['polldaddy_partner_guid'].'">
      <pd:userCode>'.$settings['polldaddy_usercode'].'</pd:userCode>
      <pd:demands>
          <pd:demand id="GetPolls">
            <pd:list end="0" start="0"/>
          </pd:demand>
      </pd:demands>
  </pd:pdRequest>';
  $response = polldaddy_send_request($xml);
  $response = polldaddy_clear_request($response);
  $parsed = polldaddy_parse_response($response);
  $polls = array('None' => '- None selected -');
  foreach($parsed as $poll){
    if($poll['tag'] == 'PD:POLL'){
      $polls[$poll['attributes']['ID']] = $poll['value'];
    }
  }
  
  return $polls;
}