getopts
=======

getopts - a replacement for php's getopt — 
easier to handle, also returns the other arguments and more.

Requirement
-----------

PHP 5 (tested on PHP 5.3)


Usage
-----

`getopts($params, $args=NULL, $raw=false)`

* `$params`: is a list of options to parse.
	- key: user defined parameter name
	- value: type und name(s) of options
	  (see Parameters)

* `$args`: are the arguments to parse.
	if not set, they default to the arguments
	passed to php.
	
* `$raw`: return all parameters in raw mode.
	(see Raw Mode and Compatibility)


Returns
-------

`list($errors, $params, $args) = getopts(...)`

In case of an error while parsing the parameters,
an error will be triggered.

* `$errors`: is either false if there is no error
	otherwise it's an array of strings describing the errors.
	(usually you'll print the first and exit in case of an error, see Example)
	(the other errors may be subsequent errors)

* `$params`: is an array of all parameters
	- key: user defined parameter name (as defined in the parameter)
	- value: bool, string or array of string/bool, dependent of the type

* `$args`: is the list of all non option arguments.
	All arguments which are passed after a "--" will not
	be parsed but copied directly into this array.
	
	
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


Types
-----

The three and a half basic types:

* SWITCH (S)
	- an switch without an value
	- e.g. -s, -t, --help
	- may be combined (e.g. -ab counts like -a -b)
	
* VALUE (V)
	- an option which requires a value
	- e.g. -v=val, -vval, -v val
	- may be combined (e.g. -av3 counts like -a -v 3)
		
* OPTIONAL (O)
	- like V except:
		+ an value is not required (reported as true in that case)
		+ the format "-o val" is not supported (use -oval, -o=val, -o)

* ASSOCIATIVE (A)
	- an option which requires a key and a value
	- must contain a `=` between the key and the value
	- e.g. -akey=val, -a key=val, -a=key=val


Subtypes
--------

The subtype describes how it should be returned:

* SWITCH
	- simple (Ss, default)

		returns true if the option is one or multiple
		times is given, false otherwise

	- toggle (St)

		each occurrence of the option toggles it on/off
		returns true/false

	- count (Sc)

		counts the occurrence, returns the count (integer)

	- raw (Sr)

		see Raw Mode
	
* VALUE
	- single (Vs, default)

		returns the last occurrence of the option, or false if not found

	- multiple (Vm)

		returns false if not found, otherwise an array of values

	- automatic (Va)

		returns false if not found, a string if only once is found,
		an array with all values otherwise

	- raw (Vr)

		see Raw Mode

* OPTIONAL

	An option without value is returned as true.
	(Instead of a string, to be differentiated. Only `-o=` returns an empty string.)

	- single (Os, default)

		like Vs, except that the last non true value is used (if there is one which is non true)

	- multiple (Om)

		like Vm

	- automatic (Oa)

		like Va

	- raw (Or)

		see Raw Mode

* ASSOCIATIVE
	- simple (As, default)
	
		returns always an array with the keys/values
		
	- raw (Ar)

		see Raw Mode


Parameters
----------

The types & names for each parameter has either:

- an string, elements separated with spaces
- an array of strings

The first element of them has to be the type:

- one big and maybe an small letter (see Types/Subtypes)

All other elements are names for options, either:

- short options (a single letter)
- long options (more than one letter or starting with a dash)

Examples (definition and use):

		"S a"   "-a"
		"S arg" "--arg"
		"S -a"  "--a"


Raw Mode
--------

Either selected globally with the third option or as subtype "r".
In this case the return value has an array for each parameter.
And all occurrence of that parameter are in the array.
In the array is either an string in the case a value is given,
or a true in cases where no value is given.
This mode is primary for development purposes, but free available.


Compatibility
-------------

In the future version some things may change:

- what's the default subtype for a type
	(you should specify the subtype always)
- the usage of the third (and maybe even more) parameters
	(use `$raw` only for debugging, do not use the parameter
	 in productive use)
- the format of the raw output


Get involved
------------

just leave me a comment on my [homepage][].

[homepage]:  http://alex.kazik.de/195/getopts/
