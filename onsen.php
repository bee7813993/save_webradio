<?php


function file_get_html_with_retry($url, $retrytimes = 5, $timeoutsec = 1){

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
  $url = 'http://www.onsen.ag/data/api/getMovieInfo/'.$id.'?callback=callback';
  // print $url."\n";
  $json = file_get_html_with_retry($url);
  // print $json."\n";
  preg_match('/callback\((.+)\)/',$json,$json_matchs);
  // var_dump($json_matchs);
  $radioinfo=json_decode($json_matchs[1],$assoc = true);
  // var_dump($radioinfo);
  return $radioinfo;
}

function build_filename($radioinfo,$id = 'none'){

  $path_parts = pathinfo($radioinfo['moviePath']['pc']);
  

  $name = "";
  if(!empty($radioinfo['update'])){
     $name = $name.$radioinfo['update'].'放送'.'_';
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
  return $name;

  
}

function downloadfiles($url,$filename)
{
$fp = fopen($filename, "w");
if($fp == false){
  print("file open failed :$filename");
  return false;
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);

$ret = curl_exec($ch);
if($ret == true){
    print "Success download : $filename\n";
}else {
    print "Download failed : $filename\n";
}

curl_close($ch);
fclose($fp);

}


// test radio_alcot

$radioinfo = analyze_radiomedia('radio_alcot');
$finename = build_filename($radioinfo,'radio_alcot');
print $finename;

downloadfiles($radioinfo['moviePath']['pc'],$finename);


?>
