<?php
/**
 * @file
 * Chart builder for the Statedata.info website.
 */

/**
 * @defgroup d3_statedata
 * @ingroup examples
 * @{
 *
*  The Node Form Alter example needs to be in another file.
* module_load_include('inc', 'd3_statedata', 'd3_statedata_node_form_alter');

/**
 * Implements hook_menu().
 *
 * Sets up calls to drupal_get_form() for all our example cases.
 *
 * @see menu_example.module
 * @see menu_example_menu()
 */
class stateFunctions {
  function __autoload($class_name)

  {

      require_once(dirname(__FILE__) . '/' . $classname . '.php');



  }






  function store_url_vars($report,$tabledescription, $state,$year,$table, $reportvar) {

    

    include('/var/www/chartinclude.inc');
    $con = new mysqli($servername, $username, $password, $dbname);
    $randomno = rand(100000, 999999);
    $sessstring = "vars$randomno";

    $storvars = array();
    $description = "$tabledescription: ";
    $storvars['report'] = $report ? $report : '';
    $storvars['state'] = $state ? $state : '';
    $storvars['year'] = $year ? $year : '';
    $storvars['table'] = $table ? $table : '';
    $storvars['reportvar'] = $reportvar ? $reportvar : '';
    $descripquery = $table && $reportvar ? "SELECT `description` FROM `chart_labels` WHERE table_name = '$table' AND `column_name` = '$reportvar'" : null;
    $vardescrip = '';
    if($descripquery) {
       $descripresult = mysqli_query($con,$descripquery);
       $value = $descripresult->fetch_column();

         $vardescrip .= $value;


     }

    if($vardescrip && $vardescrip != '') {
      $description .= ': ' . $vardescrip;
    }

      $stringstates = '';
        if ($state && is_array($state)) { foreach($state as $key => $val)  $stringstates .=
        ", " . $val; }
        else { $stringstates = $state; }
          $description .= ": in $stringstates";
      $stringyears = '';
        if ($year && is_array($year)) { foreach($year as $key => $val)  $stringyears .=
        ", " . $val; }
        else { $stringyears = $year; }
          $description .= " during year(s): $stringyears";

    $time = time();
     $serialized = serialize($storvars);
     // echo "Description: $description";
    // $serialform = serialize($formrebuild);
     $userip = $_SERVER['REMOTE_ADDR'];
     $insertquery = "INSERT INTO `chart_url_store`(`urlid`,`create_time`,`formvars`,`ip_address`,`search_descrip`) VALUES ($randomno,$time,'$serialized','$userip','$description')";
     $insert = mysqli_query($con,$insertquery);
     // echo "Insertquery: $insertquery";
        return $description;





  }






}
