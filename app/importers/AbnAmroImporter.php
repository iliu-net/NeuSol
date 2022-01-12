<?php

abstract class AbnAmroImporter implements ImporterInterface {

  static public function detect($f3,$name,$file) {
    if (!preg_match('/\.tab$/i',$name)) return FALSE;
    $fp = @fopen($file,"r");
    if ($fp === FALSE) return FALSE;
    $line = fgets($fp);
    fclose($fp);
    if ($line === FALSE) return FALSE;
    return preg_match('/^\d+\t[A-Z][A-Z][A-Z]\t\d\d\d\d\d\d\d\d\t/',$line);
  }

  static public function parse_text($input) {
   $lines = [];

   while ($input != "") {
      foreach ([33,32] as $mark) {
	 $lines[] = substr($input,0,$mark);
	 $input = substr($input,$mark);
      }
   }
   return $lines;
  }

  static public function import($f3,$name,$file,$dacct=NULL) {
   $fp = @fopen($file,"r");
   if ($fp === false) return [];
   $cols = [
      'ACCOUNT','CURRENCY','DATE','PREVBAL','BALANCE','POSTDATE','AMOUNT','TEXT',
      'NCOLS'
   ];
   $k=0;
   foreach ($cols as $j) {
      eval('$IC_'.$j.'='.($k++).';');
   }
   unset($j,$k);
   $numbers = $f3->get('accounts_numbers');

   $rows = [];
   while (FALSE !== ($line = fgets($fp))) {
      $dat = explode("\t",trim($line));
      $row = [];
      for ($i =0; $i < CN_NCOLS;$i++) {
	 $row[$i] = '';
      }
      $row[CN_ACCOUNT] = is_null($dacct) ? (isset($numbers[$dat[$IC_ACCOUNT]]) ? $numbers[$dat[$IC_ACCOUNT]] : '') : $dacct;
      $row[CN_DATE] = substr($dat[$IC_POSTDATE],0,4).'-'.substr($dat[$IC_POSTDATE],4,2).'-'.
	 substr($dat[$IC_POSTDATE],6,2);
      $row[CN_XID] = sprintf('%u',crc32($dat[$IC_TEXT]));
      $row[CN_AMOUNT] = (float)str_replace(",",".",$dat[$IC_AMOUNT]);
      $row[CN_TEXT] = $dat[$IC_TEXT];

      // Also make available all the raw input...
      $i = 0;
      foreach ($cols as $j) {
	 if ($i == $IC_NCOLS) break;
	 $row[$j] = $dat[$i++];
      }
      if (substr($dat[$IC_TEXT],0,1) == '/') {
	 $row['AMRO_TYPE'] = 'TRTP';
	 $l = explode("/",substr($dat[$IC_TEXT],1));

	 $last = FALSE;
	 while (count($l)) {
	    $c = array_shift($l);
	    if (strlen($c) == 4 || $c == "BIC") {
	       $last = $c;
	       $row[$last] = '';
	       //$xx[] = $last; //DEBUG
	    } else {
	       if ($last) $row[$last] .= $c;
	    }
	 }

	 if ($row['TRTP'] == 'iDEAL') {
	    $row[CN_DESCRIPTION] = $row['REMI'];
	 } else {
	    $row[CN_DESCRIPTION] = $row['NAME'];
	 }
	 //echo str_replace("/","\n",$dat[$IC_TEXT])."\n====\n";
	 /*
	 foreach($xx as $y) {
	    echo $y."=> ".$row[$y]."\n";
	    }
	    echo "=====\n";*/
	 //print_r($row);
      } elseif (strlen($dat[$IC_TEXT]) < 33) {
	 $row['AMRO_TYPE'] = 'SHORT';
	 $row[CN_DESCRIPTION] = $dat[$IC_TEXT];
      } else {
	 $l = self::parse_text($dat[$IC_TEXT]);
	 $row[CN_TEXT]= implode("\n",array_map('trim',$l));
	 if (preg_match('/^BEA\s+/',$l[0])) {
	    $row['AMRO_TYPE'] = 'BEA';
	    $row[CN_DESCRIPTION] = $l[1];

	    list($id,$txt) = preg_split('/\s+/',preg_replace('/^BEA\s+NR:/','',$l[0]));
	    $row['PAYEE_ID'] = 'BEA/'.$id;
	    $row['DETAIL'] = $txt;
	    if (preg_match('/^(.+),PAS(\d+)$/',trim($l[1]),$mv)) {
	       $row['PAYEE'] = $mv[1];
	       $row['PAS'] = $mv[2];
	    } else {
	       $row['PAYEE'] = $l[1];
	       $row['PAS'] = '';
	    }
	 } elseif (preg_match('/^GEA\s+/',$l[0])) {
	    $row['AMRO_TYPE'] = 'GEA';
	    $row[CN_DESCRIPTION] = 'ATM:'.$l[1];

	    list($id,$txt) = preg_split('/\s+/',preg_replace('/^GEA\s+NR:/','',$l[0]),2);
	    if (preg_match('/^(.+),PAS(\d+)$/',trim($l[1]),$mv)) {
	       list(,$payee,$pas) = $mv;
	    } else {
	       $payee = $l[1];
	       $pas = 'cash';
	    }
	    $row['PAYEE_ID'] = $pas;
	    $row['DETAIL'] = implode('/',[$payee,$pas,$txt]);
	 } elseif (preg_match('/^SEPA\s+/',$l[0])) {
	    $row['AMRO_TYPE'] = 'SEPA';
	    //echo implode("|\n",$l)."\n=====\n";
	    $row['TRTP'] = trim(array_shift($l));
	    $last = FALSE;
	    while (count($l)) {
	       $c = array_shift($l);
	       if (preg_match('/^([A-Z][A-Za-z]+)\.?:\s(.+)$/',$c,$mv)) {
		  $last = $mv[1];
		  $row[$last] = $mv[2];
		  //$xx[$last] = $last; //DEBUG
	       } else {
		  if ($last) $row[$last] .= $c;
	       }
	    }
	    if ($row['TRTP'] == 'SEPA iDEAL') {
	       $desc = $row['Omschrijving'];
	       $desc = preg_replace('/\s+Betalingskenmerk:\s+\d+\s*/',' ',$desc);
	       if (preg_match('/^[ 0-9]+$/',$desc)) {
		  $row[CN_DESCRIPTION] = $row['Naam'];
	       } else {
		  $row[CN_DESCRIPTION] = preg_replace('/^[ 0-9]+/','',$desc);
	       }
	    } else {
	       $row[CN_DESCRIPTION] = $row['Naam'];
	    }
	    //foreach($xx as $y) {
	    //echo $y."=> ".$row[$y]."\n";
	    //}
	    //echo "=====\n";
	    //}
	 } else {
	    $row['AMRO_TYPE'] = 'LONG';
	    $row[CN_DESCRIPTION] = trim($l[0]).' '.trim($l[1]);
	 }
      }
      // Generate encoded markedup data...
      $dat = $row;
      for ($i=0;$i < CN_NCOLS;$i++) unset($dat[$i]);
      $q = "";
      foreach ($dat as $j=>$k) {
	 $row[CN_DETAIL] .= $q.$j.": ".$k;
	 $q = "\n";
      }

      $rows[] = $row;
   }
   return $rows;
  }


}
