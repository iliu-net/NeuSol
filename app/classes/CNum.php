<?php
#****c* classes/CNum
# NAME
#   CNum -- Number conversion utilities
#******
abstract class CNum {
  #****m* CNum/fmt
  # NAME
  #   fmt -- Format number
  # SYNOPSIS
  #   $html = CNum::fmt($num,$dec,$style)
  # FUNCTION
  #   Applies some simple styling/formatting to numbers.
  #   It will add ","'s for thousand separators and set the
  #   decimals to $dec.
  #   If the number is negative it will set the style to color:red
  #   other wise it will use the given $style.  (This is optional).
  # INPUTS
  #   $num - number to format
  #
  #   $style - optional styling string.
  # RESULTS
  #   Formatted HTML.
  #******
  static public function fmt($num,$dec=2,$style=NULL) {
    if ($num < 0)
      return '<span style="color:red">'.number_format($num,$dec).'</span>';
    else if ($style === NULL)
      return number_format($num,$dec);
    else
      return '<span style="'.$style.'">'.number_format($num,$dec).'</span>';
  }
  #****m* CNum/parse
  # NAME
  #   parse -- Parse numbers
  # SYNOPSIS
  #   $num = CNum::parse($num)
  # FUNCTION
  #   Will parse the number given in $num.  It will try to figure
  #   out what to do with ,'s and .'s and smartly figures out
  #   what is being used for thousand or decimal separators.
  # INPUTS
  #   $num - number to parse
  # RESULTS
  #   Parsed number with "." for decimal separator and no thousand
  #   separators.
  #******
  static public function parse($num) {
    //~ echo 'INPUT '.$num.' : ';
    $num = trim($num);
    if ($num == '') return $num;

    if (substr($num,0,1) == '-') {
      $sign = '-';
      $num = trim(substr($num,1));
    } else {
      $sign = '';
    }
    $nd = preg_replace('/[0-9]+/','',$num);
    if ($nd == '') return $sign.$num; /* Found only digits */
    if (preg_replace('/[\.,]*/', '',$nd) != '') return $sign.$num; /* Found weird characters */

    if (preg_match('/^\.+,$/',$nd)) return $sign.strtr($num,['.'=>'', ','=>'.']);
    if (preg_match('/^,+\.$/',$nd)) return $sign.strtr($num,[','=>'']);
    if (preg_match('/^,,+$/',$nd)) return $sign.strtr($num,[','=>'']);
    if (preg_match('/^\.\.+$/',$nd)) return $sign.strtr($num,['.'=>'']);

    // Count digits to the right
    $i = strpos($num,$nd);
    if ($i === False) return $sign.$num; // Give up if this happens
    if ($i > 3 || strlen($num)-$i-1 != 3) { // It is a fraction separator
      if ($nd == ',') return $sign.strtr($num,[','=>'.']);
      return $sign.$num;
    }
    // OK, then we don't know, so we assume '.' is fraction
    // and comma is thousands...
    if ($nd == ',')  return $sign.strtr($num,[','=>'']);
    return $sign.$num;
  }
  #****m* CNum/human_filesize
  # NAME
  #   human_filesize -- format number as readable file sizes
  # SYNOPSIS
  #   $num = CNum::human_filesize($bytes,$dec)
  # INPUTS
  #   $bytes -- size in bytes
  #   $dec -- number of decimals to print
  # RESULTS
  #   Formatted file size
  #******
  static public function human_filesize($bytes) {
    $i = floor(log($bytes) / log(1024));
    $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    return sprintf('%.02F', $bytes / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
  }
}

//~ echo CNum::human_filesize(1000).PHP_EOL;
//~ echo CNum::human_filesize(2000).PHP_EOL;
//~ echo CNum::human_filesize(9874321).PHP_EOL;
//~ echo CNum::human_filesize("10000000000").PHP_EOL;
//~ echo CNum::human_filesize(712893712304234).PHP_EOL;
//~ echo CNum::human_filesize(6212893712323224).PHP_EOL;
//~ echo CNum::human_filesize(14590).PHP_EOL;
//~ echo CNum::human_filesize(145903).PHP_EOL;
//~ echo CNum::human_filesize(145900).PHP_EOL;
//~ echo CNum::human_filesize(1459035).PHP_EOL;

//~ $x= CNum::parse('139533'); var_dump($x);
//~ $x= CNum::parse('1akab9445la'); var_dump($x);

//~ $x= CNum::parse('1,395.33');  var_dump($x);
//~ $x= CNum::parse('1,405,395.33');  var_dump($x);
//~ $x= CNum::parse('1.395,33');  var_dump($x);
//~ $x= CNum::parse('1.405.395,33');  var_dump($x);

//~ $x= CNum::parse('1,405,395');  var_dump($x);
//~ $x= CNum::parse('1.405.395');  var_dump($x);

//~ $x= CNum::parse('1.2');  var_dump($x);
//~ $x= CNum::parse('1,2');  var_dump($x);
//~ $x= CNum::parse('1.000');  var_dump($x);
//~ $x= CNum::parse('1,000');  var_dump($x);
//~ $x= CNum::parse('3000,000');  var_dump($x);
//~ $x= CNum::parse('4.000');  var_dump($x);
//~ $x= CNum::parse('50000.00');  var_dump($x);
//~ $x= CNum::parse('54985043.000');  var_dump($x);
//~ $x= CNum::parse('304580345,456');  var_dump($x);

//~ $x= CNum::parse('-139533'); var_dump($x);
//~ $x= CNum::parse('-1akab9445la'); var_dump($x);

//~ $x= CNum::parse('-1,395.33');  var_dump($x);
//~ $x= CNum::parse('-1,405,395.33');  var_dump($x);
//~ $x= CNum::parse('-1.395,33');  var_dump($x);
//~ $x= CNum::parse('-1.405.395,33');  var_dump($x);

//~ $x= CNum::parse('-1,405,395');  var_dump($x);
//~ $x= CNum::parse('-1.405.395');  var_dump($x);

//~ $x= CNum::parse('-1.2');  var_dump($x);
//~ $x= CNum::parse('-1,2');  var_dump($x);
//~ $x= CNum::parse('-1.000');  var_dump($x);
//~ $x= CNum::parse('-1,000');  var_dump($x);
//~ $x= CNum::parse('-3000,000');  var_dump($x);
//~ $x= CNum::parse('-4.000');  var_dump($x);
//~ $x= CNum::parse('-50000.00');  var_dump($x);
//~ $x= CNum::parse('-54985043.000');  var_dump($x);
//~ $x= CNum::parse('-304580345,456');  var_dump($x);
