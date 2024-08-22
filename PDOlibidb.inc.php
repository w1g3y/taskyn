<?php
/* ======================================
 * A c t i v e B l o x
 *
 * The ActiveBlox collection of classes is Copyright (c) Nigel Weeks
 *
 * Any usage of these classes without permission is prohibited.
 * 
 * Please send suggestions/improvements to nigel.weeks@gmail.com
 *
 * All this include needs are the following:
 * $config[dbstring] // e.g. "twister:/path/to/database/gdb
 * $config[dbuser]
 * $config[dbpass]
 * ======================================= 
 */

function db_deglob($var,$type='str'){
$dbvar = "_".$var;
if(!isset($$var)){
  global $$var;
}

if(!isset($$dbvar)){
  global $$dbvar;
}
  /* ------------
   * This function pulls variables from SuperGlobals
   * If it encounters an array, it decends further, retrieving vars
   * in each case, it also creates a $_var variable, which is cleaned
   *  ready for DB use
   * ------------
   */

  // Test the _GET SuperGlobal first
  if($_GET[$var] != ""){
    //echo $var." in the \$_GET";
    //echo "DB_DEGLOB: Setting $var to $_GET[$var]<br>";
    $$var = $_GET[$var];
    $$dbvar = $_GET[$var];
    db_fix($$dbvar);
    //echo "DB_DEGLOB: Setting ".$dbvar." to ".$$dbvar."<br>";
  }
  // Test the _POST SuperGlobal next
  if($_POST[$var] != ""){
    //echo $var." in the \$_POST";
    //echo "DB_DEGLOB: Setting $var to $_POST[$var]<br>";
    $$var = $_POST[$var];
    $$dbvar = $_POST[$var];
    db_fix($$dbvar);
    //echo "DB_DEGLOB: Setting ".$dbvar." to ".$$dbvar."<br>";
  }
  // Test the _SERVER SuperGlobal last
  if($_SERVER[$var] != ""){
    //echo $var." in the \$_SERVER";
    //echo "DB_DEGLOB: Setting $var to $_SERVER[$var]<br>";
    $$var = $_SERVER[$var];
    $$dbvar = $_SERVER[$var];
    db_fix($$dbvar);
    //echo "DB_DEGLOB: Setting ".$dbvar." to ".$$dbvar."<br>";
  }
  if($type == "int"){
    if(!is_numeric($$dbvar)){
      unset($$dbvar);
    }
    if(!is_numeric($$var)){
      unset($$var);
    }
  } 

}

function db_safe($arr)
{
        array_walk($arr,'db_chk');
        return $arr;
} // end of db_safe

function db_chk(&$cell,$key){
        if(is_array($cell)){
                $cell = db_safe($cell);
        } else {
                db_fix($cell);
        }
}

function db_fix($a){
        // If it's going in the database, we want none of this...
        $a = stripslashes($a);
        $a = str_replace("'","''","$a");
        $a = str_replace('"','"',"$a");
        //$a = str_replace("<","&lt;","$a");
        //$a = str_replace(">","&gt;","$a");
        //$a = str_replace(";",".","$a");

        // Turn dates into ISO formatted YYYY-MM-DD
        if(substr_count($a,"/") == 2 && strlen($a) <= 10){
          // We have a date! Make it right!
	  $da = explode("/",$a);
          if(strlen($da[0]) <= 2 && $da[0] < 32) {
            // We have a date. Ensure the rest of it is ok
            if(strlen($da[1]) < 2 && $da[1] <= 10){
              // Single digit month - make double dig
              $da[1] = "0".$da[1];
            }
            if(strlen($da[0]) < 2 && $da[0] <= 10){
              // Single digit day - make double dig
              $da[0] = "0".$da[0];
            }
            if(strlen($da[2]) < 3){
              // two digit year - pad out to four
              if($da[2] < 50){
                $da[2] = "20".$da[2];
              } else {
                $da[2] = "19".$da[2];
              }
            }
           
            $a = "$da[2]-$da[1]-$da[0]";
          }
        }

        // Turn Timestamps into ISO formatted YYYY-MM-DD 00:00:00
        if(substr_count($a,"/") == 2 && substr_count($a,":") == 2 && strlen($a) <= 19){
          // We have a timestamp! Break the date section off it
          $parts = explode(" ",$a);

          // We have a date! Make it right!
          $da = explode("/",$parts[0]);
          if(strlen($da[0]) <= 2 && $da[0] < 32) {
            // We have a date. Ensure the rest of it is ok
            if(strlen($da[1]) < 2 && $da[1] <= 10){
              // Single digit month - make double dig
              $da[1] = "0".$da[1];
            }
            if(strlen($da[0]) < 2 && $da[0] <= 10){
              // Single digit day - make double dig
              $da[0] = "0".$da[0];
            }
            if(strlen($da[2]) < 3){
              // two digit year - pad out to four
              if($da[2] < 50){
                $da[2] = "20".$da[2];
              } else {
                $da[2] = "19".$da[2];
              }
            }

            $a = $da[2]."-".$da[1]."-".$da[0]." ".$parts[1];
          }
        }
}


