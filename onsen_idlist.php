<?php
setlocale(LC_ALL,'ja_JP.UTF-8');

$onsentopurl="http://www.onsen.ag/index.html";

function file_get_html_with_retry($url, $retrytimes = 5, $timeoutsec = 1){

    global $errormsg;
    $errno = 0;

    for($loopcount = 0 ; $loopcount < $retrytimes ; $loopcount ++){
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
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

$html = file_get_html_with_retry($onsentopurl);

if($html == false){
    print("Onsen Top Page download Failed.\n");
    die();
}
$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'ASCII, JIS, UTF-8, EUC-JP, SJIS');
#mb_convert_variables("UTF-8",'auto',$html );
$domDocument = new DOMDocument();
libxml_use_internal_errors(true);
$domDocument->loadHTML($html);
libxml_clear_errors();
$xmlString = $domDocument->saveXML();

#$xmlString = preg_replace('/^.*denkifes2019.*$/um','',$xmlString);
print($xmlString);
$xmlObject = simplexml_load_string($xmlString);
var_dump(libxml_get_errors());
#var_dump($xmlObject);
$array = json_decode(json_encode($xmlObject), true);
#var_dump($array);
if(empty($array) ) {
    print $xmlObject."\n";
    die();
}
$programarray=$array['body']['div']['div'][0]['div']['div'][0]['div']['div']["section"][1]["div"][1]["ul"]["li"];
#var_dump($programarray);
#die();

foreach($programarray as $program){
# var_dump($program);
   $id = $program["@attributes"]["id"];
   $title = $program["h4"]["span"];
   $update = $program["@attributes"]["data-update"];
   $mc = $program["p"][1]["span"];
   if(empty($mc)){
       $mc="";
   }
   $idinfo = sprintf("%-16s %10s %s %s\n",$id,$update,$title,$mc);
   print $idinfo;
}

#
?>
