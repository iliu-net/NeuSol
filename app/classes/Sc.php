<?php
abstract class Sc {
  static public function f3() {
    return Base::instance();
  }
  static public function cli_only() {
    if (php_sapi_name() != 'cli') {
      $f3->error(405);
      return;
    }
    global $argv;
    return [array_slice($argv,2),array_slice($argv,0,2)];
  }
  static public function cli_path($name) {
    if ($name{0} == '/') return $name;
    return WORKING_DIR.'/'.$name;
  }

  static public function uploadForm($opts=[]) {
    if (isset($opts['label'])) {
      $label = $opts['label'];
      unset($opts['label']);
    } else {
      $label = 'Select file to upload';
    }
    $tag = '';
    $tag .= '<form method="post" enctype="multipart/form-data"';
    foreach ($opts as $k => $v) {
      if (preg_match('/^\d+$/',$k)) {
        $tag .= ' '.$v;
      } else {
	$tag .= ' '.$k.'="'.$v.'"';
      }
    }
    $tag .=  '>'.$label;
    $tag .= '<input type="file" name="fileToUpload" id="fileToUpload" onchange="form.submit()"/>';
    $f3 = Sc::f3();
    $tag .= 'Default Account: <select id="def_acct" name="def_acct">';
    $tag .= ' <option value="#">* automatic *</option>';
    foreach ($f3->get('accounts_long') as $k=>$v) {
      $tag .= '<option value="'.$k.'"';
      if ($f3->exists('POST.def_acct')) {
        if ($f3->get('POST.def_acct') == $v) $tag .= ' selected';
      }
      $tag .= '>'.$v.'</option>';
    }
    $tag .= '</select>';
    $tag .= '</form>';
    return $tag;
  }

  static public function add_path($var,$path,$prepend_base = true) {
    $f3 = Base::instance();
    if ($f3->exists($var)) {
      $inc = $f3->get($var);
    } else {
      $inc = [];
    }
    if (!is_array($path)) $path = [ $path ];
    $base = $prepend_base ? $f3->get('BASE') : '';
    foreach ($path as $p) {
      $inc[$base.$p] = $base.$p;
    }
    $f3->set($var,$inc);
  }
  static public function x_css($css) {
    $f3 = Base::instance();
    $var = 'x_css_inline';
    if ($f3->exists($var)) {
      $f3->concat($var,$css);
    } else {
      $f3->set($var,$css);
    }
  }

  static public function x_css_inc($path,$opt=true) {
    Sc::add_path('x_css_inc',$path);
  }
  static public function ui_script($path,$opt=true) {
    Sc::add_path('ui_scripts',$path);
  }

  static public function url($path) {
    return Base::instance()->get("BASE").$path;
  }
  static public function go($path,$text,$opts = []) {
    if (isset($opts['confirm'])) {
      $opts['onclick'] = "return confirm('".$opts['confirm']."')";
      unset($opts['confirm']);
    }

    $tag = '';
    $tag .= '<a href="'.Sc::url($path).'"';
    foreach ($opts as $k => $v) {
      if (preg_match('/^\d+$/',$k)) {
        $tag .= ' '.$v;
      } else {
	$tag .= ' '.$k.'="'.$v.'"';
      }
    }
    $tag .= '>';
    $tag .= $text;
    $tag .= '</a>';
    return $tag;
  }
  static public function jslnk($js,$text,$opts = []) {
    $tag = '';
    $tag .= '<a href="javascript:void(0);"';
    $tag .= ' onclick="'.$js.'"';
    foreach ($opts as $k => $v) {
      if (preg_match('/^\d+$/',$k)) {
        $tag .= ' '.$v;
      } else {
	$tag .= ' '.$k.'="'.$v.'"';
      }
    }
    $tag .= '>';
    $tag .= $text;
    $tag .= '</a>';
    return $tag;
  }
  static public function esc($txt) {
    return htmlspecialchars($txt);
  }
  static public function enc($txt) {
    return urlencode($txt);
  }
  static public function bool($val,$opts = ['disabled']) {
    $tag = '<input type="checkbox"' .($val ? ' checked' : '');
    foreach ($opts as $k => $v) {
      if (preg_match('/^\d+$/',$k)) {
        $tag .= ' '.$v;
      } else {
	$tag .= ' '.$k.'="'.$v.'"';
      }
    }
    $tag .= '/>';
    return $tag;
  }
  static public function select($val,$txts,$name,$opts=[]) {
    $tag = '';
    $tag .= '<select name="'.$name.'" id="form_'.$name.'"';
    foreach ($opts as $k => $v) {
      if (preg_match('/^\d+$/',$k)) {
        $tag .= ' '.$v;
      } else {
	$tag .= ' '.$k.'="'.$v.'"';
      }
    }
    $tag .= '>';
    foreach ($txts as $k=>$v) {
      $tag .= '<option value="'.$k.'"';
      if ($k == $val) $tag.= ' selected';
      $tag .= '>'.$v.'</option>';
    }
    $tag .= '</select>';
    return $tag;
  }

/*
  static public function radio($val,$opts = ['disabled']) {
    $tag = '<input type="radio"' .($val ? ' checked' : '');
    foreach ($opts as $k => $v) {
      if (preg_match('/^\d+$/',$k)) {
        $tag .= ' '.$v;
      } else {
	$tag .= ' '.$k.'="'.$v.'"';
      }
    }
    $tag .= '/>';
    return $tag;
  }
  

  static public function month($month,$year,$name) {
    $tag = '';
    $tag .= '<input type="text" size=4 maxlength=2 name="'.$name.'_month" value="'.$month.'" />';
    $tag .= '<input type="text" size=6 maxlength=4 name="'.$name.'_year" value="'.$year.'" />';
    return $tag;
  }
*/
}