function db_err($err,$sql)
{
	$err = stripslashes("$err");
	$err = str_replace('"','',$err);
	//$err = str_replace("'","",$err);
	echo "<!--".$sql."<br>returned the error<br>".$err."-->";
}

function db_error()
{
  //return ibase_errmsg();

}

function db_newseq($gen,$inc=1){
  $sql = "select gen_id(".$gen.",$inc) FROM rdb\$database";
  $rec = db_qry($sql);
  $row = db_row($rec);
  return $row[0];
}

function db_connect($config)
{
global $err;
  switch($config['dbtype']){
    case "pgsql":
      $conn = pg_connect("host=".$config[dbhost]." dbname=".$config[dbname]." user=".$config[dbuser]." password=".$config[dbpass]);
    break;

    case "mysql":
      $config['dbtype'] = $config['dbtype'];
    break;

    case "oracle":
      $config['dbtype'] = $config['dbtype'];
    break;

    default:
      $config['dbtype'] = "firebird";

      if($config['dbstring'] != ""){
        if($config['persist'] == 1){
          //$conn = ibase_pconnect("$config[dbstring]","$config[dbuser]","$config[dbpass]","",0,3,$config[dbrole]) or $err = 1;
          $conn = new \PDO("firebird:dbname=".$config['dbstring'],"$config[dbuser]","$config[dbpass]", [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]) or $err = 1;
        } else {
          //$conn = ibase_connect("$config[dbstring]","$config[dbuser]","$config[dbpass]","",0,3,$config[dbrole]) or $err = 1;
          $conn = new \PDO("firebird:dbname=".$config['dbstring'],"$config[dbuser]","$config[dbpass]", [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]) or $err = 1;
        }
        ini_set("ibase.timeformat","%H:%M");
        ini_set("ibase.timestampformat","%d %b %Y %H:%M:%S");
        ini_set("ibase.dateformat","%d %b %Y");

      } // End of if connection details have been provided
    break;
  } // End Switch Statement


  if($err != ""){	
    db_err($err,"Connect to (config['dbstring'])'".$config['dbstring']."' as (config[dbuser]:config[dbpass])'");
  } // End of error catcher
  return $conn;
}




