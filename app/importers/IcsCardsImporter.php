<?php

abstract class IcsCardsImporter implements ImporterInterface {
  const POST_DATE = 0;
  const BOOK_DATE = 1;
  const DESC = 2;
  const AMOUNT_FEX = 3;
  const AMOUNT_EUR = 4;
  const DETAIL = 5;
  const COLS = 6;

  static $str = [
    'date' => ['Datum'],
    'ics-client-id' => ['ICS-klantnummer'],
    'transaction' => ['transactie'],
    'booking' => ['boeking'],
    'credit' => ['Bij'],
    'debit' => ['Af'],
  ];
  static $cnames = [
    self::POST_DATE => ['Datum transactie'],
    self::BOOK_DATE => ['Datum boeking'],
    self::DESC => ['Omschrijving'],
    self::AMOUNT_FEX => ['Bedrag in vreemde valuta'],
    self::AMOUNT_EUR => ['Bedrag in euro'."'".'s'],
  ];
  static $details  = [
    self::POST_DATE => 'posted',
    self::BOOK_DATE => 'booked',
    self::DESC => 'desc',
    self::AMOUNT_FEX => 'amount-fex',
    self::AMOUNT_EUR => 'amount',
  ];
  static $mnames = [
    '01' => ['jan'],
    '02' => ['feb'],
    '03' => ['mrt'],
    '04' => ['apr'],
    '05' => ['mei'],
    '06' => ['jun'],
    '07' => ['jul'],
    '08' => ['aug'],
    '09' => ['sep'],
    '10' => ['okt'],
    '11' => ['nov'],
    '12' => ['dec'],
  ];
  static $data = [];

  static public function detect($f3,$name,$file) {
    if (!preg_match('/^Rekeningoverzicht/i',$name) && !preg_match('/\.pdf/i',$name)) {
      return FALSE;
    }
    $rows = self::read($file);
    if ($rows === FALSE) return FALSE;
    if (count($rows) == 0) return FALSE;
    self::$data = $rows;
    return TRUE;
  }

  static public function import($f3,$name,$file,$dacct=NULL) {
    if (is_null($dacct)) {
      $tm = $f3->get('IcsCardsImporter_default');
      if ($tm && defined($tm)) $tm = constant($tm);
      $dacct = $tm;
    }

    $rows = [];
    foreach (self::$data as $dat) {
      $row = [];
      for ($i=0; $i< CN_NCOLS;$i++) {
        $row[$i] = '';
      }
      $row[CN_ACCOUNT] = $dacct;
      $row[CN_DATE] = $dat[self::POST_DATE];
      $row[CN_XID] = sprintf('%u',crc32($dat[self::POST_DATE].$dat[self::BOOK_DATE].
					$dat[self::AMOUNT_EUR].$dat[self::DESC]));

      $row[CN_DESCRIPTION] = $dat[self::DESC];
      $row[CN_AMOUNT] =  $dat[self::AMOUNT_EUR];
      $row[CN_TEXT] = $dat[self::DESC];
      if (!empty($dat[self::AMOUNT_FEX])) $row[CN_TEXT] .= $dat[self::AMOUNT_FEX] . PHP_EOL;
      if (!empty($dat[self::DETAIL])) $row[CN_TEXT] .= $dat[self::DETAIL] . PHP_EOL;

      foreach (self::$details as $c => $k) {
	if (empty($dat[$c])) continue;
	$row[CN_DETAIL] .= $k . ': '.str_replace("\n","|",trim($dat[$c])) . PHP_EOL;
      }
      $rows[] = $row;
    }
    return $rows;
  }

