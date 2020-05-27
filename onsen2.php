<?php

$tokenfile="onsen_token.conf";
$token="Authorization: Bearer 2073d5d49e40dbe93cde09949ade421bae3daa01bdd88b4a3afbbba58389a1f7";
$programurl='https://app.onsen.ag/api/me/programs/';

if ( file_exists($tokenfile) ) {
    $ret = file_get_contents($tokenfile);
    $token = "Authorization: ".$ret;
}
//echo $token;
$errormsg = "";
$id=166;

$options = getopt("i:");
if(array_key_exists('i',$options) ){
	$id=$options["i"];
}
//print $id;
//var_dump($options);
//die();

function file_get_html_with_retry_json($url, $retrytimes = 5, $timeoutsec = 1){

    global $errormsg;
    global $token;
    $errno = 0;
    
    if(empty($token)) {
    $headers = array('X-Device-Os: ios', 'Accept-Version: v3', 'Accept: */*', 'X-Device-Name: ', 'Accept-Language: ja-JP;q=1.0, en-JP;q=0.9', 'Content-Type: application/json', 'X-App-Version: 25');
    }else{
    $headers = array('X-Device-Os: ios', 'Accept-Version: v3', $token , 'Accept: */*', 'X-Device-Name: ', 'Accept-Language: ja-JP;q=1.0, en-JP;q=0.9', 'Content-Type: application/json', 'X-App-Version: 25');
    }

    for($loopcount = 0 ; $loopcount < $retrytimes ; $loopcount ++){
        
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "User-Agent: iOS/Onsen/2.6.1");
        curl_setopt($ch, CURLOPT_HTTPHEADER , $headers);
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

function get_programinfo_json($id){
	global $programurl;
	if(empty($id)){
		return false;
	}
	$url=$programurl.$id;
	
	$html =file_get_html_with_retry_json($url);
	if($html === false){
	  print("Onsen Program JSON download Failed.\n");
	  die();
	}
	$program_array=json_decode($html,true);
	if($html === false){
	   print("Parse Onsen Program JSON download Failed.\n");
	   print $html;
	}
	//var_dump($program_array);	
    return $program_array;
}


function build_filename_json($updateday,$title,$episode_title,$mc,$media_type,$id = 'none'){

  $name = "";
  if(!empty($updateday)){
     $name = $name.$updateday.'放送'.'_';
  }
  
  if($title){
     $name = $name.$title;
  }else{
     $name = $name.$id;
  }

  if(!empty($episode_title)){
     $name = $name.'_'.'第'.$episode_title.'回';
  }
  $name_bak = $name;
  
  if(!empty($mc)){
     $name = $name.'_'.'［'.$mc;
     $name = $name.'］';
  }
  if( strlen($name) > 250 ) {
     $name = $name_bak;
  }
  if($media_type="sound"){
    $name = $name.'.m4a';
  }else {
    $name = $name.'.mp4';
  }
  $name = mb_ereg_replace('\/','／',$name);
  $name = mb_ereg_replace('\*','＊',$name);
  $name = mb_ereg_replace('\!','！',$name);
  return $name;
}

function download_onsen_m3u8($url, $outfile){
    $cmd="ffmpeg";
    
    if(empty($url)){
       echo "URL is empty\n";
       return -1;
    }
    if(empty($outfile)){
       echo "outfilename is empty\n";
       return -1;
    }
    
    if(file_exists( $outfile )){
        if ( filesize ($finename) < 1024 ) {
            print "$finename is exists. but too small. Overwrite.  filesize : ".filesize ($finename)."\n";
            unlink ($finename);
        }else{
            echo "File: ".$outfile." is already exists\n";
           return -1;
        }
    }


    $execcmd=$cmd." -i ".$url."  -strict -2 '".$outfile."'";
    echo $execcmd;
    exec($execcmd);
}

$programinfo=get_programinfo_json($id);
$title = $programinfo["title"];
$program_mc="";
    foreach($programinfo["performer_list"] as $performer ){
          if(!empty($program_mc) ) $program_mc = $program_mc." ";
          $program_mc = $program_mc.$performer;
    }
    
foreach( $programinfo["episodes"] as $episode){
	$updateday= date('Y.m.d', strtotime($episode["updated_on"]));
	$episode_title=$episode["title"];
    $media_type=$episode["media_type"];
	$mc=$program_mc;
    foreach($episode["episode_performers"] as $performer ){
          if(!empty($mc) ) $mc = $mc." ";
          $mc = $mc.$performer["name"];
    }
    $mediaurl="";
    foreach($episode["episode_files"] as $episode_file ){
    	if($episode_file["media_url"] == "ios" ){
          $mediaurl = $episode_file["media_url"];
        }
        if(empty($mediaurl)) {
          $mediaurl = $episode_file["media_url"];
        }
    }
    $outfilename = build_filename_json($updateday,$title,$episode_title,$mc,$media_type,$programinfo["directory_name"]);
    print $outfilename."\n";
    print $mediaurl."\n";
    download_onsen_m3u8($mediaurl,$outfilename);
	
}

die();


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


function analyze_radiomedia($id)
{
  global $errormsg;

  $url = 'http://www.onsen.ag/data/api/getMovieInfo/'.$id.'?callback=callback';
  // print $url."\n";
  $json = file_get_html_with_retry($url);
  if($json === false ) {
        print "Onsen Meta data Download Failed \n";
	print "id : $id \n";
	print "url $url \n";
	print  $json." \n";
	return $json;
  }
  // print $json."\n";
  preg_match('/callback\((.+)\)/',$json,$json_matchs);
  // var_dump($json_matchs);
  $radioinfo=json_decode($json_matchs[1],$assoc = true);
  // var_dump($radioinfo);
  if(is_null($radioinfo)){
	print "Onsen Meta data Json Decode Failed \n";
	print "id : $id \n";
	print "url $url \n";
	print  $json." \n";
	return false;
  }
  if(!array_key_exists('moviePath',$radioinfo)){
	print "Onsen Meta data has no moviePath \n";
	print "id : $id \n";
	print "url $url \n";
	print  $json." \n";

  }
  return $radioinfo;
}

function makedatestring($ymd ){

  $retval = null;
  
  $array_date = explode('.', $ymd);
  if( $array_date != false ) {
      $retval = sprintf("%04d.%02d.%02d", $array_date[0],$array_date[1],$array_date[2]);
  }
  
  return $retval;

}

function build_filename($radioinfo,$id = 'none'){

  $path_parts = pathinfo($radioinfo['moviePath']['pc']);
  

  $name = "";
  $date_string = makedatestring($radioinfo['update']);
  if(!empty($date_string)){
     $name = $name.$date_string.'放送'.'_';
  }
  
  if(!empty($radioinfo['title'])){
     $name = $name.$radioinfo['title'];
  }else{
     $name = $name.$id;
  }

  if(!empty($radioinfo['count'])){
     $name = $name.'_'.'第'.$radioinfo['count'].'回';
  }
  $name_bak = $name;
  
  if(!empty($radioinfo['personality'])){
     $name = $name.'_'.'［'.$radioinfo['personality'];
     if(!empty($radioinfo['guest'])){
         $name = $name.'Guest '.$radioinfo['guest'];
     }
     $name = $name.'］';
  }
  if( strlen($name) > 250 ) {
     $name = $name_bak;
  }
  
  if(!empty($path_parts['extension'])){
    $name = $name.'.'.$path_parts['extension'];
  }
  $name = mb_ereg_replace('\/','／',$name);
  $name = mb_ereg_replace('\*','＊',$name);
  $name = mb_ereg_replace('\!','！',$name);
  return $name;

  
}

function downloadfiles($url,$filename)
{
global $errormsg;
$workfilename = "workfile";
$errflg = 0;

$fp = fopen($workfilename, "w");
if($fp == false){
  print("file open failed :$workfilename");
  return false;
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);

$ret = curl_exec($ch);
if($ret == true){
    logwrite ("Success download : $filename url : $url");
}else {
    logwrite ("Download failed  : $filename url : $url".curl_error($ch));
    $errormsg = curl_error($ch);
    $errflg++;
}

curl_close($ch);
fclose($fp);

if($errflg > 0){
    unlink($workfilename);
    return false;
}

if(filesize($workfilename) > 1024 ){
    copy($workfilename,$filename);
}else {
    logwrite ("filesize is too small  : $filename size : ".filesize($workfilename));
}
unlink($workfilename);

}

function read_idlist($idfile){
    $idlist = array();
    
    $idlistfd = fopen($idfile, "r");
    while( ($oneid = fgets($idlistfd,128)) !== false ){
        if($oneid[0] == '#') continue;
        $oneid = trim($oneid);
        if(strlen($oneid) > 0 )
            $idlist[] = trim($oneid);
    }
    fclose($idlistfd);
    
    return $idlist;

}

function logwrite($msg){

    $logfd = fopen('/tmp/onsendl.log', "a");
    $logmsg = date(DATE_ATOM)." $msg\n";
    fwrite($logfd, $logmsg);
    fclose($logfd);
}

logwrite("Start Onsen Download");

// start main
$idfile = false;
$destdir = false;
$idlist = array();

$options = getopt("f:d:");
if( $options === false) {
}else {
    if(array_key_exists("f", $options)) {
        $idfile = $options['f'];
        $idlist = read_idlist($idfile);
    }
    if(array_key_exists("d", $options)) {
        $destdir = $options['d'];
        $lastdirchar = substr($destdir,-1,1);
        if( $lastdirchar[0] == '/' ){
            $destdir = $destdir;
        } else {
            $destdir = $destdir.'/';
        }
    }
}

#var_dump ($idlist);
#print "\n";

$countor = 0;

foreach ($idlist as $id){
  $radioinfo = analyze_radiomedia($id);
  print "start id : $id\n";
  logwrite( "start id : $id");
  if($radioinfo === false){
    logwrite ("Analyze_radiomedia failed id : $id error : ".curl_error($ch));
    continue;
  }
  $finename = build_filename($radioinfo,$id);
  if($destdir) {
    $finename = $destdir.$finename;
  }
  if( file_exists($finename) ){
    if ( filesize ($finename) < 1024 ) {
      print "$finename is exists. but too small. Overwrite.  filesize : ".filesize ($finename)."\n";
    }else {
      print "$finename is exists. skip this file. filesize : ".filesize ($finename)."\n";
      continue;
    }
  }
  downloadfiles($radioinfo['moviePath']['pc'],$finename);
  $countor ++;
}

?>

