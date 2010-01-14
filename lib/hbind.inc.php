<?php
/**
 * Hbind -- `sprintf` for HTML
 */

/**
 * Bind selectors to a template.
 * Uses hbind selectors to identify targets to replace with values.
 * Takes an associative array of selector => value.
 * A value may be a string, an array of strings or NULL.
 * Binding NULL will remove the selected element from the source
 */
function hbind($template, $parameters = array()) {
  foreach ($parameters as $identifier => $value) {
    $selector = new hbind_Selector($identifier);
    $template = $selector->bind($template, $value);
  }
  return $template;
}

/**
 * A hbind selector.
 * A selector can scan html and replace parts.
 */
class hbind_Selector {
  protected $name;
  protected $id;
  protected $class;
  protected $attribute;
  protected $embed = false;
  function __construct($string_raw) {
    if (preg_match('~(.*)!$~', $string_raw, $mm)) {
      $this->embed = true;
      $string_raw = $mm[1];
    }
    if (preg_match('~^(.*)(:(.*))$~', $string_raw, $mm)) {
      $this->attribute = $mm[3];
      $string_raw = $mm[1];
    }
    if (preg_match('~^(.*)\.(.+)$~', $string_raw, $mm)) {
      $this->name = $mm[1];
      $this->class = $mm[2];
    } elseif (preg_match('~^#(.+)$~', $string_raw, $mm)) {
      $this->id = $mm[1];
    } else {
      $this->name = $string_raw;
    }
  }
  function getArrayCopy() {
    return get_object_vars($this);
  }
  function match($token) {
    if ($this->name) {
      if (0 !== strpos($token, '<' . $this->name)) {
        return false; // mismatch on name
      }
    }
    if ($this->id) {
      if ((false === strpos($token, "id='" . $this->id . "'")) &&
          (false === strpos($token, 'id="' . $this->id . '"')) &&
          (false === strpos($token, "name='" . $this->id . "'")) &&
          (false === strpos($token, 'name="' . $this->id . '"'))) {
        return false; // mismatch on id/name
      }
    }
    if ($this->class) {
      if (preg_match('~class="([^"]*)"~', $token, $mm)) {
        $classes = explode(" ", $mm[1]);
      } elseif (preg_match('~class=\'([^\']*)\'~', $token, $mm)) {
        $classes = explode(" ", $mm[1]);
      } else {
        return false; // mismatch on class (no class)
      }
      if (!in_array($this->class, $classes)) {
        return false; // mismatch on class
      }
    }
    return true;
  }
  function slice($input) {
    // TODO: cdata-sections + comments
    $result = array();
    $buffer = "";
    $state = 0;
    $depth = 0;
    $search = null;
    foreach (preg_split('~(<[^>]+>)~', $input, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $token) {
      if ('</' === substr($token, 0, 2)) {
        $type = 0; // closing
      } elseif ('/>' === substr($token, -2)) {
        $type = 2; // singlet
      } elseif ('<' === substr($token, 0, 1)) {
        $type = 1; // opening
      } else {
        $type = 3; // text
      }
      if ($state === 0) { // non-match
        if ($type === 1 && $this->match($token)) {
          // match opening
          $result[] = array(0, $buffer);
          $buffer = $token;
          $state = 1;
          preg_match('~^<([\w]+)~', $token, $mm);
          $search = $mm[1];
        } elseif ($type === 2 && $this->match($token)) {
          // match singlet
          $result[] = array(0, $buffer);
          $result[] = array(1, $token);
          $buffer = "";
          $state = 0;
        } else {
          $buffer .= $token;
        }
      } elseif ($state === 1) { // within match
        if ($type === 0 && preg_match('~^</([\w]+)~', $token, $mm) && $mm[1] === $search) { // closing tag of the type we're looking for
          if ($depth === 0) {
            $result[] = array(1, $buffer . $token);
            $buffer = "";
            $state = 0;
          } else {
            $depth--;
            $buffer .= $token;
          }
        } elseif ($type === 1 && preg_match('~^<([\w]+)~', $token, $mm) && $mm[1] === $search) { // same type as search -> raise depth
          $depth++;
          $buffer .= $token;
        } else {
          $buffer .= $token;
        }
      }
    }
    if ($state === 0) {
      $result[] = array(0, $buffer);
    }
    return $result;
  }
  function replace($input, $value) {
    $escaped_value = $this->embed ? $value : htmlspecialchars($value);
    if ($this->attribute) {
      // TODO: delete attribute if null
      // TODO: this doesn't cater for escaped quotes. should be good for most scenarios though
      if (preg_match('~^(<[^>]+'.preg_quote($this->attribute, '~').'=")([^"]*)("[\s\S]*)$~', $input, $mm)) {
        // replace attribute, doublequoted
        return $mm[1] . $escaped_value . $mm[3];
      }
      if (preg_match('~^(<[^>]+'.preg_quote($this->attribute, '~').'=\')([^\']*)(\'[\s\S]*)$~', $input, $mm)) {
        // replace attribute, singlequoted
        return $mm[1] . $escaped_value . $mm[3];
      }
      // add attribute
      preg_match('~^(<[\w]+)([\s\S]*)$~', $input, $mm);
      return $mm[1] . ' ' . $this->attribute . '="' . $escaped_value . '"' . $mm[2];
    }
    if ($value === null) {
      return "";
    }
    // TODO: won't work with singlet tags
    if (preg_match('~^(<[^>]+>)([\s\S]*)(</[^>]+>)$~', $input, $mm)) {
      return $mm[1] . $escaped_value . $mm[3];
    }
    throw new Exception("Can't replace");
  }
  function bind($template, $value) {
    $compiled = "";
    foreach ($this->slice($template) as $part) {
      list($type, $data) = $part;
      if ($type === 0) {
        $compiled .= $data;
      } else {
        foreach ((array)$value as $val) {
          $compiled .= $this->replace($data, $val);
        }
      }
    }
    return $compiled;
  }
}