  public static function fixamt(&$eur) {
    if (!preg_match('/^(\S+)\s+(\S+)$/', $eur, $mv)) return FALSE;
    $qty = CNum::parse($mv[1]);
    if (in_array($mv[2],self::$str['debit'])) {
      $qty = -$qty;
    } elseif (!in_array($mv[2],self::$str['credit'])) {
      return FALSE;
    }
    $eur = $qty;
    return TRUE;
  }
  public static function fixdate(&$date,$year) {
    if (!preg_match('/^(\d+)\s+(\S+)$/',$date,$mv)) return FALSE;

    $mon = NULL;

    foreach (self::$mnames as $i=>$j) {
      if (in_array($mv[2],$j)) {
	$mon = $i;
	break;
      }
    }
    if (is_null($mon)) return FALSE;
    $date = $year .'-'.$mon.'-'. (strlen($mv[1]) == 1 ? '0' : ''). $mv[1];
    return TRUE;
  }
  public static function read($pdfname) {
    try {
      // Parse pdf file using Parser library
      $parser = new \Smalot\PdfParser\Parser();
      $pdf = $parser->parseFile($pdfname);
    } catch (Exception $e) {
      return FALSE;
    }
    //~ $metadata = $pdf->getDetails();
    //~ print_r($metadata);
    // Extract text from PDF
    //~ $textContent = $pdf->getText();
    //~ echo $textContent;
    //~ echo PHP_EOL;
    //~ echo '==========================='.PHP_EOL;
    $res = [];
    foreach ($pdf->getPages() as $pg) {
      $tab = [];
      $data = $pg->getDataTm();
      foreach ($data as $seg) {
	list($tm,$txt) = $seg;
	if (empty($txt)) continue;
	list(,,,,$x,$y) = $tm;
	if (!isset($tab[$y])) $tab[$y] = [];
	$tab[$y][$x] = trim($txt);
      }
      krsort($tab);
      foreach (array_keys($tab) as $i) {
	ksort($tab[$i]);
      }
    }
    $year = NULL;
    $map = [];
    $hdr = 2;

    foreach ($tab as $row) {
      if (is_null($year)) {
	if (count($row) < 4) continue;
	$cc = [];
	foreach ($row as $j) {
	  $cc[] = $j;
	}
	if (in_array($cc[0],self::$str['date']) && in_array($cc[1],self::$str['ics-client-id'])) {
	  $year = 0;
	}
      } elseif ($year == 0) {
	if (count($row) < 4) continue;
	foreach ($row as $j) {
	  $cc = $j;
	  break;
	}
	//~ echo $cc .PHP_EOL;
	if (preg_match('/^\d+\s+\S+\s+(\d+)$/',$cc,$mv)) $year = $mv[1];
      } elseif ($hdr) {
	if (count($row) < 4) continue;
	$cc = [];
	foreach ($row as $j) {
	  $cc[] = $j;
	}
	if ($hdr == 2) {
	  if (in_array($cc[0],self::$str['date']) && in_array($cc[1],self::$str['date'])) {
	    foreach ($row as $i=>$j) {
	      $map[$i] = $j;
	    }
	    $hdr--;
	  }
	} elseif ($hdr == 1) {
	  if (in_array($cc[0],self::$str['transaction']) && in_array($cc[1],self::$str['booking'])) {
	    foreach ($row as $i=>$j) {
	      if (isset($map[$i])) {
		$map[$i] .= ' ' .$j;
	      } else {
		$map[$i] = $j;
	      }
	    }
	    $hdr--;
	  }
	  # Tweak map...
	  $tt  = [];
	  foreach ($map as $i=>$j) {
	    $t = $j;
	    foreach (self::$cnames as $s=>$tab) {
	      if (in_array($j,$tab)) {
		$t = $s;
		break;
	      }
	    }
	    $tt[] = [ intval($i/10), $i,$t ];
	  }
	  $map = $tt;
	  //~ print_r($map);
	}
      } else {
	$mrow = [];
	foreach ($row as $i=>$j) {
	  $p = intval($i/10);
	  for ($k = count($map)-1;$k >= 0; --$k) {
	    list($cp,$cx,$cn) = $map[$k];
	    if ($p >= $cp) {
	      if (isset($mrow[$cn])) {
		$mrow[$cn] .= "\t" . $j;
	      } else {
		$mrow[$cn] = $j;
	      }
	      break;
	    }
	  }
	}
	if ((count($mrow) == 5 || count($mrow) == 4) &&
		isset($mrow[self::POST_DATE]) &&
		isset($mrow[self::BOOK_DATE]) &&
		isset($mrow[self::DESC]) &&
		isset($mrow[self::AMOUNT_EUR]) &&
		self::fixdate($mrow[self::POST_DATE],$year) &&
		self::fixdate($mrow[self::BOOK_DATE],$year) &&
		self::fixamt($mrow[self::AMOUNT_EUR])) {
	  //
	  for ($i=0; $i < self::COLS ; ++$i) {
	    if (!isset($mrow[$i])) $mrow[$i] = '';
	  }
	  $res[] = $mrow;
	} elseif (count($mrow) == 1 && isset($mrow[self::DESC]) && count($res) > 0) {
	  $res[count($res)-1][self::DETAIL] = $mrow[self::DESC];
	}
      }
    }
    return $res;
  }
}

//~ $cmd = array_shift($argv);
//~ foreach ($argv as $pdfname) {
  //~ $res = PdfReader::read($pdfname);
  //~ print_r($res);
//~ }



