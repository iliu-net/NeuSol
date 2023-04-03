<?php

#****c* classes/Fm
# NAME
#   Fm -- Form related utility class
#******

abstract class Fm {
  const BOOLEAN_RE = '/^\d+$/';

  #****m* Fm/input
  # NAME
  #   input -- create HTML of a input field
  # SYNOPSIS
  #   $html = Fm::input($name,$opts)
  # FUNCTION
  #   Returns the HTML rendering for a generic input field.  It will
  #   pre-load with the POST.$name value.
  # INPUTS
  #   $name - F3 POST variable name
  #   $opts - array containing options as a possible mix of scalars
  #           and key+value pairs.
  #           Normally, these will be simply included in the <input>
  #           tag as additional attributes.
  # OPTIONS
  #   These options in "$opts" are treated specially:
  #
  #   prefix - This is prepended to the $name to generic a "id"
  #            attribute.  The "id" attribute can be used to refer
  #            to the control in JavaScript.  If not specified,
  #            it will default to "form_".
  #   type   - type of input control.  Defaults to "text".
  #   name   - Used to override the field name.  Defaults to $name.
  #   value  - pre-load field value.  If not specified, it would read
  #            from "POST.$name" or use a $opts['default'].
  #   default - Used to initialize "value" if not in "POST.$name".
  #   label - Label for this input control.
  # RESULTS
  #   HTML rendering for input field.
  #******
  static public function input($name,$opts=[]) {
    $tag = '';
    if (isset($opts['prefix'])) {
      $prefix = $opts['prefix'];
      unset($opts['prefix']);
    } else {
      $prefix = 'form_';
    }

    if (!isset($opts['type'])) $opts['type'] = 'text';
    if (!isset($opts['name'])) $opts['name'] = $name;
    if (!isset($opts['id'])) $opts['id'] = $prefix.$name;

    if (!isset($opts['value'])) {
      $f3 = Base::instance();
      if ($f3->exists('POST.'.$name)) {
        $opts['value'] = $f3->get('POST.'.$name);
      } elseif (isset($opts['default'])) {
	$opts['value'] = self::esc($opts['default']);
      } else {
        $opts['value'] = '';
      }
    } else {
      $opts['value'] = self::esc($opts['value']);
    }
    if (isset($opts['default'])) unset($opts['default']);
    if (isset($opts['label'])) {
      $tag .= '<label for="'.$opts['id'].'">'.$opts['label'].'</label>';
      unset($opts['label']);
    }

    $tag .= '<input';
    foreach ($opts as $k=>$v) {
      if (preg_match(self::BOOLEAN_RE,$k)) {
        $tag .= ' '.$v;
      } else {
        $tag .= ' '.$k.'="'.$v.'"';
      }
    }
    $tag .= '/>';
    return $tag;
  }
  #****m* Fm/text
  # NAME
  #   text -- create HTML input text
  # SYNOPSIS
  #   $html = Fm::text($name,$opts)
  # FUNCTION
  #   This is just an alias for Fm::input().
  # INPUTS
  #   $name - F3 POST variable name
  #   $opts - array containing options as a possible mix of scalars
  #           and key+value pairs.
  # SEE ALSO
  #   Fm/input
  # RESULTS
  #   HTML rendering for input text field.
  #******
  static public function text($name,$opts=[]) {
    return self::input($name,$opts);
  }
  #****m* Fm/checkbox
  # NAME
  #   checkbox -- create HTML checkbox
  # SYNOPSIS
  #   $html = Fm::checkbox($name,$opts)
  # FUNCTION
  #   Returns the HTML rendering for a checkbox.  It will pre-load
  #   with the POST.$name value.
  # INPUTS
  #   $name - F3 POST variable name
  #   $opts - array containing options as a possible mix of scalars
  #           and key+value pairs.
  # OPTIONS
  #    See OPTIONS section for Fm/input.
  # SEE ALSO
  #   Fm/input
  # RESULTS
  #   HTML rendering for checkbox.
  #******
  static public function checkbox($name,$opts=[]) {
    $f3 = Base::instance();
    if ($f3->exists('POST.'.$name) && $f3->get('POST.'.$name)) {
      $opts[] = 'checked';
    }
    if (!isset($opts['value'])) $opts['value'] = 1;
    if (!isset($opts['type'])) $opts['type'] = 'checkbox';
    return self::input($name,$opts);
  }
  #****m* Fm/radio
  # NAME
  #   radio -- create HTML radio button
  # SYNOPSIS
  #   $html = Fm::radio($name,$opts)
  # FUNCTION
  #   Returns the HTML rendering for a radio button.  It will pre-load
  #   with the POST.$name value.
  #
  #   The radio group is specified by the "$name" value.  i.e. radio
  #   buttons with the same "$name" value will belong to the same
  #   radio group.
  # INPUTS
  #   $name - F3 POST variable name
  #   $opts - array containing options as a possible mix of scalars
  #           and key+value pairs.
  # OPTIONS
  #    See OPTIONS section for Fm/input.
  # SEE ALSO
  #   Fm/input
  # RESULTS
  #   HTML rendering for radio button.
  #******
  static public function radio($name,$opts=[]) {
    $f3 = Base::instance();
    $isdefault = false;
    foreach ($opts as $k=>$v) {
      if (preg_match(self::BOOLEAN_RE,$k) && $v == 'default') {
        $isdefault = [$k];
	break;
      }
    }
    if ($isdefault) {
      unset($opts[$isdefault[0]]);
      $isdefault = true;
    }
    if (!isset($opts['value'])) $f3->error(404);

    if (!$f3->exists('POST.'.$name) && $isdefault) $f3->set('POST.'.$name,$opts['value']);
    if ($f3->exists('POST.'.$name) && $f3->get('POST.'.$name) == $opts['value']) {
      $opts[] = 'checked';
    }
    if (!isset($opts['type'])) $opts['type'] = 'radio';
    return self::input($name,$opts);
  }