function db_prep($sql){
global $conn;
global $config;
global $err;
$a = microtime();
	/* Prepares a query for later execution with db_exec 
	Usual calls: 
	$qry = db_prep("insert into blah(foo) values (?)");
	$qry = db_prep("update foo set blah = ?");
	$qry = db_prep("select blah, blurb from procedure(?) where blurb = ?");
  	$numargs = func_num_args();
  	if($numargs >= 1){
		for($a=0;$a<$numargs;$a++){
			$sql .= func_get_arg($a);
			if($a+1 < $numargs){$sql .= ",";}
		}
	}
	*/
	switch($config['dbtype']){
                case "pgsql":
                break;
                 
                case "mysql": 
                break; 

                case "oracle";
                break;

                default:
                case "firebird";
                case "interbase";
			$prep = $conn->prepare($sql,[PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
			//$prep = $conn->prepare($sql);
		break;
	}
return $prep;
}


function db_qry($sql)
{
global $config;
global $err;
global $tr;
global $conn;
$a = microtime();
error_reporting(1);
	/* ======================= U S A G E ========================
	Pass in an sql string on the $sql parameter, and you get a 
	result handle back.
	If there's a problem, an alert box tells you the reason
	========================================================== */
	switch($config['dbtype']){
                case "pgsql":
                break;

                case "mysql":
                break;

                case "oracle";
                break;

                default:
                case "firebird";
                case "interbase";
/* 
create table sys_mimick (
int_mimickid BIGINT not null,
dtm_stamp TIMESTAMP default 'now' not null,
str_query VARCHAR(2048),
primary key(int_mimickid)
);
*/
                if(substr_count(strtoupper($sql), "SELECT") == 0){
                  $null = $conn->query("insert into sys_mimick(int_mimickid, str_query) values (gen_id(gen_sys_mimick,1),'".str_replace("'","''",$sql)."')");
                }
                        if($tr){
			  $rec = $conn->query($tr,$sql);
                        } else {
			  $rec = $conn->query($sql);
                        }
                      
			//$err = ibase_errmsg();
		break;
	}
	if($err != ""){
		db_err($err,$sql);
	}
return $rec;
}


function db_tqry($tr,$sql='')
{
global $config;
global $err;
$a = microtime();
//error_reporting(1);
	/* ======================= U S A G E ========================
	Pass in a transaction handle from db_trans(options), and 
	an sql string on the $sql parameter, and you get a 
	result handle back.
	If there's a problem, an alert box tells you the reason
	========================================================== */
	switch($config['dbtype']){
                case "pgsql":
                break;

                case "mysql":
                break;

                case "oracle";
                break;

                default:
                case "firebird";
                case "interbase";
			$rec = $conn->query($tr,$sql);
			//$err = ibase_errmsg();
		break;
	}
	if($err != ""){
		db_err($err,"TQRY:".$sql);
	}
return $rec;
}

function db_obj($rec,$sql='',$t='')
{
global $err;
global $config;
$a = microtime();
	/* ===================== U S A G E ========================
	Pass in a result handle, and you get a pseudo-object of 
	the next record, accessable via $obj->FIELDNAME (in capitals)
	If a problems arises, an alert box tells you the reason
	======================================================== */
	switch($config['dbtype']){
		case "pgsql":
		break;

		case "mysql":
		break;

		case "oracle";
		break;

		default:
		case "firebird";
		case "interbase";
			$obj = $rec->fetch(\PDO::FETCH_OBJ);
			//$err = ibase_errmsg();
		break;
	}
	if($err != ""){
		if(substr_count("$err","deadlock") > 0){
			//ibase_rollback();
			echo "Deadlock occured. Please try the operation again";
		} else {
		db_err("OBJ:$err",$sql);
		$obj = null;
		}
	}
	
return $obj;
}


function db_row($rec,$sql="",$t=0)
{
global $err;
global $config;
$a = microtime();
	/* ===================== U S A G E ========================
	Pass in a result handle, and you get an associative array of 
	the next record, accessable via $obj[0] - $obj[n]
	If a problems arises, an alert box tells you the reason
	======================================================== */
	switch($config['dbtype']){
                case "pgsql":
                break;
                 
                case "mysql":
                break;

                case "oracle";
                break;

                default:
                case "firebird";
                case "interbase";
			$row = $rec->fetch(\PDO::FETCH_NUM);
			//$err = ibase_errmsg();
		break;
	}
	if($err != ""){
		db_err($err,$sql);
		$row = null;
	}
if($t){ $row[db_t] = db_perf($a);}
return $row;
}

function db_trans($args=''){
  switch($config['dbtype']){
    case "firebird":
    case "interbase":
      $tr = ibase_trans($args);
    break;
  }
  return $tr;
}

function db_commit($tr){
  switch($config['dbtype']){
    case "firebird":
    case "interbase":
      ibase_commit($tr);
    break;
  }
return $tr;
}

function db_rback($tr){
  switch($config['dbtype']){
    case "firebird":
    case "interbase":
      ibase_rollback($tr);
    break;
  }
}

class db_activegrid {

  // Public member names
  public $gridname;
  public $table;
  public $query;
  public $order;
  public $odirec;
  public $filters;

  // Private members names
  private $err = 0;
  private $errmsg = "<b>Error!</b><br>One or more errors were encountered.<hr>";

  // Method Declaration
  public function render() {
    // Check all variables before doing ajax call
    if($this->gridname == ""){
      $err = 1;
      $errmsg .= "'gridname' member is not defined<br>";
    }
    if($this->table == ""){
      $err = 1;
      $errmsg .= "'table' member is not defined<br>";
    }
    if($this->query == ""){
      if($this->table != ""){
        $this->query = "select * from ".$this->table;
      } else {
        $err = 1;
        $errmsg .= "'Query' and 'Table' members aren't defined<br>";
      }
    }
    // Start producing the div
    
    echo "<div class=activegrid id=\"".$this->gridname."\"><img src='/images/loading.gif' onLoad=\"
dateobj = new Date()
    stamp = dateobj.getYear()+'-'+dateobj.getMonth()+'-'+dateobj.getHours()+'-'+dateobj.getMinutes()+'-'+dateobj.getSeconds();
    
ActiveGridRequest('http://203.26.213.181/cm_pro/StudioX/activegrid.php?q=00001&s=%&ts='+ stamp +'-','".$this->gridname."');\">";
    if($err){
      echo $errmsg;
    } else {
      echo "AJAX goes here...yeah baby!<br>";
    } // end of div contents rendering
    echo "</div>";

  } // End of render function
} // End of livegrid class

function db_livegrid($rec,$width='',$link='',$titles=''){
  // Get the number of fields in this recordset
  $fc = ibase_num_fields($rec);
  $fnames = explode(",",$titles);
  $tc = count($fnames);
  // Echo out the headings
  if($width != ""){
    $output .= "\n<TABLE width='".$width."'>\n";
  } else {
    $output .= "\n<TABLE>\n";
  }
  $output .= " <TR>\n";
  for($a=0;$a<$fc;$a++){
    $ci=ibase_field_info($rec,$a);
    $fn[$a] = $ci['name'];
    switch($ci['type']){
      case "INTEGER":
        $al[$a] = " align=right";
      break;
      case "BIGINT":
        $al[$a] = " align=right";
      break;
      case "TIMESTAMP":
      case "TIME":
      case "DATE":
        $al[$a] = " align=right";
        break;
      default:
        $al[$a] = "";
      break;
    }
    $output .= "  <TD class=t".$al[$a].">";
    if($fnames[$a] != ""){
      $output .= "$fnames[$a]"."</TD>\n";
    } else {
      $output .= $ci['name']."</TD>\n";
    } // End of if Dialect array is passed in
  }
  $output .= " </TR>\n";
  $rows = 0;
  // Echo out the data lines
  while($row = db_row($rec)){
    if($link != ""){
      $tlink = str_replace("!0!",$row[0],$link);
      $tlink = str_replace("!1!",$row[1],$tlink);
      $tlink = str_replace("!2!",$row[2],$tlink);
      $tlink = str_replace("!3!",$row[3],$tlink);
      $tlink = str_replace("!4!",$row[4],$tlink);
      $tlink = str_replace("!5!",$row[5],$tlink);
      $tlink = str_replace("!6!",$row[6],$tlink);
      $tlink = str_replace("!7!",$row[7],$tlink);
      $output .= " <TR style=\"cursor:pointer;\" title=\"Link to '".$tlink."'\" onClick=\"window.location='".$tlink."';\">\n";
    } else {
      $output .= " <TR>\n";
    }
    
    $rows++;
    for($a=0;$a<$fc;$a++){
      if($rows%2 == 0){
        $cl = " class=even";
      } else {
        $cl = " class=odd";
      }
      $row[$a] = str_replace(".  ",".<BR>",$row[$a]);

      if(substr_count($fn[$a],"IBL_") > 0){
        if($row[$a]){
          $output .= "  <TD".$cl."><B>Yes</B></TD>\n";
        } else {
          $output .= "  <TD".$cl."><B>No</B></TD>\n";
        }
      } else {
        $output .= "  <TD".$cl.$al[$a].">";
        // We nullify any HTML
        $text = str_replace("<","&lt;",$row[$a]);
        $text = str_replace(">","&gt;",$text);
        if(strlen($text) > 40){
          $output .= substr($text,0,40)."...";
        } else {
          $output .= $text;
        }
        $output .= "</TD>\n";
      }
    }
    $output .= " </TR>\n";
  } // End of result output
    $output .= " <TR>\n  <TD class=footer colspan=".$fc.">Record Count: ".$rows."</TD>\n </TR></TABLE>";
  return $output;
}




function db_datagrid($rec,$width='',$link='',$titles=''){
  // Get the number of fields in this recordset
  $fc = $rec->columnCount();
  $lc = $fc*2;
  print_r($temp);
  $fnames = explode(",",$titles);
  $tc = count($fnames);
  // Echo out the headings
  if($width != ""){
    $output .= "\n<TABLE width='".$width."'>\n";
  } else {
    $output .= "\n<TABLE>\n";
  }
  $output .= "<thead class=dg_thead><TR>\n";
  for($a=0;$a<$fc;$a++){
    $totals[$a] = 0;
    $min[$a] = 0;
    $max[$a] = 0;
    /*
    $ci=ibase_field_info($rec,$a);
    $fn[$a] = $ci['name'];
    switch($ci['type']){
      case "INTEGER":
        $al[$a] = " right";
        $fmt="num";
      break;
      case "BIGINT":
        $al[$a] = " right";
        $fmt="num";
      break;
      case "TIMESTAMP":
      case "TIME":
      case "DATE":
        $al[$a] = " right";
        $fmt="num";
        break;
      default:
        $al[$a] = "";
      break;
    }
    $output .= "  <TD class='t".$al[$a]."'>";
    if($fnames[$a] != ""){
      $output .= $fnames[$a]."</TD>\n";
    } else {
      $output .= $ci['name']."</TD>\n";
    } // End of if Dialect array is passed in
     */
  }
  $output .= " </TR></thead>
<tbody class=dg_tbody>";
  $rows = 0;
  // Echo out the data lines
  while($row = $rec->fetch(PDO::FETCH_BOTH)){
    $keys = array_keys($row);
    //echo print_r($keys);
    if($rows == 0){
      $output .= "<tr>";
      for($a=0;$a<$lc;$a+=2){
        $output .= "<td class=tt>".$keys[$a]."</td>";
      }
      $output .= "</tr>";
    }
    if($link != ""){
      $tlink = str_replace("!0!",$row[0],$link);
      $tlink = str_replace("!1!",$row[1],$tlink);
      $tlink = str_replace("!2!",$row[2],$tlink);
      $tlink = str_replace("!3!",$row[3],$tlink);
      $tlink = str_replace("!4!",$row[4],$tlink);
      $tlink = str_replace("!5!",$row[5],$tlink);
      $tlink = str_replace("!6!",$row[6],$tlink);
      $tlink = str_replace("!7!",$row[7],$tlink);
      $output .= " <TR style=\"cursor:pointer;\" title=\"Link to '".$tlink."'\" onClick=\"window.location='".$tlink."';\">\n";
    } else {
      $output .= " <TR>\n";
    }
    
    for($a=0;$a<$fc;$a++){
      if(is_float($row[$a]) || is_numeric($row[$a]) || $fn[$a] == "SUM"){
        $_SERVER['logging'][] = "Adding amount '".$row[$a]."' to total[".$a."] '".$totals[$a]."'";
        $totals[$a] += (float)$row[$a];
        if($row[$a] < $min[$a] || $rows == 0){
          $min[$a] = $row[$a];
        }
        if($max[$a] < $row[$a] || $rows == 0){
          $max[$a] = $row[$a];
        }
      }
      if($rows%2 == 0){
        $cl = " class='even";
      } else {
        $cl = " class='odd";
      }
      //$row[$a] = str_replace(".  ",".<BR>",$row[$a]);

      if(substr_count($fn[$a],"IBL_") > 0){
        if($row[$a]){
          $output .= "  <TD ".$cl."'><B>Yes</B></TD>\n";
        } else {
          $output .= "  <TD ".$cl."'><B>No</B></TD>\n";
        }
      } else {
        $output .= "  <TD ".$cl.$al[$a]."'>";

        // We nullify any HTML
        $text = trim(str_replace("<","&lt;",$row[$a]));
        $text = str_replace(">","&gt;",$text);
        if(is_float($row[$a]) || $fn[$a] == "SUM"){
          $output .= number_format($row[$a],4);
        } else {
          if(strlen($text) > 100){
            $output .= substr($text,0,100)."...";
          } else {
            $output .= $text;
          }
        }
        $output .= "</TD>\n";
      }
    }
    $output .= " </TR>\n";
    $rows++;
  } // End of result output
  $output .= "</tbody>
<tfoot class=dg_tfoot><tr>";
    for($a=0;$a<$fc;$a++){
      $output .= "<TD class=dark>";
      if($totals[$a] > 0){
        $output .= "<small>
<span style=\"cursor:pointer;\" title='Sum: ".number_format($totals[$a],4)."'>Sum</span>|<span style=\"cursor:pointer;\" title='Average: ".number_format($totals[$a] / $rows,4)."'>Avg</span>|<span style=\"cursor:pointer;\" title='Min: ".number_format($min[$a],4)."'>Min</span>|<span style=\"cursor:pointer;\" title='Max: ".number_format($max[$a],4)."'>Max</span>

";
      }
      $output .= "</TD>";
    }
    $output .= "</tr>";
    $output .= " <TR>\n  <TD class=footer colspan=".$fc.">Record Count: ".$rows."</TD>\n </TR></tfoot>";
    $output .= "</table>";
echo $output;
    return $output;
}


function db_perf($start,$op=0)
{
global $config;
        $smicro = substr($start,2,8);
        $ssec = substr($start,11);
        $begin = "$ssec.$smicro";
 
        $finish = microtime();
        $fmicro = substr($finish,2,8);
        $fsec = substr($finish,11);
        $end = "$fsec.$fmicro";
        $perf = $end - $begin;
        $perf = substr($perf,0,6);
 
	if($op){
       		$diff[speed] = number_format($perf,4);
		$diff[ps] = number_format(1 / $perf,4);
		$diff[ph] = number_format(((1/$perf) * 60 * 60),0);
		$diff[pd] = number_format(((1/$perf) * 60 * 60 * 24),0);
	} else {
       		$diff = number_format($perf,4);
	}
        return $diff;
	
}


?>
