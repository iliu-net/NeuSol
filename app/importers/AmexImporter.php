<?php
use \ParseCsv\Csv as parseCSV;

abstract class AmexImporter implements ImporterInterface {

  static public function detect($f3,$name,$file) {
    if (!preg_match('/^ofx/i',$name) && !preg_match('/\.csv/i',$name)) {
      return FALSE;
    }
    $fp = @fopen($file,"r");
    if ($fp === FALSE) return FALSE;
    $line = fgets($fp);
    fclose($fp);
    if ($line === FALSE) return FALSE;
    return preg_match('/^\d\d-\d\d-\d\d,"[^"]*","/',$line);
  }

  static public function import($f3,$name,$file,$dacct=NULL) {
    $csvdata = new parseCSV();
    $csvdata->heading = false;
    $csvdata->parseFile($file);

    $cols = [
	'DATE','REFERENCE','AMOUNT','DESC1','DESC2','NCOLS'
    ];
    $k=0;
    foreach ($cols as $j) {
	eval('$IC_'.$j.'='.($k++).';');
    }
    unset($j,$k);

    if (is_null($dacct)) $dacct = '';

    $rows = [];
    foreach ($csvdata->data as $dat) {
	$row = [];
	for ($i=0; $i< CN_NCOLS;$i++) {
	   $row[$i] = '';
	}
	$row[CN_ACCOUNT] = $dacct;
	$row[CN_DATE] = '20'.substr($dat[$IC_DATE],6,2).'-'.substr($dat[$IC_DATE],3,2).'-'.substr($dat[$IC_DATE],0,2);
	if ($dat[$IC_REFERENCE] != '') {
	   $row[CN_XID] = sprintf('%u',crc32($dat[$IC_REFERENCE]));
	} else {
	   $row[CN_XID] = sprintf('%u',crc32($dat[$IC_DATE].$dat[$IC_AMOUNT].
					     $dat[$IC_DESC1].$dat[$IC_DESC2]));
	}
	$row[CN_AMOUNT] =  -(float)str_replace(",",".",trim($dat[$IC_AMOUNT]));
	$row[CN_TEXT] = $dat[$IC_REFERENCE]."\n".$dat[$IC_DESC1]."\n".$dat[$IC_DESC2];
	$row[CN_DESCRIPTION] = $dat[$IC_DESC1];
	$rows[] = $row;
    }
    return $rows;
  }
  static public function SW_ExplodeCSV($csv, $headerrow=true, $mode='EXCEL', $fmt='2D_FIELDNAME_ARRAY') {
      // SW_ExplodeCSV - parses CSV into 2D array(MS Excel .CSV supported)
      // AUTHOR: tgearin2@gmail.com
      // RELEASED: 9/21/13 BETA
      //SWMessage("SW_ExplodeCSV() - CALLED HERE -");
      // REFERENCE:
      // http://stackoverflow.com/questions/9139202/how-to-parse-a-csv-file-using-php
      $rows=array(); $row=array(); $fields=array();// rows = array of arrays

      //escape code = '\'
      $escapes=array('\r', '\n', '\t', '\\', '\"');  //two byte escape codes
      $escapes2=array("\r", "\n", "\t", "\\", "\""); //actual code

      if($mode=='EXCEL')
      {// escape code = ""
	 $delim=','; $enclos='"'; $esc_enclos='""'; $rowbr="\r\n";
      }
      else //mode=STANDARD
      {// all fields enclosed
	 $delim=','; $enclos='"'; $rowbr="\r\n";
      }

      $indxf=0; $indxl=0; $encindxf=0; $encindxl=0; $enc=0; $enc1=0; $enc2=0; $brk1=0; $rowindxf=0; $rowindxl=0; $encflg=0;
      $rowcnt=0; $colcnt=0; $rowflg=0; $colflg=0; $cell="";
      $headerflg=0; $quotedflg=0;
      $i=0; $i2=0; $imax=strlen($csv);

      while($indxf < $imax)
      {
	 //find first *possible* cell delimiters
	 $indxl=strpos($csv, $delim, $indxf);  if($indxl===false) { $indxl=$imax; }
	 $encindxf=strpos($csv, $enclos, $indxf); if($encindxf===false) { $encindxf=$imax; }//first open quote
	 $rowindxl=strpos($csv, $rowbr, $indxf); if($rowindxl===false) { $rowindxl=$imax; }

	 if(($encindxf>$indxl)||($encindxf>$rowindxl))
	 { $quoteflg=0; $encindxf=$imax; $encindxl=$imax;
	    if($rowindxl<$indxl) { $indxl=$rowindxl; $rowflg=1; }
	 }
	 else
	 { //find cell enclosure area (and real cell delimiter)
	    $quoteflg=1;
	    $enc=$encindxf;
	    while($enc<$indxl) //$enc = next open quote
	    {// loop till unquoted delim. is found
	       $enc=strpos($csv, $enclos, $enc+1); if($enc===false) { $enc=$imax; }//close quote
	       $encindxl=$enc; //last close quote
	       $indxl=strpos($csv, $delim, $enc+1); if($indxl===false)  { $indxl=$imax; }//last delim.
	       $enc=strpos($csv, $enclos, $enc+1); if($enc===false) { $enc=$imax; }//open quote
	       if(($indxl==$imax)||($enc==$imax)) break;
	    }
	    $rowindxl=@strpos($csv, $rowbr, $enc+1); if($rowindxl===false) { $rowindxl=$imax; }
	    if($rowindxl<$indxl) { $indxl=$rowindxl; $rowflg=1; }
	 }

	 if($quoteflg==0)
	 { //no enclosured content - take as is
	    $colflg=1;
	    //get cell
	    // $cell=substr($csv, $indxf, ($indxl-$indxf)-1);
	    $cell=substr($csv, $indxf, ($indxl-$indxf));
	 }
	 else// if($rowindxl > $encindxf)
	 { // cell enclosed
	    $colflg=1;

	    //get cell - decode cell content
	    $cell=substr($csv, $encindxf+1, ($encindxl-$encindxf)-1);

	    if($mode=='EXCEL') //remove EXCEL 2quote escapes
	    { $cell=str_replace($esc_enclos, $enclos, $cell);
	    }
	    else //remove STANDARD esc. sceme
	    { $cell=str_replace($escapes, $escapes2, $cell);
	    }
	 }

	 if($colflg)
	 {// read cell into array
	    if( ($fmt=='2D_FIELDNAME_ARRAY') && ($headerflg==1) )
	    { $row[$fields[$colcnt]]=$cell; }
	    else if(($fmt=='2D_NUMBERED_ARRAY')||($headerflg==0))
	    { $row[$colcnt]=$cell; } //$rows[$rowcnt][$colcnt] = $cell;

	    $colcnt++; $colflg=0; $cell="";
	    $indxf=$indxl+1;//strlen($delim);
	 }
	 if($rowflg)
	 {// read row into big array
	    if(($headerrow) && ($headerflg==0))
	    {  $fields=$row;
	       $row=array();
	       $headerflg=1;
	    }
	    else
	    { $rows[$rowcnt]=$row;
	       $row=array();
	       $rowcnt++;
	    }
	    $colcnt=0; $rowflg=0; $cell="";
	    $rowindxf=$rowindxl+2;//strlen($rowbr);
	    $indxf=$rowindxf;
	 }

	 $i++;
	 //SWMessage("SW_ExplodeCSV() - colcnt = ".$colcnt."   rowcnt = ".$rowcnt."   indxf = ".$indxf."   indxl = ".$indxl."   rowindxf = ".$rowindxf);
	 //if($i>20) break;
      }

      return $rows;
  }
}