  #****m* Fm/select
  # NAME
  #   select -- create HTML of a select field
  # SYNOPSIS
  #   $html = Fm::select($name,$choices,$opts)
  # FUNCTION
  #   Returns the HTML rendering for a generic select field.  It will
  #   pre-load with the POST.$name value.
  # INPUTS
  #   $name - F3 POST variable name
  #   $choices - array with the possible selectable options.
  #   $opts - array containing options as a possible mix of scalars
  #           and key+value pairs.
  #           Normally, these will be simply included in the <select>
  #           tag as additional attributes.
  # OPTIONS
  #   These options in "$opts" are treated specially:
  #
  #   prefix - This is prepended to the $name to generic a "id"
  #            attribute.  The "id" attribute can be used to refer
  #            to the control in JavaScript.  If not specified,
  #            it will default to "form_".
  #   name   - Used to override the field name.  Defaults to $name.
  #   value  - pre-load field value.  If not specified, it would read
  #            from "POST.$name" or use a $opts['default'].
  #   default - Used to initialize "value" if not in "POST.$name".
  #   label - Label for this select control.
  # RESULTS
  #   HTML rendering for input field.
  #******
  static public function select($name,$choices,$opts=[]) {
    $tag = '';

    if (isset($opts['prefix'])) {
      $prefix = $opts['prefix'];
      unset($opts['prefix']);
    } else {
      $prefix = 'form_';
    }

    if (!isset($opts['name'])) $opts['name'] = $name;
    if (!isset($opts['id'])) $opts['id'] = $prefix.$name;

    if (isset($opts['value'])) {
      $value = $opts['value'];
      unset($opts['value']);
    } else {
      $f3 = Base::instance();
      if ($f3->exists('POST.'.$name)) {
        $value = $f3->get('POST.'.$name);
      } elseif (isset($opts['default'])) {
        $value = $opts['default'];
      } else {
        foreach ($choices as $k=>$v) {
	  $value = $k;
	  break;
	}
      }
    }
    if (isset($opts['default'])) unset($opts['default']);

    if (isset($opts['label'])) {
      $tag .= '<label for="'.$opts['id'].'">'.$opts['label'].'</label>';
      unset($opts['label']);
    }

    $tag .= '<select';
    foreach ($opts as $k=>$v) {
      if (preg_match(self::BOOLEAN_RE,$k)) {
        $tag .= ' '.$v;
      } else {
        $tag .= ' '.$k.'="'.$v.'"';
      }
    }
    $tag .= '>';
    foreach ($choices as $k=>$v) {
      $tag .= '<option value="'.$k.'"';
      if ($k == $value) $tag.= ' selected';
      $tag .= '>'.$v.'</option>';
    }
    $tag .= '</select>';
    return $tag;
  }
  #****m* Fm/textarea
  # NAME
  #   textarea -- create HTML of a textarea field
  # SYNOPSIS
  #   $html = Fm::textarea($name,$opts)
  # FUNCTION
  #   Returns the HTML rendering for a generic textarea.  It will
  #   pre-load with the POST.$name value.
  # INPUTS
  #   $name - F3 POST variable name
  #   $opts - array containing options as a possible mix of scalars
  #           and key+value pairs.
  #           Normally, these will be simply included in the <input>
  #           tag as additional attributes.
  # OPTIONS
  #   These options in "$opts" are treated specially:
  #
  #   prefix - This is prepended to the $name to generic a "id"
  #            attribute.  The "id" attribute can be used to refer
  #            to the control in JavaScript.  If not specified,
  #            it will default to "form_".
  #   name   - Used to override the field name.  Defaults to $name.
  #   value  - pre-load field value.  If not specified, it would read
  #            from "POST.$name" or use a $opts['default'].
  #   default - Used to initialize "value" if not in "POST.$name".
  #   label - Label for this textarea.
  # RESULTS
  #   HTML rendering for input field.
  #******

