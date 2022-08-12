<?php
require_once('lib/parsecsv-for-php/parsecsv.lib.php');

# Header
# Datum,Omschrijving,Kaartlid,Rekening #,Bedrag,Aanvullende informatie,Vermeld op uw rekeningoverzicht als,Adres,Plaats,Postcode,Land,Referentie


abstract class AmexImporterV2 implements ImporterInterface {

   static public function detect($f3,$name,$file) {
     if (!preg_match('/^activity/i',$name) && !preg_match('/\.csv/i',$name)) {
	return FALSE;
     }
     $fp = @fopen($file,"r");
     if ($fp === FALSE) return FALSE;
     $line = fgets($fp);
     fclose($fp);
     if ($line === FALSE) return FALSE;
     return preg_match('/^Datum,Omsc/',$line);
  }

  static public function import($f3,$name,$file,$dacct=NULL) {
    $csvdata = new parseCSV();
    $csvdata->heading = true;
    $csvdata->parseFile($file);

    //     echo "DACCT: $dacct\n";
    if (is_null($dacct)) {
	// echo "GUESS ACCOUNT $name\n";
	if (preg_match('/^activity/i',$name)) {
	  $tm = $f3->get('AmexImporterV2_default');
	  if ($tm && defined($tm)) $tm = constant($tm);
	  $dacct = $tm;
	} else {
	  $dacct = '';
	}
     }

     $rows = [];
     foreach ($csvdata->data as $dat) {
	$row = [];
	for ($i=0; $i< CN_NCOLS;$i++) {
	   $row[$i] = '';
	}
	$row[CN_ACCOUNT] = $dacct;

	$row[CN_DATE] = substr($dat['Datum'],6,4).'-'.substr($dat['Datum'],0,2).'-'.substr($dat['Datum'],3,2);
	if ($dat['Referentie'] != '') {
	   $row[CN_XID] = sprintf('%u',crc32($dat['Referentie']));
	} else {
	   $row[CN_XID] = sprintf('%u',crc32($dat['Datum'].$dat['Bedrag'].
					     $dat['Omschrijving'].$dat['Rekening #']));
	}
	$row[CN_DESCRIPTION] = str_replace("\n","|",trim($dat['Omschrijving']));
	$row[CN_AMOUNT] =  -(float)str_replace(",",".",trim($dat['Bedrag']));
	$row[CN_TEXT] = $dat['Kaartlid']."\n"
			. $dat['Vermeld op uw rekeningoverzicht als']."\n"
			. $dat['Adres'];
	foreach ($dat as $k => $v) {
	  $row[CN_DETAIL] .= $k . ': ' . str_replace("\n","|",trim($v)) . "\n";
	}

	$rows[] = $row;
     }
     return $rows;
   }
}
