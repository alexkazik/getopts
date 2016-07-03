<?php

/*

  GetOpts by ALeX Kazik

  Code: https://github.com/alexkazik/getopts
  Docs: https://github.com/alexkazik/getopts/wiki/Documentation
  Issues: https://github.com/alexkazik/getopts/issues

  License: Creative Commons Attribution 3.0 Unported License
  http://creativecommons.org/licenses/by/3.0/

*/

class GetOpts {
  /** @const string */
  const SIMPLE = 'S';
  /** @const string */
  const TOGGLE = 'T';
  /** @const string */
  const COUNT = 'C';
  /** @const string */
  const VALUE = 'V';
  /** @const string */
  const VALUE_MULTIPLE = 'Vm';
  /** @const string */
  const VALUE_AUTOMATIC = 'Va';
  /** @const string */
  const OPTIONAL = 'O';
  /** @const string */
  const OPTIONAL_MULTIPLE = 'Om';
  /** @const string */
  const OPTIONAL_AUTOMATIC = 'Oa';
  /** @const string */
  const ASSOCIATIVE = 'A';

  /** @var string[] */
  private $optionType = [];
  /** @var string[] */
  private $optionLong = [];
  /** @var string[] */
  private $optionShort = [];

  /** @var string[] */
  private $inputArgs;
  /** @var string[] */
  private $outputErrors;
  /** @var array */
  private $outputResult;

  /**
   * @param string[][] $params
   * @throws Exception
   */
  public function __construct(array $params) {
    // parse options
    foreach ($params as $opt => $names) {
      if (!self::isStringArray($names)) {
        throw new Exception('Definition of "'.$opt.'" is not an array of strings');
      }
      $type = array_shift($names);
      if (!in_array($type, [
          self::SIMPLE,
          self::TOGGLE,
          self::COUNT,
          self::VALUE,
          self::VALUE_MULTIPLE,
          self::VALUE_AUTOMATIC,
          self::OPTIONAL,
          self::OPTIONAL_MULTIPLE,
          self::OPTIONAL_AUTOMATIC,
          self::ASSOCIATIVE,
        ])
      ) {
        throw new Exception('Invalid type to param "'.$opt.'"');
      }
      $this->optionType[$opt] = $type;

      foreach ($names as $name) {
        if (preg_match('!^[0-9a-zA-Z]$!', $name)) {
          if (array_key_exists($name, $this->optionShort)) {
            throw new Exception('Duplicate option name "'.$name.'"');
          }
          $this->optionShort[$name] = $opt;
        } elseif (preg_match('!-?([0-9a-zA-Z]+)(-([0-9a-zA-Z]+))*$!', $name)) {
          if ($name[0] == '-') {
            $name = substr($name, 1);
          }
          if (array_key_exists($name, $this->optionLong)) {
            throw new Exception('Duplicate option name "'.$name.'"');
          }
          $this->optionLong[$name] = $opt;
        } else {
          throw new Exception('Invalid name to param "'.$opt.'"');
        }
      }
    }
  }

  /**
   * @param string[]|null $args
   * @return array
   * @throws Exception
   */
  public function parse($args = null) {
    // check input
    if ($args === null && is_array($_SERVER['argv'])) {
      $args = $_SERVER['argv'];
      array_shift($args);
    }
    if (!self::isStringArray($args)) {
      throw new Exception('Arguments are not an array of strings');
    }

    // set arguments
    $this->inputArgs = $args;

    // output
    $this->outputErrors = [];
    $this->outputResult = array_combine(array_keys($this->optionType), array_fill(0, count($this->optionType), []));
    $outArgs = [];

    // parse arguments
    while (($arg = $this->getNextArg()) !== false) {
      if ($arg == '--') {
        // end of options, copy all other args
        while (($arg = $this->getNextArg()) !== false) {
          $outArgs[] = $arg;
        }
      } elseif ($arg == '-') {
        // neither an option nor argument
        $this->outputErrors[] = 'Invalid option "-"';
      } elseif ($arg == '' || $arg[0] != '-') {
        // not an option -> copy to args
        $outArgs[] = $arg;
      } elseif ($arg[1] == '-') {
        // this is an long option
        $p = strpos($arg, '=');
        if ($p !== false) {
          $next = self::subString($arg, $p + 1);
          $arg = substr($arg, 2, $p - 2);
        } else {
          $next = true;
          $arg = substr($arg, 2);
        }
        if (!array_key_exists($arg, $this->optionLong)) {
          $this->outputErrors[] = 'Unknown option "--'.$arg.'"';
        } else {
          $opt = $this->optionLong[$arg];
          $this->parseOption($opt, '--'.$arg, $next);
        }
      } else {
        // is is(are) short option(s)
        for ($i = 1; $i < strlen($arg); $i++) {
          $c = $arg[$i];
          $next = self::subString($arg, $i + 1);
          if ($next == '') {
            $next = true;
          } elseif ($next[0] == '=') {
            $next = self::subString($next, 1);
          }
          if (!array_key_exists($c, $this->optionShort)) {
            $this->outputErrors[] = 'Unknown option "-'.$c.'"';
            break;
          } else {
            $opt = $this->optionShort[$c];
            if ($this->parseOption($opt, '-'.$c, $next)) {
              break;
            }
          }
        }
      }
    }

    // result
    return [
      count($this->outputErrors) == 0 ? false : $this->outputErrors,
      $this->formatOutput(),
      $outArgs,
    ];
  }

