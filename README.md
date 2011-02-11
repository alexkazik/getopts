getopts
=======

The goal of this project is to build an alternative
to php's [getopt][], which ich really crippled.

[getopt]: http://www.php.net/manual/en/function.getopt.php

All you need
------------

[Code / Docs](https://github.com/alexkazik/getopts)
[Homepage](http://alex.kazik.de/xxxx)

Example
-------

#### script "test.php"

		<?php
			require_once('getopts.php');
			list($errors, $params, $args) = getopts(array(
				'a' => array('St', 'a'), // param as array
				'b' => 'Vs b long',     // param as string
			));
			if($errors){
				die($errors[0].PHP_EOL);
			}
			echo 'a = '.var_export($params['a'], true).', ';
			echo 'b = '.var_export($params['b'], true).', ';
			echo 'args = '.var_export($args, true).PHP_EOL;
		?>
	
#### sample input 1:

		php test.php -a file

#### output 1:

		a = true, b = false, args = array('file')
	
-a is given, therefore true. -b is not given.
'file' is not attached to an option and returned as args.
			
#### sample input 2:

		php test.php -a --long file -a

#### output 2:

		a = false, b = 'file', args = array()

-a is given twice, and since a is defined as toggle it's off (false).
-b is given (as the long version --long). No other arguments.