  static public function textarea($name,$opts=[]) {
    $tag = '';

    if (isset($opts['prefix'])) {
      $prefix = $opts['prefix'];
      unset($opts['prefix']);
    } else {
      $prefix = 'form_';
    }

    if (!isset($opts['name'])) $opts['name'] = $name;
    if (!isset($opts['id'])) $opts['id'] = $prefix.$name;

    if (isset($opts['value'])) {
      $value = self::esc($opts['value']);
      unset($opts['value']);
    } else {
      $f3 = Base::instance();
      if ($f3->exists('POST.'.$name)) {
        $value = $f3->get('POST.'.$name);
      } elseif (isset($opts['default'])) {
        $value = self::esc($opts['default']);
      } else {
        $value = "";
      }
    }
    if (isset($opts['default'])) unset($opts['default']);

    if (isset($opts['label'])) {
      $tag .= '<label for="'.$opts['id'].'">'.$opts['label'].'</label>';
      unset($opts['label']);
    }

    $tag .= '<textarea';
    foreach ($opts as $k=>$v) {
      if (preg_match(self::BOOLEAN_RE,$k)) {
        $tag .= ' '.$v;
      } else {
        $tag .= ' '.$k.'="'.$v.'"';
      }
    }
    $tag .= '>';

    $tag .= $value;

    $tag .= '</textarea>';
    return $tag;

  }
  #****m* Fm/date
  # NAME
  #   date -- create HTML date field
  # SYNOPSIS
  #   $html = Fm::date($name,$opts)
  # FUNCTION
  #   Returns the HTML rendering for a date field.  It will pre-load
  #   with the POST.$name value.
  # INPUTS
  #   $name - F3 POST variable name
  #   $opts - array containing options as a possible mix of scalars
  #           and key+value pairs.
  # OPTIONS
  #    See OPTIONS section for Fm/input.
  # SEE ALSO
  #   Fm/input
  # RESULTS
  #   HTML rendering for checkbox.
  #******
  static public function date($name,$opts=[]) {
    $f3 = Base::instance();
    if (!isset($opts['type'])) $opts['type'] = 'date';
    return self::input($name,$opts);
  }
  #****m* Fm/esc
  # NAME
  #   esc -- Escape HTML entities
  # SYNOPSIS
  #   $escaped = Fm::esc($txt)
  # FUNCTION
  #   Escape text in $txt using PHP htmlspecialchars function.
  # INPUTS
  #   $txt - Text to escape
  # RESULTS
  #   Escaped HTML output.
  #******
  static public function esc($txt) {
    return htmlspecialchars($txt);
  }

}
