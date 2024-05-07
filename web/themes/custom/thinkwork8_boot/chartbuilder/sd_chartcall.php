<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//print_r($_POST);
include_once('/var/www/chartinclude.inc');
$con = new mysqli($servername, $username, $password, $dbname);

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
include_once(dirname(__FILE__) . '/stateFunctions.php');
$type = isset($_GET['type']) ? mysqli_real_escape_string($con,$_GET['type']) : null;
$reporttype = isset($_GET['reportType']) ? mysqli_real_escape_string($con,$_GET['reportType']) : null;
$singletype = isset($_GET['singletype']) ? mysqli_real_escape_string($con,$_GET['singletype']) : '';
$storedid = isset($_GET['storedid']) ? mysqli_real_escape_string($con,$_GET['storedid']) : null;
$numpercdol  = isset($_GET['numpercdol']) ? mysqli_real_escape_string($con,$_GET['numpercdol']) : null;
$tableindex = isset($_GET['tableindex']) ? mysqli_real_escape_string($con,$_GET['tableindex']) : null;
//$programselect = $_REQUEST['programselect'] ? mysqli_real_escape_string($con,$_REQUEST['programselect']) : NULL;
$getstoredvar = isset($_GET['getstoredvar']) ? $_GET['getstoredvar'] : null;

$urlid = isset($_GET['urlid']) ? $_GET['urlid'] : null;


$postAssoc = $_POST ? $_POST : NULL;

$activityarray = array();

if($getstoredvar) pullStoredVar($getstoredvar,$urlid);

if ($type && $type == 'payer') {
    populatePayers($grp);

} elseif ($type && $type == 'program') {
        populatePrograms('nocull');

} elseif ($type && $type == 'race') {
    populateRace();

}  elseif ($type && $type == 'ethnic') {
populateEthnic();

}  elseif ($type && $type == 'nonPop') {
    populateNonwork();

}   elseif ($type && $type == 'years') {
        populateYears($tableindex);

 }
elseif ($type && $type == 'programcull') {
        populatePrograms('cull',$grp);

}
elseif ($type && ($type == 'table' || $type=='download')) {
    sendChart($type,$reporttype,$singletype,null,null);

}
elseif ($type && $type == 'chart') {
    sendChart($type,$reporttype,$singletype,$tableindex,$numpercdol);

}
/*
function switchTableName($rptperiod,$view,$grp) {
    $first = substr($rptperiod, 0,4) . substr($rptperiod,5,2) . "_";
    $middle = $view == 'payer' || $view == 'trend' ? 'activ_' : 'nonwork_';
    $last = $grp;
    return $first . $middle . $last;
}

function populatePayers($grp) {
		global $con;
        $info = new stdClass();

        $query = "SELECT `field_choice_code`, `field_choice_label` from `mi_labels` WHERE `field_name` = 'payerid'" . ($grp !== 'sud' ? " AND `field_choice_code` != '1182841'" : "") . " ORDER BY `field_choice_label`";

		$result = mysqli_query($con,$query);


        $rows = array();
        while($r = mysqli_fetch_assoc($result)) {
            $rows[] = $r;
        }
        echo json_encode($rows);


} */
function populateYears($tableindex) {
    global $con;
    $query = "SELECT DISTINCT `YEAR` FROM `$tableindex` WHERE `YEAR` != 0000 ORDER BY `YEAR` ASC";

    $result = mysqli_query($con,$query);

    $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
    }
    $rptperarray = array();
    foreach($rows as $i) {
        foreach($i as $key => $value) {
        if(!in_array(array('YEAR' =>$value),$rptperarray)) $rptperarray[]= array('YEAR' => $value);
        }
    }

    echo json_encode($rptperarray);


}
function pullStoredVar($getstoredvar,$urlid) {
    global $con;
    if($getstoredvar == 'report') {
        $firstquery = "SELECT `formvars` from `chart_url_store` where `urlid` = $urlid";
        $result = mysqli_query($con,$firstquery);
        $formvars = $result->fetch_column();
        $variablearray = unserialize($formvars);
        $storedvar = $variablearray['report'];
    
        echo $storedvar;
        
    } else {
   
        $firstquery = "SELECT `urlid`,`formvars`, `search_descrip` from `chart_url_store` where `urlid` = $urlid LIMIT 1";
        $result = mysqli_query($con,$firstquery);
        while($row = mysqli_fetch_assoc($result)) {;
            $formvars = unserialize($row['formvars']);
            $search_descrip = $row['search_descrip'];
            $reportvar = $formvars['reportvar'];
           
            if ($getstoredvar == 'search_descrip') return $search_descrip;
            elseif($getstoredvar == 'reportvar') return $reportvar;
            return  is_array($formvars) ? $formvars : FALSE;
        }
    }
      
}

