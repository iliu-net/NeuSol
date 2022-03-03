<?php

#****c* classes/CompatV1
# NAME
#   CompatV1 -- Fat Free Framework shortcut utilities compatibility module
#******
abstract class CompatV1 {
  #****m* CompatV1/add_path
  # NAME
  #   add_path -- Add items to URL list (DEPRECATED)
  # SYNOPSIS
  #   CompatV1::add_path($var,$path,$prepend_base)
  # FUNCTION
  #   Add a $path to the f3 variable named $var.
  #
  #   By default the $f3->BASE variable is prepended in order
  #   to make absolute URLs.  This default behaviour can be
  #   disabled by making $prepend_base = FALSE.
  # INPUTS
  #   $var - variable to modify
  #   $path - path to add
  #   $prepend_base - set to FALSE to disable prefixing BASE.
  # RESULTS
  #   $var in $f3 HIVE is updated.
  #******
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
  #****m* CompatV1/x_css_inc
  # NAME
  #   x_css_inc -- Register a CSS file (DEPRECATED)
  # SYNOPSIS
  #   CompatV1::x_css_inc($path)
  # FUNCTION
  #   Register a file so that it is sent along with other CSS
  #   settings.
  # INPUTS
  #   $path - path to css to register.
  # RESULTS
  #   x_css_inc in $f3 HIVE is updated.
  #******
  static public function x_css_inc($path) {
    self::add_path('x_css_inc',$path);
  }
  #****m* CompatV1/ui_script
  # NAME
  #   ui_script -- Register a JavaScript file (DEPRECATED)
  # SYNOPSIS
  #   CompatV1::ui_script($path)
  # FUNCTION
  #   Register a file so that it is sent along with other JavaScript
  #   files.
  # INPUTS
  #   $path - path to JS to register.
  # RESULTS
  #   ui_scripts in $f3 HIVE is updated.
  #******
  static public function ui_script($path) {
    self::add_path('ui_scripts',$path);
  }
  #****m* CompatV1/select
  # NAME
  #   select -- create HTML of a select field (DEPRECATED)
  # SYNOPSIS
  #   $html = CompatV1::select($value,$txts,$name,$opts)
  # FUNCTION
  #   Returns the HTML rendering for a generic select field.  It will
  #   pre-load with $value.
  # INPUTS
  #   $value - value to pre-initialize control to.
  #   $txts - array with the possible selectable options.
  #   $name - F3 POST variable name.  Also used to generate id=form_$name.
  #   $opts - array containing options as a possible mix of scalars
  #           and key+value pairs.
  #           Normally, these will be simply included in the <select>
  #           tag as additional attributes.
  # RESULTS
  #   HTML rendering for input field.
  #******
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
      if ($k.'' == $val.'') $tag.= ' selected'; // Force string comparisons...
      $tag .= '>'.$v.'</option>';
    }
    $tag .= '</select>';
    return $tag;
  }
}
