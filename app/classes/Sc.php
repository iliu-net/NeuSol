<?php

#****c* classes/Sc
# NAME
#   Sc -- Fat Free Framework shortcut utilities
#******
abstract class Sc {
  #****m* Sc/f3
  # NAME
  #   f3 -- Returns the FatFreeFramework instance
  # SYNOPSIS
  #   $f3 = Sc::f3()
  # FUNCTION
  #   Returns the current FatFreeFramework instance.
  # INPUTS
  #   None
  # RESULTS
  #   Returns a FatFreeFramework instance.
  #******
  static public function f3() {
    return Base::instance();
  }
  #****m* Sc/cli_only
  # NAME
  #   cli_only -- ensure CLI environment
  # SYNOPSIS
  #   list($args,$arg0n1 = Sc::cli_only()
  # FUNCTION
  #   Make sure that the script is running in CLI environment.
  #   If running from web server it is considered a fatal error.
  # INPUTS
  #   None
  # RESULTS
  #   Aborts the script in run from web server.
  #   Returns arguments and [$argv[0],$argv[1]].
  #******
  static public function cli_only() {
    if (php_sapi_name() != 'cli') {
      $f3->error(405);
      return;
    }
    global $argv;
    return [array_slice($argv,2),array_slice($argv,0,2)];
  }
  #****m* Sc/cli_path
  # NAME
  #   cli_path -- covert a CLI path
  # SYNOPSIS
  #   $fpath = Sc::cli_path($name)
  # FUNCTION
  #   Used to translate the passed command line arguments into
  #   file paths.
  #
  #   This is needed because our "index.php" will change
  #   current working directories to match the web environment.
  # INPUTS
  #   $name - file name to convert
  # RESULTS
  #   Absolut path to file.
  #******
  static public function cli_path($name) {
    if (substr($name,0,1) == '/') return $name;
    return WORKING_DIR.'/'.$name;
  }
  #****m* Sc/url
  # NAME
  #   url -- Generate a URL path
  # SYNOPSIS
  #   $url = Sc::url($path)
  # FUNCTION
  #   Create a $url for the given $path.
  # INPUTS
  #   $path - HTML path
  # RESULTS
  #   Absolue $url for $path.
  #******
  static public function url($path) {
    return Base::instance()->get("BASE").$path;
  }
  #****m* Sc/enc
  # NAME
  #   enc -- Escape special URL characters
  # SYNOPSIS
  #   $escaped = Sc::enc($txt)
  # FUNCTION
  #   Escape text in $txt for use in URLs.
  # INPUTS
  #   $txt - Text to escape
  # RESULTS
  #   Escaped URL output.
  #******
  static public function enc($txt) {
    return urlencode($txt);
  }
  #****m* Sc/go
  # NAME
  #   go -- Generate an HTML hyperink
  # SYNOPSIS
  #   $html = Sc::go($path,$text,$opts)
  # FUNCTION
  #   Generate HTML for hyperlink.
  # INPUTS
  #   $path - path to link to.
  #   $text - text to display
  #   $opts - array containing options as a possible mix of scalars
  #           and key+value pairs.
  #           Normally, these will be simply included in the <a>
  #           tag as additional attributes.
  # OPTIONS
  #   These options in "$opts" are treated specially:
  #
  #   onclick - Will create a confirmation dialog with the text
  #             given in the "onclick" attribute.
  # RESULTS
  #   HTML rendering for a tag..
  #******
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
  #****m* Sc/gowin
  # NAME
  #   gowin -- Generate an HTML hyperink that opens a new window
  # SYNOPSIS
  #   $html = Sc::gowin($path,$text,$opts)
  # FUNCTION
  #   Generate HTML for hyperlink.
  # INPUTS
  #   $path - path to link to.
  #   $text - text to display
  #   $opts - array containing options as a possible mix of scalars
  #           and key+value pairs.
  #           Normally, these will be simply included in the <a>
  #           tag as additional attributes.
  # OPTIONS
  #   These options in "$opts" are treated specially:
  #
  #   onclick - Will create a confirmation dialog with the text
  #             given in the "onclick" attribute.
  #   top,left,width,height,menubar,toolbar,location,status,scrollbars,
  #   resizable,popup - these options are passed to the javascript
  #              that opens the new window.
  # RESULTS
  #   HTML rendering for a tag..
  #******
  static public function gowin($path,$text,$opts = []) {
    $opts['onclick'] = 'window.open('."'".Sc::url($path)."'";
    if (isset($opts['pagename'])) {
      $opts['onclick'] .= ",'".$opts['pagename']."'";
      unset($opts['pagename']);
    } else {
      $opts['onclick'] .= ",'$text'";
    }
    $opts['onclick'] .= ",'"; // Add features...
    $q='';
    foreach (['top','left','width','height'] as $k) {
      if (isset($opts[$k])) {
	$opts['onclick'] .= $q . $k . '=' . $opts[$k];
	$q = ',';
	unset($opts[$k]);
      }
    }
    $features = ['menubar','toolbar','location','status','scrollbars','resizable','popup'];
    foreach ($opts as $k => $v) {
      if (!preg_match('/^\d+$/',$k)) continue;
      if (!in_array($v,$features)) continue;
      $opts['onclick'] .= $q.$v;
      $q = ',';
      unset($opts[$k]);
    }
    $opts['onclick'] .= "'";
    $opts['onclick'] .= ');';
    $opts['onclick'] .= 'return false;';

    $tag = '';
    $tag .= '<a href="#"';
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
  #****m* Sc/jslnk
  # NAME
  #   jslink -- Generate link that calls a javascript function
  # SYNOPSIS
  #   $html = Sc::jslnk($js,$text,$opts)
  # FUNCTION
  #   Generate HTML for javascript hyperlink.
  # INPUTS
  #   $js - JavaScript to execute.
  #   $text - text to display
  #   $opts - array containing options as a possible mix of scalars
  #           and key+value pairs.
  #           Normally, these will be simply included in the <a>
  #           tag as additional attributes.
  # RESULTS
  #   HTML rendering for a tag..
  #******
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

}