function sendChart($type,$reporttype,$singletype,$tableindex,$numpercdol) {
    global $con;
    global $storedid;
    global $urlid;
    if($urlid && $urlid != ''){
        $postAssoc = array();

        $storedAssoc = pullStoredVar('formvars',$urlid);
        $postAssoc['tableId'] = $storedAssoc['table'];
        $postAssoc['tableDescrip'] = pullStoredVar('search_descrip',$urlid);
        $postAssoc['reportChoose'] = pullStoredVar('reportvar',$urlid);
        if($reporttype == 'comparison') {
            foreach($storedAssoc['state'] as $key => $value)
            { 
                $postAssoc['regionStates'][]= $value;
            }
        }
        else  
        {
            
                $postAssoc['regionStatesInd']= $storedAssoc['state'];
            
        }

        if($reporttype == 'national') {
            foreach($storedAssoc['state'] as $key => $value)
            { 
                $postAssoc['summChoose'] = $storedAssoc['year'];
            }
        }
        else  {
            foreach($storedAssoc['year'] as $key => $value)
                { 
                    $postAssoc['regionYear'][]= $value;
                }
        }

    } else {
        global $postAssoc;
        
    }
     // $showarray = print_r($storedAssoc,true);
     // echo "Show array: $showarray";
    //if urlid query the url_store and replace the post values with the urlstore values
    $yeararray = array();
    foreach ($postAssoc['regionYear'] as $key => $value) { $yeararray[]= $value ;}
    $statearray = array();
    foreach ($postAssoc['regionStates'] as $key => $value) { $statearray[]= $value ;}
    $state = isset($postAssoc['regionStatesInd']) ? $postAssoc['regionStatesInd'] : null;
    $state_functions = new stateFunctions;
    $tabledescription = $postAssoc['tableDescrip'];
    $yearstring = implode(",", $yeararray);
    $statestring = implode("','", $statearray);
    switch($reporttype) {
      case 'single':
        $table = NULL;
        $reportvar = NULL;
        $tabledescription = $postAssoc['tableDescrip'];
        $tablename = $postAssoc['tableId'];
        $limitindex = $tableindex ? $tableindex : '0';
        
        
        
        if($singletype){
            $getfieldsquery = $type == 'chart' ? "SELECT `column_name`,`short_name` FROM `chart_labels` WHERE `table_name` = '$tablename' AND `num_perc_dol` = '" . ($numpercdol ? $numpercdol : $singletype) . "' ORDER BY `sort_order` ASC LIMIT $limitindex,1" : "SELECT `column_name`,`short_name` FROM `chart_labels` WHERE `table_name` = '$tablename' AND `num_perc_dol` = '$singletype' AND `sort_order` IS NOT NULL ORDER BY `sort_order`";
            $result = mysqli_query($con,$getfieldsquery);
            $querystring = "SELECT ";
            $row_cnt = $result->num_rows;
            if ($row_cnt === 0) break;
            $i = 0;
            while($row = mysqli_fetch_assoc($result)) {
                $querystring .= $i == $row_cnt - 1 ? "IF(`" . $row['column_name'] . "` = -1, NULL,`" . $row['column_name'] . "`) '" . $row['short_name'] . "'" : "IF(`" . $row['column_name'] . "` = -1, NULL,`" . $row['column_name'] . "`) '" . $row['short_name'] . "',";

                $i++;

            }
        
           $querystring .= " FROM `$tablename` WHERE `STATE` = '$state' AND YEAR IN ($yearstring) ORDER BY `YEAR`";
       
            array_unshift($yeararray, 'Data point');

            $tablerows = mysqli_query($con,$querystring);
            $tableoutput = array();
            $csvcols = array();
            $csvbody = array();
            while($row = mysqli_fetch_assoc($tablerows)) {
                foreach ($row as $key => $value) {
                if(!is_array($tableoutput[$key])) $tableoutput[$key][]= $key;
                $tableoutput[$key][]= $value;
                }
            }
            $table['cols'] = array();
            foreach($yeararray as $key => $value) {
                

                //Labels for the chart, these represent the column titles
                if ($key === 0) $table['cols'][]= array('id' => '', 'label' => $value, 'type' => 'string');
                else 
                $table['cols'][]= array('id' => '', 'label' => $value, 'type' => 'number');
                $csvcols[]= $value;
                
            }
            $temp = array();
            $k = 0;
            foreach($tableoutput as $key => $val) {
                
                $valcount = count($yeararray);
                for ($i = 0; $i < $valcount; $i++) {
                    if($i === 0) { $temp[$k][] = array('v' => (string) $val[$i]); 
                        $csvbody[$k][]= $val[$i];
                    }
                    else { 
                        if($val[$i] == -1) {
                            $temp[$k][] = array('v' => null); 

                        } else {

                            $temp[$k][] = $singletype == 'num' ? (is_null($val[$i]) ? array('v' => null) :  array('v' => (int) $val[$i])) : (is_null($val[$i]) ? array('v' => null) :  array('v' => (float) $val[$i])) ; 
                            $csvbody[$k][] = is_null($val[$i]) || $val[$i] == -1 ? null : $val[$i];
                        }
                    }
                
                
                }
                $jsonrows[] =  array('c' => $temp[$k]);
                $k++;
            }

        
        
            $table['rows'] = $jsonrows;
            $yearminus =array_shift($yeararray);

        if(!$urlid && $type == 'chart') $storedvars = $state_functions->store_url_vars('single',$tabledescription,$state,$yeararray,$tablename, null);
            $jsonTable = json_encode($table, true);
            if($type == 'download' && $singletype == 'num') { 
                array_unshift($tableoutput, $csvcols);
                array_unshift($tableoutput, array('Numbers'));
                sendCSV($tableoutput,$reporttype,$singletype);
                  return;
            }
            elseif($type == 'download' && $singletype != 'num') { 
                array_unshift($tableoutput, $csvcols);
                if($singletype == 'perc') 
                    array_unshift($tableoutput, array(' '),array('Percentages'));
                else 
                    array_unshift($tableoutput, array(' '), array('Dollars and Hours'));
                return $tableoutput;
            }
        
            else echo $jsonTable;
        }
        break;

    


        
      case 'comparison':
        $reportvar = isset($postAssoc['reportChoose']) ? $postAssoc['reportChoose'] : null;
        $tablename = isset($postAssoc['tableId']) ? $postAssoc['tableId'] : null;
        $getfieldsquery = "SELECT `short_name` FROM `chart_labels` WHERE `table_name` = '$tablename' AND `column_name` = '$reportvar' LIMIT 1";
        $result = mysqli_query($con,$getfieldsquery);
        $shortname = $result->fetch_column();
        $getformatquery = "SELECT `format` FROM `chart_labels` WHERE `table_name` = '$tablename' AND `column_name` = '$reportvar' LIMIT 1";
        $getformat = mysqli_query($con,$getformatquery);
        $format = $getformat->fetch_column();
        $getformattypequery = "SELECT `num_perc_dol` FROM `chart_labels` WHERE `table_name` = '$tablename' AND `column_name` = '$reportvar' LIMIT 1";
        $getformattype = mysqli_query($con,$getformattypequery);
        $formattype = $getformattype->fetch_column();
        $querystring = "SELECT `s`.`name` 'varstate',GROUP_CONCAT(IF(`$reportvar` = -1, NULL, `$reportvar`)) 'var' FROM `$tablename` t LEFT JOIN `sta_d3_states` s ON t.`STATE` = s.`abbreviation` WHERE `STATE` IN ('$statestring') AND `YEAR` IN ($yearstring) GROUP BY `STATE` ORDER BY `STATE`,`YEAR`";
       if(!$urlid) array_unshift($yeararray, 'State');
        //echo "Query string: $querystring";

        $tablerows = mysqli_query($con,$querystring);
        $tableoutput = array();
        $csvcols = array();
        while($row = mysqli_fetch_assoc($tablerows)) {
            $rowarray = explode(",",$row['var']);
            array_unshift($rowarray,$row['varstate']);
            $tableoutput[]= $rowarray;
        }
        $table['cols'] = array();
        foreach($yeararray as $key => $value) {
            
            
            //Labels for the chart, these represent the column titles
            if ($key === 0) $table['cols'][]= array('id' => '', 'label' => $value, 'type' => 'string');
            else 
            $table['cols'][]= array('id' => '', 'label' => $value, 'type' => 'number');
            //for the CSV download
            $csvcols[]= $value;
            
        }
        $temp = array();
        $k = 0;
        
        $valcount = count($yeararray);
        foreach($tableoutput as $key => $val) {
           
            for ($i = 0; $i < $valcount; $i++) {
                if($i === 0) { $temp[$k][] = array('v' => (string) $val[$i]); }
                else { 
                    if($formattype == 'perc') {
                        $formatter = new NumberFormatter('en_US', NumberFormatter::PERCENT);
                        $percvalue = $formatter->format($val[$i]);
                    }
                    $temp[$k][] = $format == 'double' ? (is_null($val[$i]) || $val[$i] == -1 ? array('v' => null) :  ($formattype == 'perc' ? array('v' => (float) $val[$i], 'f' => $percvalue ) : array('v' => (float) $val[$i]))) : (is_null($val[$i]) || $val[$i] == -1 ? array('v' => null) :  array('v' => (int) $val[$i])) ; 
                }
            
            
            }
            $jsonrows[] =  array('c' => $temp[$k]);
            $k++;
        }
        if(!$urlid && $type == 'chart') $storedvars = $state_functions->store_url_vars('comparison',$tabledescription,$statearray,$yeararray,$tablename,$reportvar);
        $table['rows'] = $jsonrows;
        $jsonTable = json_encode($table, true);

        if($type == 'download') { 
            array_unshift($tableoutput, $csvcols);
            sendCSV($tableoutput,'comparison', null);
              return;
          }
    
        else echo $jsonTable;


        break;
      case 'national':
        $tablename = isset($postAssoc['tableId']) ? $postAssoc['tableId'] : null;
        $reportvar = isset($postAssoc['reportChoose']) ? $postAssoc['reportChoose'] : null;
        $year = isset($postAssoc['summChoose']) ? $postAssoc['summChoose'] : null;

        $getfieldsquery = "SELECT `short_name` FROM `chart_labels` WHERE `table_name` = '$tablename' AND `column_name` = '$reportvar' LIMIT 1";
        $result = mysqli_query($con,$getfieldsquery);
        $shortname = $result->fetch_column();

        $getformatquery = "SELECT `format` FROM `chart_labels` WHERE `table_name` = '$tablename' AND `column_name` = '$reportvar' LIMIT 1";
        $getformat = mysqli_query($con,$getformatquery);
        $format = $getformat->fetch_column();
        $getformattypequery = "SELECT `num_perc_dol` FROM `chart_labels` WHERE `table_name` = '$tablename' AND `column_name` = '$reportvar' LIMIT 1";
        $getformattype = mysqli_query($con,$getformattypequery);
        $formattype = $getformattype->fetch_column();
        $querystring = "SELECT `s`.`name` 'State',IF(`$reportvar` = -1 , NULL , `$reportvar`) '$shortname' FROM `$tablename` t LEFT JOIN `sta_d3_states` s ON t.`STATE` = s.`abbreviation` WHERE `YEAR` = $year AND t.`STATE` != 'US' ORDER BY `State`";
        $tablerows = mysqli_query($con,$querystring);
        $tableoutput = array();
        while($row = mysqli_fetch_assoc($tablerows)) {
           
            $tableoutput[]= $row;
        }
        $table['cols'] = array();
        
            

            //Labels for the chart, these represent the column titles
        $table['cols'][]= array('id' => '', 'label' => 'State', 'type' => 'string');
            
        $table['cols'][]= array('id' => '', 'label' => $shortname, 'type' => 'number');
            
        $temp = array();
        $k = 0;
        foreach($tableoutput as $key => $val) {
            $valcount = count($tableoutput);
            if($formattype == 'perc') {
                $formatter = new NumberFormatter('en_US', NumberFormatter::PERCENT);
                $percvalue = $formatter->format($val[$shortname]);
            }
           $temp[$k][]= array('v' => (string) $val['State']);
           $temp[$k][]= is_null($val[$shortname])  || $val[$shortname] == -1 ?  array('v' => null) :  ($format == 'double' ? ($formattype == 'perc' ? array('v' => (float) $val[$shortname], 'f' => $percvalue ) : array('v' => (float) $val[$shortname])) : array('v' => (int) $val[$shortname]));
           $jsonrows[] =  array('c' => $temp[$k]);
            $k++;
            
        }
        if(!$urlid && $type == 'chart') $storedvars = $state_functions->store_url_vars('national',$tabledescription,'national',$year,$tablename,$reportvar);
        $table['rows'] = $jsonrows;
       
        $jsonTable = json_encode($table, true);


    if($type == 'download') { 
        $csvcols = array("State", $shortname);
        array_unshift($tableoutput, $csvcols);
        sendCSV($tableoutput,'national',null);
          return;
      }
      else echo $jsonTable;

        break;
    }

} 

 /*
function buildLabelArray($incoming, $type) {
    global $con;
    $fieldname = $type == 'nonwork' ? 'notlabd' : ($type == 'payer' ? 'empstatd' : $type);
    $labelquery = "SELECT `mi_labels`.`field_choice_code` AS 'code',`mi_labels`.`field_choice_label` AS 'label' from `mi_labels` WHERE `mi_labels`.`field_name` = '$fieldname' and `mi_labels`.`field_choice_code` IN(" . implode(",", $incoming) . ") ORDER BY `mi_labels`.`field_choice_code`" ;

    $result2 = mysqli_query($con,$labelquery);
    // mysqli_close($con);
    $labelarray = array();

    foreach($result2 as $row){
        $labelarray[$row['code']] = $row['label'];
    }



    return  $labelarray;
} */

function sendCSV($tableoutput,$downtype,$downcat) {
    global $con;
    global $postAssoc;
    $csvFilename = 'sd_chart_builder_data-' . date('m-d-Y_H-i') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $csvFilename);
    $outFile = fopen('php://output', 'w'); 
   $charttitle = isset($postAssoc['sendTitle']) ? array($postAssoc['sendTitle'] . "\r\n") : array("Your report" . "\r\n") ;
    $creditline = array("Downloaded from http://statedata.info on " . date('m/d/Y') . "\r\n");

    $headerrow = '';
    fputcsv($outFile, $charttitle);
    fputcsv($outFile, $creditline);

    if($downtype == 'single') {
        $tableoutputperc = sendChart('download','single','perc',null,null);
        $tableoutputdol = sendChart('download','single','dol',null,null);
        foreach($tableoutputperc as $key => $val)  array_push($tableoutput, $val);
        foreach($tableoutputdol as $key => $val)  array_push($tableoutput, $val);
    }
    //$showtable= print_r($tableoutput,true);
    //echo $showtable;
   foreach($tableoutput as $key => $value){
        if($value) fputcsv($outFile, $value);
    }




    fclose($outFile);
  
    
    

   

}

