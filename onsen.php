<?php

$errormsg = "";

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
  
  if(!empty($radioinfo['personality'])){
     $name = $name.'_'.'［'.$radioinfo['personality'];
     if(!empty($radioinfo['guest'])){
         $name = $name.'Guest '.$radioinfo['guest'];
     }
     $name = $name.'］';
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

