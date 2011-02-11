<?php

	/*
	
		getopts - a replacement for php's getopt
		easier to handle, also returns the other arguments
		
		Requires:
			PHP 5+ (tested on PHP 5.3)
		
		Usage: getopts($params, $args=NULL, $raw=false)
	
			$params is a list of options to parse.
				key: user defined parameter name
				value: type und name(s) of options
				(more see below)
			
			$args are the arguments to parse.
				if not set, they default to the arguments
				passed to php
				
			$raw: return all parameters in raw mode
				(ignores all subtypes) (see raw mode and compatibility below)
		
		Returns: list($errors, $params, $args)
		
			In case of an error while parsing the parameters,
			an error will be triggered.
			
			$errors is either false if there is no error
				otherwise it's an array of strings describing the errors.
				(usually you'll print the first and exit in case of an error)
				(the other errors may be subsequent errors)
			
			$params is an array of all parameters
				key: user defined parameter name (as defined in the parameter)
				value: bool, string or array of string/bool, dependent of the type
			
			$args is the list of all non option arguments
				all arguments which are passed after a "--" will not
				be parsed but copied directly into this array.
			
			
		Sample script:
			require_once('getopts.php');
			list($errors, $params, $args) = getopts(array(
				'a' => 'St a',
				'b' => 'Vs b long',
			));
			if($errors){
				die($errors[0]);
			}
			echo 'a = '.var_export($params['a'], true).', ';
			echo 'b = '.var_export($params['b'], true).', ';
			echo 'args = '.var_export($args, true);
	
		Sample usage:
			in: "php test.php -a file"
			out: a = true, b = false, args = array('file')
			
			in: "php test.php -a --long file -a"
			out: a = false, b = 'file', args = array()


		Types:
			The two and a half basic types:
			SWITCH (S)
				an switch without an value
				e.g. -a, -b, --help
				may be combined (e.g. -ab counts like -a -b)
			
			VALUE (V)
				an option which requires a value
				e.g. -v=val, -vval, -v val
				may be combined (e.g. -av3 counts like -a -v 3)
				
			OPTIONAL (O)
				like V except:
				- an value is not required (reported as true in that case)
				- the format "-o val" is not supported (use -oval, -o=val)
			
		Subtypes:
			The subtype describes how it should be returned
			
			SWITCH:
				simple (Ss, default)
					returns true if the option is one or multiple
					times is given, false otherwise
				toggle (St)
					each occurrence of the option toggles it on/off
					returns true/false
				count (Sc)
					counts the occurrence, returns the count (integer)
				raw (Sr)
					see raw below
				
			VALUE:
				single (Vs, default)
					returns the last occurrence of the option, or false if not found
				multiple (Vm)
					returns false if not found, otherwise an array of values
				automatic (Va)
					returns false if not found, a string if only once is found,
					an array with all values otherwise
				raw (Vr)
					see raw below
			
			OPTIONAL:
				An option without value is treated as true.
				single (Os, default)
					like Vs, except that the last non true value is used (if there is one)
				multiple (Om)
					like Vm, except that there may be an true to indicate an not given value
				automatic (Oa)
					like Vm, except that there may be an true to indicate an not given value
				raw (Or)
					see raw below


		Parameters:
			The types&names for each parameter has either:
				- an string, elements separated with spaces
				- an array of strings
			The first element of them has to be the type:
				one big and maybe an small char (see types/subtypes)
			All other elements are names for options, either
				- short options (a single letter)
				- long options (a multi letter string or starting with a dash)
			Examples (definition, use)
				"S a" "-a"
				"S arg" "--arg"
				"S -a" "--a"
	
		raw mode:
			Either selected globally with the third option or as subtype "r".
			In this case the return value has an array for each parameter.
			And all occurrence of that parameter are in the array.
			In the array is either an string in the case a value is given,
			or a true in cases where no value is given.
			This mode is primary for development purposes, but free available.
	
		compatibility:
			in the future version some things may change:
			- what's the default subtype for a type
				(you should specify the subtype always)
			- the usage of the third (and maybe even more) parameters
				(use $raw only for debugging, do not use the parameter
				 in productive use)
			- the format of the raw output
	
	*/

	function getopts($params, $args=NULL, $raw=false){
		if(!is_array($params)){
			trigger_error('Invalid parameter table', E_USER_ERROR);
		}
		if($args === NULL){
			$args = $_SERVER['argv'];
		}
		if(!is_array($args)){
			trigger_error('Invalid args table', E_USER_ERROR);
		}
		if(!is_bool($raw)){
			trigger_error('Invalid raw option', E_USER_ERROR);
		}
	
		// output
		$Ores = array();
		$Oerr = array();
		$Oags = array();
		
		// parsed options
		$short = array();
		$long = array();
		$type = array();
		
		// all types & subtypes
		$types_subtypes = array('S' => 'stcr', 'V' => 'smar', 'O' => 'smar');
		
		// parse options
		foreach($params AS $opt => $names){
			if(is_string($names)){
				$names = split(' +', $names);
			}
			if(!is_array($names) || count($names) < 2){
				trigger_error('Invalid names to option "'.$opt.'"', E_USER_ERROR);
			}
			
			$ty = array_shift($names);
			if(strlen($ty) < 1 || strlen($ty) > 2){
				trigger_error('Invalid type to option "'.$opt.'"', E_USER_ERROR);
			}
			$ty0 = $ty[0];
			if(!isset($types_subtypes[$ty0])){
				trigger_error('Invalid type to option "'.$opt.'"', E_USER_ERROR);
			}
			if(strlen($ty) == 1){
				$ty1 = $types_subtypes[$ty0][0];
			}else{
				$ty1 = $ty[1];
				if(strpos($types_subtypes[$ty0], $ty1) === false){
					trigger_error('Invalid type to option "'.$opt.'"', E_USER_ERROR);
				}
			}
			$type[$opt] = $ty0.$ty1;
			
			foreach($names AS $name){
				if(!is_string($name)){
					trigger_error('Invalid names to option "'.$opt.'"', E_USER_ERROR);
				}
				if(!preg_match('!^(-)?([0-9a-zA-Z]+)$!', $name, $r)){
					trigger_error('Invalid option to option "'.$opt.'"', E_USER_ERROR);
				}
				if($r[1] == '-' || strlen($r[2]) > 1){
					if(isset($long[$r[2]])){
						trigger_error('Duplicate option "'.$r[2].'"', E_USER_ERROR);
					}
					$long[$r[2]] = $opt;
				}else{
					if(isset($short[$r[2]])){
						trigger_error('Duplicate option "'.$r[2].'"', E_USER_ERROR);
					}
					$short[$r[2]] = $opt;
				}
			}
			
			$Ores[$opt] = array();
		}
		
		// parse arguments
		for($num=0; $num<count($args); $num++){
			$arg = $args[$num];
			
			if($arg == '--'){
				// end of options, copy all other args
				$num++;
				for(; $num<count($args); $num++){
					$Oags[] = $args[$num];
				}
				break;
			}else if($arg == ''){
				// empty -> skip
				continue;
			}else if($arg[0] != '-'){
				// not an option -> copy to args
				$Oags[] = $arg;
				continue;
			}
			
			// this arg is an option!
			if($arg[1] == '-'){
				// long option
				$p = strpos($arg, '=');
				if($p !== false){
					$next = substr($arg, $p+1);
					$arg = substr($arg, 2, $p-2);
				}else{
					$next = true;
					$arg = substr($arg, 2);
				}
				if(!isset($long[$arg])){
					$Oerr[] = 'Unknown option "--'.$arg.'"';
				}else{
					$opt = $long[$arg];
					switch($type[$opt][0]){
					case 'S':
						$Ores[$opt][] = $next;
						break;
					case 'V':
						if($next !== true){
							$Ores[$opt][] = $next;
						}else if($num+1 >= count($args)){
							$Oags[] = 'Missing artument to option "--'.$arg.'"';
						}else{
							$Ores[$opt][] = $args[$num+1];
							$num++;
						}
						break;
					case 'O':
						$Ores[$opt][] = $next;
						break;
					}
				}
			}else{
				// short option(s)
				for($i=1; $i<strlen($arg); $i++){
					$c = $arg[$i];
					$next = substr($arg, $i+1);
					if($next == ''){
						$next = true;
					}else if($next[0] == '='){
						$next = substr($next, 1);
					}
					if(!isset($short[$c])){
						$Oerr[] = 'Unknown option "-'.$c.'"';
						$i = strlen($arg);
					}else{
						$opt = $short[$c];
						switch($type[$opt][0]){
						case 'S':
							$Ores[$opt][] = true;
							break;
						case 'V':
							if($next !== true){
								$Ores[$opt][] = $next;
							}else if($num+1 >= count($args)){
								$Oerr[] = 'Missing artument to option "-'.$c.'"';
							}else{
								$Ores[$opt][] = $args[$num+1];
								$num++;
							}
							$i = strlen($arg);
							break;
						case 'O':
							$Ores[$opt][] = $next;
							$i = strlen($arg);
							break;
						}
					}
				}
			}
		}

		// reformat result
		if(!$raw){
			foreach($Ores AS $opt => &$r){
				switch($type[$opt]){
				case 'Ss':
					$r = count($r) > 0;
					break;
				case 'St':
					$r = (count($r) & 1) == 1;
					break;
				case 'Sc':
					$r = count($r);
					break;

				case 'Vs':
				case 'Os':
					if(count($r) == 0){
						// none found -> false
						$r = false;
					}else{
						// pick last entry; if possible last used (non true) entry
						do{
							$rr = array_pop($r);
						}while($rr === true && count($r) > 0);
						$r = $rr;
					}
					break;

				case 'Vm':
				case 'Om':
					if(count($r) == 0){
						// none found -> false
						$r = false;
					}else{
						// as array
						// (already done)
					}
					break;
			
				case 'Va':
				case 'Oa':
					// flase if none, direct (string) if only one, array otherwise
					if(count($r) == 0){
						$r = false;
					}else if(count($r) == 1){
						$r = array_pop($r);
					}
					break;
				}
			}
		}
		
		// errors?
		if(count($Oerr) == 0){
			$Oerr = false;
		}
		
		// result
		return array($Oerr, $Ores, $Oags);
	}

?>