<?php
abstract class AmexImporterV3 implements ImporterInterface {
  static $xlsdat;

  const DATE = 0;
  const DESC = 1;
  const USER = 2;
  const ACCOUNT = 3;
  const AMOUNT = 4;
  const DETAIL = 5;
  const OVERVIEW = 6;
  const ADDRESS = 7;
  const PLACE = 8;
  const ZIPCODE = 9;
  const COUNTRY = 10;
  const REFERENCE = 11;
  const CATEGORY = 12;
  const COLS = 13;
  public static $details = [
    self::DATE => 'date',
    self::DESC => 'description',
    self::USER => 'user',
    self::ACCOUNT => 'acct',
    self::AMOUNT => 'amount',
    self::DETAIL => 'detail',
    self::OVERVIEW => 'overview',
    self::ADDRESS => 'address',
    self::PLACE => 'place',
    self::ZIPCODE => 'zipcode',
    self::COUNTRY => 'country',
    self::REFERENCE => 'ref',
    self::CATEGORY => 'cat',
  ];
  public static $mapping = [
    'Datum' => self::DATE,
    'Omschrijving' => self::DESC,
    'Kaartlid' => self::USER,
    'Rekening #' => self::ACCOUNT,
    'Bedrag' => self::AMOUNT,
    'Aanvullende informatie' => self::DETAIL,
    'Vermeld op uw rekeningoverzicht als' => self::OVERVIEW,
    'Adres' => self::ADDRESS,
    'Plaats' => self::PLACE,
    'Postcode' => self::ZIPCODE,
    'Land' => self::COUNTRY,
    'Referentie' => self::REFERENCE,
    'Categorie' => self::CATEGORY,
  ];
  public static $str_date = [ 'Datum' ];

  static public function detect($f3,$name,$file) {
    if (!preg_match('/^activiteit/i',$name) && !preg_match('/\.xlsx/i',$name)) {
	return FALSE;
    }

    try {
      $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
      $spreadsheet = $reader->load($file);
      $ws = $spreadsheet->getActiveSheet();
    } catch (Exception $e) {
      return FALSE;
    }

    $res = [];
    $map = [];
    foreach ($ws->getRowIterator() as $row) {
      $ci = $row->getCellIterator();
      if (count($map) == 0) {
	if (in_array($ci->current()->getValue(), self::$str_date)) {
	  foreach ($ci as $cell) {
	    $map[] = self::$mapping[$cell->getValue()] ?? false;
	  }
	}
      } else {
	$i = 0;
	$rdat = [];
	foreach ($ci as $cell) {
	  $rdat[$map[$i]] = $cell->getValue();
	  $i++;
	}
	for ($i=0;$i<self::COLS;++$i) {
	  if (!isset($rdat[$i])) $rdat[$i] = '';
	}
	$res[] = $rdat;
      }
    }
    if (count($res) == 0) return FALSE;

    self::$xlsdat = $res;
    return TRUE;
  }
  static public function guessAccount($f3,$dacct) {
    if (!is_null($dacct)) return $dacct;

    foreach (['AmexImporterV3_default', 'AmexImporterV2_default',
	      'AmexImporter_default'] as $i) {
      $tm = $f3->get($i);
      if ($tm && defined($tm)) return constant($tm);
    }
    return NULL;
  }

  static public function import($f3,$name,$file,$dacct=NULL) {
    $dacct = self::guessAccount($f3,$dacct);
    $rows = [];
    foreach (self::$xlsdat as $dat) {
      $row = [];
      for ($i=0; $i< CN_NCOLS;$i++) {
        $row[$i] = '';
      }
      $row[CN_ACCOUNT] = $dacct;
      $row[CN_DATE] = substr($dat[self::DATE],6,4).'-'.substr($dat[self::DATE],0,2).'-'.substr($dat[self::DATE],3,2);
      if (!empty($dat[self::REFERENCE])) {
	$row[CN_XID] = sprintf('%u',crc32($dat[self::REFERENCE]));
      } else {
	$row[CN_XID] = sprintf('%u',crc32($dat[self::DATE].$dat[self::AMOUNT].
					  $dat[self::DESC].$dat[self::ACCOUNT]));
      }
      $row[CN_DESCRIPTION] = str_replace("\n","|",trim($dat[self::DESC]));
      $row[CN_AMOUNT] =  -(float)$dat[self::AMOUNT];
      $row[CN_TEXT] = $dat[self::USER] . ': '. $dat[self::OVERVIEW] . PHP_EOL;
      if (!empty($dat[self::DETAIL])) $row[CN_TEXT] .= $dat[self::DETAIL] . PHP_EOL;
      if (!empty($dat[self::CATEGORY])) $row[CN_TEXT] .= $dat[self::CATEGORY] . PHP_EOL;
      if (!empty($dat[self::ADDRESS])) $row[CN_TEXT] .= $dat[self::ADDRESS] . PHP_EOL;
      foreach (self::$details as $c => $k) {
	if (empty($dat[$c])) continue;
	$row[CN_DETAIL] .= $k . ': '.str_replace("\n","|",trim($dat[$c])) . PHP_EOL;
      }
      $rows[] = $row;
    }
    return $rows;
  }
}
