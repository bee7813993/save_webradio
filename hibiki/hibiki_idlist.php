<?php
setlocale(LC_ALL,'ja_JP.UTF-8');

$hibikitopurl="https://vcms-api.hibiki-radio.jp/api/v1//programs?limit=128&page=1";

function file_get_html_with_retry($url, $retrytimes = 5, $timeoutsec = 1){

    global $errormsg;
    $errno = 0;
    $hearders = array( 'Host: vcms-api.hibiki-radio.jp' , 
                       'Origin: http://hibiki-radio.jp' ,
                       'X-Requested-With: XMLHttpRequest' );

    for($loopcount = 0 ; $loopcount < $retrytimes ; $loopcount ++){
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_REFERER, 'http://hibiki-radio.jp/' );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $hearders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutsec);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutsec);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $contents = curl_exec($ch);
        //var_dump($contents); //debug
        if( $contents !== false) {
            curl_close($ch);
            break;
        }
        $errno = curl_errno($ch);
        $errormsg = curl_error($ch);
        print $timeoutsec;
        curl_close($ch);
    }
    if ($loopcount === $retrytimes) {
        $error_message = curl_strerror($errno);
        print 'http connection error : '.$error_message . ' url : ' . $url . "\n";
    }
    return $contents;

}

$html = file_get_html_with_retry($hibikitopurl,5,15);

if($html == false){
    print("Hibiki Top Page download Failed.\n");
    die();
}
#$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'ASCII, JIS, UTF-8, EUC-JP, SJIS');
##mb_convert_variables("UTF-8",'auto',$html );
#$domDocument = new DOMDocument();
#libxml_use_internal_errors(true);
#$domDocument->loadHTML($html);
#libxml_clear_errors();
#$xmlString = $domDocument->saveXML();
#$xmlObject = simplexml_load_string($xmlString);
#$array = json_decode(json_encode($xmlObject), true);
$array = json_decode($html, true);
$programarray=$array;
#var_dump($programarray);

foreach($programarray as $program){
   $id = $program["access_id"];
   $title = $program["name"];
   $update = $program["episode_updated_at"];
   $times = $program["latest_episode_name"];
   $publish_start = $program["publish_start_at"];
   if(array_key_exists("cast",$program)){
       $mc = $program["cast"];
   }else {
       $mc = "";
   }
   $idinfo = sprintf("%-24s %20s %s:%s %s %s start\n",$id,$update,$title,$times,$mc,$publish_start);
   print $idinfo;
}

#
?>
