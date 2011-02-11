<?php

	/*

		getopts by ALeX Kazik

		Code / Docs: https://github.com/alexkazik/getopts
		Homepage: http://alex.kazik.de/195/getopts/
	
	*/

	function getopts($params, $args=NULL, $raw=false){
		// check input
		if(!is_array($params)){
			trigger_error('Invalid params table', E_USER_ERROR);
		}
		if($args === NULL && is_array($_SERVER['argv'])){
			$args = $_SERVER['argv'];
			array_shift($args);
		}
		if(!is_array($args)){
			trigger_error('Invalid args table', E_USER_ERROR);
		}
		if(!is_bool($raw)){
			trigger_error('Invalid raw option', E_USER_ERROR);
		}
	
		// substr, which returns '' in case of an empty substr (usually false)
		$substr = create_function(
			'$string,$start,$length=NULL', // is not used, only for definition

			'$ret = call_user_func_array(\'substr\', func_get_args());'.
			'if($ret === false){'.
			'	return \'\';'.
			'}else{'.
			'	return $ret;'.
			'}'
		);

		// get arg (either implicit or the following)
		$get_arg = create_function(
			'&$next,&$args,&$num', // pass by reference: num may be changed, others: performance

			'if($next !== true){'.
			'	return $next;'.
			'}else if($num+1 >= count($args)){'.
			'	return false;'.
			'}else{'.
			'	$num++;'.
			'	return $args[$num];'.
			'}'
		);
	
		// all types & subtypes
		$types_subtypes = array('S' => 'stcr', 'V' => 'smar', 'O' => 'smar', 'A' => 'sr');
		
		// output
		$Ores = array();
		$Oerr = array();
		$Oags = array();
		
		// parsed options
		$short = array();
		$long = array();
		$type = array();
		
		// parse options
		foreach($params AS $opt => $names){
			if(is_string($names)){
				$names = split(' +', $names);
			}
			if(!is_array($names) || count($names) < 2){
				trigger_error('Invalid type/name(s) to param "'.$opt.'"', E_USER_ERROR);
			}
			
			$ty = array_shift($names);
			if(!is_string($ty) || strlen($ty) < 1 || strlen($ty) > 2){
				trigger_error('Invalid type to param "'.$opt.'"', E_USER_ERROR);
			}
			$ty0 = $ty[0];
			if(!isset($types_subtypes[$ty0])){
				trigger_error('Invalid type to param "'.$opt.'"', E_USER_ERROR);
			}
			if(strlen($ty) == 1){
				$ty1 = $types_subtypes[$ty0][0];
			}else{
				$ty1 = $ty[1];
				if(strpos($types_subtypes[$ty0], $ty1) === false){
					trigger_error('Invalid type to param "'.$opt.'"', E_USER_ERROR);
				}
			}
			$type[$opt] = $ty0.$ty1;
			
			foreach($names AS $name){
				if(!is_string($name)){
					trigger_error('Invalid names to param "'.$opt.'"', E_USER_ERROR);
				}
				if(!preg_match('!^(-)?([0-9a-zA-Z]+)$!', $name, $r)){
					trigger_error('Invalid name to param "'.$opt.'"', E_USER_ERROR);
				}
				if($r[1] == '-' || strlen($r[2]) > 1){
					if(isset($long[$r[2]])){
						trigger_error('Duplicate option name "'.$r[2].'"', E_USER_ERROR);
					}
					$long[$r[2]] = $opt;
				}else{
					if(isset($short[$r[2]])){
						trigger_error('Duplicate option name "'.$r[2].'"', E_USER_ERROR);
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
					$next = $substr($arg, $p+1);
					$arg = substr($arg, 2, $p-2);
				}else{
					$next = true;
					$arg = substr($arg, 2);
				}
				if(!isset($long[$arg])){
					$Oerr[] = 'Unknown option "--'.$arg.'"';
				}else{
					$opt = $long[$arg];
					$Earg = '--'.$arg;
					switch($type[$opt][0]){
					case 'S':
						$Ores[$opt][] = $next;
						break;
					case 'V':
						if(($val = $get_arg($next,$args,$num)) === false){
							$Oerr[] = 'Missing artument to option "'.$Earg.'"';
						}else{
							$Ores[$opt][] = $val;
						}
						break;
					case 'O':
						$Ores[$opt][] = $next;
						break;
					case 'A':
						if(($val = $get_arg($next,$args,$num)) === false){
							$Oerr[] = 'Missing artument to option "'.$Earg.'"';
						}else{
							$p = strpos($val, '=');
							if($p === false){
								$Oerr[] = 'Malformed artument to option "'.$Earg.'" (a "=" is missing)';
							}else if(isset($Ores[$opt][substr($val, 0, $p)])){
								$Oerr[] = 'Duplicate key "'.substr($val, 0, $p).'" to option "'.$Earg.'"';
							}else{
								$Ores[$opt][substr($val, 0, $p)] = $substr($val, $p+1);
							}
						}
						break;
					}
				}
			}else{
				// short option(s)
				for($i=1; $i<strlen($arg); $i++){
					$c = $arg[$i];
					$next = $substr($arg, $i+1);
					if($next == ''){
						$next = true;
					}else if($next[0] == '='){
						$next = $substr($next, 1);
					}
					if(!isset($short[$c])){
						$Oerr[] = 'Unknown option "-'.$c.'"';
						$i = strlen($arg);
					}else{
						$opt = $short[$c];
						$Earg = '-'.$c;
						switch($type[$opt][0]){
						case 'S':
							$Ores[$opt][] = true;
							break;
						case 'V':
							if(($val = $get_arg($next,$args,$num)) === false){
								$Oerr[] = 'Missing artument to option "'.$Earg.'"';
							}else{
								$Ores[$opt][] = $val;
							}
							$i = strlen($arg);
							break;
						case 'O':
							$Ores[$opt][] = $next;
							$i = strlen($arg);
							break;
						case 'A':
							if(($val = $get_arg($next,$args,$num)) === false){
								$Oerr[] = 'Missing artument to option "'.$Earg.'"';
							}else{
								$p = strpos($val, '=');
								if($p === false){
									$Oerr[] = 'Malformed artument to option "'.$Earg.'" (a "=" is missing)';
								}else if(isset($Ores[$opt][substr($val, 0, $p)])){
									$Oerr[] = 'Duplicate key "'.substr($val, 0, $p).'" to option "'.$Earg.'"';
								}else{
									$Ores[$opt][substr($val, 0, $p)] = $substr($val, $p+1);
								}
							}
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
					if(count($r) == 0){
						// no option
						$r = false;
					}else{
						// pick last entry
						$r = array_pop($r);
					}
					break;

				case 'Os':
					if(count($r) == 0){
						// no option
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
						// no option
						$r = false;
					}else{
						// as array
						// (already done)
					}
					break;
			
				case 'Va':
				case 'Oa':
					// false if none, direct (string) if only one, array otherwise
					if(count($r) == 0){
						// no option
						$r = false;
					}else if(count($r) == 1){
						// a single option
						$r = array_pop($r);
					}else{
						// as array
						// (already done)
					}
					break;

				case 'As':
					// as array
					// (already done)
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