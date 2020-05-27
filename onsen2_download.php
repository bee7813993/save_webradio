<?php

function get_onsenfilelist_tofile(){
}

function get_onsenidnum_fromidname($idname){
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

function onsen_downloadloop(){
// main

}

?>
