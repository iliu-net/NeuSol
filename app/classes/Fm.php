<?php
abstract class Fm {
  const BOOLEAN_RE = '/^\d+$/';

  static public function f3() {
    return Base::instance();
  }
  static public function checkbox($name,$opts=[]) {
    $f3 = Base::instance();
    if ($f3->exists('POST.'.$name) && $f3->get('POST.'.$name)) {
      $opts[] = 'checked';
    }
    if (!isset($opts['value'])) $opts['value'] = 1;
    if (!isset($opts['type'])) $opts['type'] = 'checkbox';
    return self::text($name,$opts);
  }
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
    return self::text($name,$opts);
  }

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
    
  static public function text($name,$opts=[]) {
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
	$opts['value'] = $opts['default'];
      } else {
        $opts['value'] = '';
      }
    }
    $opts['value'] = Sc::esc($opts['value']);    
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
  static public function txtarea($name,$opts=[]) {
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
 
}