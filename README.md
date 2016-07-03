getopts
=======

The goal of this project is to build an alternative
to php's [getopt][], which ich really crippled.

[getopt]: http://www.php.net/manual/en/function.getopt.php

All you need
------------

* [Code](https://github.com/alexkazik/getopts)
* [Docs](https://github.com/alexkazik/getopts/wiki/Documentation)
* [Issues](https://github.com/alexkazik/getopts/issues)

Example
-------

#### script "test.php"

		<?php
			require_once('GetOpts.php');
			list($errors, $params, $args) = (new GetOpts([
			  'a' => [GetOpts::TOGGLE, 'a'],
			  'b' => [GetOpts::VALUE, 'b', 'long'],
			]))->parse();
			if ($errors) {
			  die($errors[0].PHP_EOL);
			}
			echo 'a = '.var_export($params['a'], true).', ';
			echo 'b = '.var_export($params['b'], true).', ';
			echo 'args = '.var_export($args, true).PHP_EOL;
	
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


License
-------

[Creative Commons Attribution 3.0 Unported License](http://creativecommons.org/licenses/by/3.0/)