  /**
   * @param string|bool $next
   * @return string|bool
   */
  private function getNextArg($next = true) {
    if ($next !== true) {
      return $next;
    } elseif (count($this->inputArgs) == 0) {
      return false;
    } else {
      return array_shift($this->inputArgs);
    }
  }

  /**
   * @param string $opt
   * @param string $name
   * @param string|bool $next
   * @return bool
   */
  private function parseOption($opt, $name, $next) {
    switch ($this->optionType[$opt]) {
      case self::SIMPLE:
      case self::TOGGLE:
      case self::COUNT:
        $this->outputResult[$opt][] = true;
        return false;
      case self::VALUE:
      case self::VALUE_MULTIPLE:
      case self::VALUE_AUTOMATIC:
        if (($val = $this->getNextArg($next)) === false) {
          $this->outputErrors[] = 'Missing argument to option "'.$name.'"';
        } else {
          $this->outputResult[$opt][] = $val;
        }
        return true;
      case self::OPTIONAL:
      case self::OPTIONAL_MULTIPLE:
      case self::OPTIONAL_AUTOMATIC:
        $this->outputResult[$opt][] = $next;
        return true;
      case self::ASSOCIATIVE:
        if (($val = $this->getNextArg($next)) === false) {
          $this->outputErrors[] = 'Missing argument to option "'.$name.'"';
        } else {
          $p = strpos($val, '=');
          if ($p === false) {
            $this->outputErrors[] = 'Malformed argument to option "'.$name.'" (a "=" is missing)';
          } elseif (isset($this->outputResult[$opt][substr($val, 0, $p)])) {
            $this->outputErrors[] = 'Duplicate key "'.substr($val, 0, $p).'" to option "'.$name.'"';
          } else {
            $this->outputResult[$opt][substr($val, 0, $p)] = self::subString($val, $p + 1);
          }
        }
        return true;
    }
    return false;
  }

  /**
   * @return array
   */
  private function formatOutput() {
    foreach ($this->outputResult as $opt => &$r) {
      switch ($this->optionType[$opt]) {
        case self::SIMPLE:
          $r = count($r) > 0;
          break;
        case self::TOGGLE:
          $r = (count($r) & 1) == 1;
          break;
        case self::COUNT:
          $r = count($r);
          break;
        case self::VALUE:
          if (count($r) == 0) {
            // no option
            $r = false;
          } else {
            // pick last entry
            $r = array_pop($r);
          }
          break;
        case self::OPTIONAL:
          if (count($r) == 0) {
            // no option
            $r = false;
          } else {
            // pick last entry; if possible last with a value
            do {
              $rr = array_pop($r);
            } while ($rr === true && count($r) > 0);
            $r = $rr;
          }
          break;
        case self::VALUE_MULTIPLE:
        case self::OPTIONAL_MULTIPLE:
          if (count($r) == 0) {
            // no option
            $r = false;
          } else {
            // as array
            // (already done)
          }
          break;
        case self::VALUE_AUTOMATIC:
        case self::OPTIONAL_AUTOMATIC:
          // false if none, direct (string) if only one, array otherwise
          if (count($r) == 0) {
            // no option
            $r = false;
          } elseif (count($r) == 1) {
            // a single option
            $r = array_pop($r);
          } else {
            // as array
            // (already done)
          }
          break;
        case self::ASSOCIATIVE:
          // as array
          // (already done)
          break;
      }
    }

    return $this->outputResult;
  }

  /**
   * Almost identical to substr, but returns an empty string instead of false when start is length
   *
   * @param string $string
   * @param int $start
   * @return string
   */
  private static function subString($string, $start) {
    if ($start == strlen($string)) {
      return '';
    } else {
      return substr($string, $start);
    }
  }

  /**
   * @param mixed $data
   * @return bool
   */
  private static function isStringArray($data){
    if(!is_array($data)){
      return false;
    }
    foreach($data as $item){
      if(!is_string($item)){
        return false;
      }
    }
    return true;
  }
}
