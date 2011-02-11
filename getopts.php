<?php

	/*

		getopts by ALeX Kazik

		Code / Docs: https://github.com/alexkazik/getopts
		Homepage: http://alex.kazik.de/195/getopts/
	
	*/

	function getopts($params, $args=NULL, $raw=false){
		if(!is_array($params)){
			trigger_error('Invalid parameter table', E_USER_ERROR);
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
	
		// helper
		$nf_substr = create_function('$a,$b,$c=NULL', 'if($c === NULL){$d = substr($a,$b);}else{$d = substr($a,$b,$c);} if($d === false){$d = \'\';} return $d;');
	
		// output
		$Ores = array();
		$Oerr = array();
		$Oags = array();
		
		// parsed options
		$short = array();
		$long = array();
		$type = array();
		
		// all types & subtypes
		$types_subtypes = array('S' => 'stcr', 'V' => 'smar', 'O' => 'smar', 'A' => 'sr');
		
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
					$next = $nf_substr($arg, $p+1);
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
							$Oerr[] = 'Missing artument to option "--'.$arg.'"';
						}else{
							$Ores[$opt][] = $args[$num+1];
							$num++;
						}
						break;
					case 'O':
						$Ores[$opt][] = $next;
						break;
					case 'A':
						if($next !== true){
							// $next is correct
						}else if($num+1 >= count($args)){
							$Oerr[] = 'Missing artument to option "--'.$arg.'"';
						}else{
							$next = $args[$num+1];
							$num++;
						}
						if($next !== true){
							$p = strpos($next, '=');
							if($p === false){
								$Oerr[] = 'Malformed artument to option "--'.$arg.'" (a "=" is missing)';
							}else{
								$Ores[$opt][substr($next, 0, $p)] = $nf_substr($next, $p+1);
							}
						}
						break;
					}
				}
			}else{
				// short option(s)
				for($i=1; $i<strlen($arg); $i++){
					$c = $arg[$i];
					$next = $nf_substr($arg, $i+1);
					if($next == ''){
						$next = true;
					}else if($next[0] == '='){
						$next = $nf_substr($next, 1);
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
						case 'A':
							if($next !== true){
								// $next is correct
							}else if($num+1 >= count($args)){
								$Oerr[] = 'Missing artument to option "-'.$c.'"';
							}else{
								$next = $args[$num+1];
								$num++;
							}
							if($next !== true){
								$p = strpos($next, '=');
								if($p === false){
									$Oerr[] = 'Malformed artument to option "--'.$arg.'" (a "=" is missing)';
								}else{
									$Ores[$opt][substr($next, 0, $p)] = $nf_substr($next, $p+1);
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
					// false if none, direct (string) if only one, array otherwise
					if(count($r) == 0){
						$r = false;
					}else if(count($r) == 1){
						$r = array_pop($r);
					}
					break;

				case 'As':
					// already done
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