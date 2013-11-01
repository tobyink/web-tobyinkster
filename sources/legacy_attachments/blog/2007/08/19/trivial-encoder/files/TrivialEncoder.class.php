<?php

/**
 * PHP Trivial Encryption Classes
 * Copyright (C) 2007 Toby Inkster
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Toby Inkster
 * @copyright Copyright (C) 2007 Toby Inkster
 * @package TrivialEncoder
 * @version 0.2
 * @link http://tobyinkster.co.uk/tag/trivial-encoder/
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public Licence
 */

/* Disable notices because they cause problems when MCrypt is not available. */
// error_reporting(E_ALL ^ E_NOTICE);

class TE_Init_Exception extends Exception { }
class TE_Crypto_Exception extends Exception { }
class TE_Manager_Exception extends Exception { }

define('ASCII_SAFE_NEVER', 0);
define('ASCII_SAFE_PASSTHRU', 0.5);
define('ASCII_SAFE_ALWAYS', 1);

define('PARAMS_NONE', 0);
define('PARAMS_NUMBER', 1);
define('PARAMS_KEYWORD', 2);
define('PARAMS_UCASE', 4);
define('PARAMS_MISC', 1024);

define('STRENGTH_TRIVIAL',  1.1);
define('STRENGTH_WEAK',     1.5);
define('STRENGTH_MEDIUM',   2.0);
define('STRENGTH_STRONG',   4.0);
define('STRENGTH_UNKNOWN',  0.0);

/**
 * Deprecated Trivial Encoder Manager.
 *
 * Use TE_Machine instead.
 */
class TrivialEncoderManager
{
	private $encoders = array();
	
	public function __construct ()
	{
		$classes = get_declared_classes();
		foreach ($classes as $c)
			if (is_subclass_of($c, 'TrivialEncoder'))
				$this->encoders[ eval("return $c::CODE;") ] = $c;
		foreach ($this->encoders as $k=>$v)
			if (substr($k,0,1)=='!')
				unset($this->encoders[$k]);
		ksort($this->encoders);
	}
	
	public function encode ($method, $text)
	{
		$steps = explode(';', $method);
		foreach ($steps as $s)
		{
			$options = explode(' ', $s);
			$code  = strtolower(trim(array_shift($options)));
			$class = $this->encoders[$code];
			
			if (!strlen($class))
				throw new TE_Manager_Exception("Encoder '$code' not found.");
			
			$encoder = new $class($options);
			$text = $encoder->encode($text);
		}
		return $text;
	}

	public function decode ($method, $text)
	{
		$steps = array_reverse(explode(';', $method));
		foreach ($steps as $s)
		{
			$options = explode(' ', $s);
			$code  = strtolower(trim(array_shift($options)));
			$class = $this->encoders[$code];
			
			if (!strlen($class))
				throw new TE_Manager_Exception("Encoder '$code' not found.");
			
			$encoder = new $class($options);
			$text = $encoder->decode($text);
		}
		return $text;
	}
}

abstract class TrivialEncoder
{
	const NAME           = 'Abstract';
	const CODE           = '!';
	const LINK           = 'http://tobyinkster.co.uk/blog/';
	const ASCII_SAFENESS = ASCII_SAFE_NEVER;
	const REQUIRE_MCRYPT = FALSE;
	const PARAMS         = PARAMS_NONE;
	const STRENGTH       = STRENGTH_UNKNOWN;
	
	abstract public function __construct ($options);
	abstract public function encode ($text);
	abstract public function decode ($code);
	public function explain () { return "No description is available."; }
}

class TrivialEncoder_Rot extends TrivialEncoder
{
	const NAME           = 'Alphabetic Rotation';
	const CODE           = 'rot';
	const LINK           = 'http://en.wikipedia.org/wiki/Caesar_cipher';
	const ASCII_SAFENESS = ASCII_SAFE_PASSTHRU;
	const PARAMS         = PARAMS_NUMBER;
	const STRENGTH       = STRENGTH_TRIVIAL;
	
	protected $n = 0;
	protected static $alphabet = 'abcdefghijklmnopqrstuvwxyz';
	
	public function __construct ($options)
	{
		if (isset($options[0]))
			$this->n = (int)$options[0] % 26;
	}
	
	public function encode ($text)
	{
		$rotAlpha = substr(self::$alphabet, $this->n)
			. substr(self::$alphabet, 0, $this->n);
		$from = self::$alphabet.strtoupper(self::$alphabet);
		$to   = $rotAlpha.strtoupper($rotAlpha);
		
		$code = strtr($text, $from, $to);
		return $code;
	}
	
	public function decode ($code)
	{
		$this->n = (26 - $this->n) % 26;
		$text = $this->encode($code);
		$this->n = (26 - $this->n) % 26;
		return $text;
	}
	
	public function explain ()
	{
		return wordwrap("'rot' is a group of substitution cyphers in which each letter in the plaintext is replaced by a letter some fixed number of positions further down the alphabet. For example, with a shift of 3, 'A' would be replaced by 'D', 'B' would become 'E', and so on.\n\n"
				."Note that this only changes letters in the message.\n\n"
				."Examples:\n\n"
				."'rot 5'\n"
				."Will rotate each letter five places.\n\n"
				."'rot 25'\n"
				."'rot -1'\n"
				."These are exactly equivalent."
				);
	}
}

class TrivialEncoder_Rot13 extends TrivialEncoder_Rot
{
	const NAME     = 'ROT-13';
	const CODE     = 'rot13';
	const LINK     = 'http://en.wikipedia.org/wiki/Rot-13';
	const PARAMS   = PARAMS_NONE;
	const STRENGTH = STRENGTH_TRIVIAL;

	protected $n = 13;
	public function __construct ($options) { /* no options */ }

	public function explain ()
	{
		return parent::explain()."\n\n".wordwrap("'rot13' shifts the alphabet by 13 places, half the length of the alphabet, thus being its own inverse.");
	}
}

class TrivialEncoder_Caesar extends TrivialEncoder_Rot
{
	const NAME     = 'Caesar\'s Cypher';
	const CODE     = 'caesar';
	const LINK     = 'http://en.wikipedia.org/wiki/Caesar_cipher';
	const PARAMS   = PARAMS_NONE;
	const STRENGTH = STRENGTH_TRIVIAL;
	
	protected $n = 3;
	public function __construct ($options) { /* no options */ }

	public function explain ()
	{
		return parent::explain()."\n\n".wordwrap("'caesar' is the cypher used by Julius Caesar to communicate with his generals. It uses a shift of three places.");
	}
}

class TrivialEncoder_Rot47 extends TrivialEncoder
{
	const NAME           = 'ROT-47';
	const CODE           = 'rot47';
	const LINK           = 'http://en.wikipedia.org/wiki/Rot-13#Variants';
	const ASCII_SAFENESS = ASCII_SAFE_PASSTHRU;
	const PARAMS         = PARAMS_NUMBER;
	const STRENGTH       = STRENGTH_TRIVIAL;
	
	protected $n = 47;
	protected static $alphabet = '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
	
	public function __construct ($options)
	{
		if (isset($options[0]))
			$this->n = (int)$options[0] % 94;
	}
	
	public function encode ($text)
	{			
		$rotAlpha = substr(self::$alphabet, $this->n)
			. substr(self::$alphabet, 0, $this->n);
		
		$code = strtr($text, self::$alphabet, $rotAlpha);
		return $code;
	}
	
	public function decode ($code)
	{
		$this->n = (94 - $this->n) % 94;
		$text = $this->encode($code);
		$this->n = (94 - $this->n) % 94;
		return $text;
	}

	public function explain ()
	{
		return wordwrap("This is a family of cyphers that operate similarly to the alphabetic rotation cyphers. However, instead of just operating on the alphabet, they operate on the 94 most common printable ASCII characters. This provides a slightly stronger encryption, especially for text that includes significant numeric information.\n\n"
				."Although the default is to rotate text 47 characters (half of 94, and thus its own inverse), you may pass a numeric parameter to this function to rotate it a different number of places."
				);
	}
}

class TrivialEncoder_XOR extends TrivialEncoder
{
	const NAME     = 'Simple XOR Encryption';
	const CODE     = 'xor';
	const PARAMS   = PARAMS_NUMBER;
	const STRENGTH = STRENGTH_WEAK;

	protected $n = 255;
	
	public function __construct ($options)
	{
		if (isset($options[0]))
			$this->n = (int)$options[0] % 256;
	}
	
	public function encode ($text)
	{
		$code = '';
		
		while (strlen($text))
		{
			$char = substr($text, 0, 1);
			$text = substr($text, 1);
			
			$code .= chr($this->n ^ ord($char));
		}
		
		return $code;
	}
	
	public function decode ($code)
	{
		return $this->encode($code);
	}
	
	public function explain ()
	{
		return wordwrap("'xor N' performs a bitwise XOR operation with a given number N on each character of the message. Although this is a pretty simple encoding scheme, it's a little better than the 'rot' family of encodings, and works well on binary data. Default N is 255.\n\n"
				."Examples:\n\n"
				."'xor 13'\n"
				."Will XOR each byte with the binary 00001101. Will encode 'ABCDE' to 'LONIH'.\n\n"
				."'xor 0'\n"
				."Does nothing."
				."'xor 19; xor 19'\n"
				."XOR encodings are their own inverse, so this does nothing too."
				);
	}
}

class TrivialEncoder_Memfrob extends TrivialEncoder_XOR
{
	const NAME     = 'Memfrob Frobnication';
	const CODE     = 'memfrob';
	const LINK     = 'http://www.gnu.org/software/libc/manual/html_node/Trivial-Encryption.html';
	const PARAMS   = PARAMS_NONE;
	const STRENGTH = STRENGTH_TRIVIAL;

	protected $n = 42;

	public function __construct ($options) { /* no options */ }
	
	public function explain ()
	{
		return parent::explain()."\n\n".wordwrap("'memfrob' is the equivalent of 'xor 42'. (According to Douglas Adams, 42 is the answer to life, the universe and everything.)");
	}
}

class TrivialEncoder_Keyword extends TrivialEncoder
{
	const NAME           = 'Keyword Cypher';
	const CODE           = 'key';
	const LINK           = 'http://en.wikipedia.org/wiki/Keyword_cipher';
	const ASCII_SAFENESS = ASCII_SAFE_PASSTHRU;
	const PARAMS         = PARAMS_KEYWORD;
	const STRENGTH       = STRENGTH_WEAK;
	
	protected $key = 'kryptos';
	protected static $alphabet = 'abcdefghijklmnopqrstuvwxyz';

	public function __construct ($options)
	{
		if (isset($options[0]))
		{
			$gotit = array();
			$key = strtolower($options[0]);
			$key = preg_replace('/[^a-z]/', '', $key);
			if (strlen($key))
				$this->key = '';
			while (strlen($key))
			{
				$char = substr($key, 0, 1);
				$key  = substr($key, 1);
				if (isset($gotit[$char]))
					continue;
				$gotit[$char] = 1;
				$this->key .= $char;
			}
		}
	}
	
	public function encode ($text)
	{
		$to   = $this->make_keystring();
		$from = self::$alphabet;
		$to   = $to   . strtoupper($to);
		$from = $from . strtoupper($from);
		return strtr($text, $from, $to);
	}
	
	public function decode ($code)
	{
		$to   = $this->make_keystring();
		$from = self::$alphabet;
		$to   = $to   . strtoupper($to);
		$from = $from . strtoupper($from);
		return strtr($code, $to,  $from);
	}
	
	protected function make_keystring ()
	{
		return $this->key.preg_replace('/['.$this->key.']/i','', self::$alphabet);
	}
}

class TrivialEncoder_OneTimePad_Xor extends TrivialEncoder
{
	const NAME     = 'One Time Pad (Xor)';
	const CODE     = 'otpx';
	const LINK     = 'http://en.wikipedia.org/wiki/One-time_pad';
	const STRENGTH = STRENGTH_WEAK;
	
	public function __construct () { /* NOOP */ }
	
	public function encode ($text)
	{
		$sheet = self::get_sheet(strlen($text));
		return $sheet . self::use_sheet($text, $sheet);
	}
	
	public function decode ($code)
	{
		if (strlen($code)%2 != 0)
			throw new TE_Crypto_Exception("Input not valid. Wrong length: ".strlen($code).".");
	
		$sheet = substr($code, 0, strlen($code)/2);
		$code  = substr($code, strlen($code)/2);

		return self::use_sheet($code, $sheet);
	}
	
	public function explain ()
	{
		return wordwrap("The one time pad is a very old, but incredibly effective form of encryption. The only problem is that it's symmetric, with a very big key. To implement it in a library such as this, the key needs to be included as part of the result, which makes this algorithm as secure as a locked door where they key has been left in the lock.\n\n"
				."This algorithm combines the plaintext with the pad sheet using the XOR operation. See also 'otp'.\n\n"
				."For a one time pad algorithm with very good security, see 'multix'."
				);
	}

	public static function get_sheet ($len)
	{
		$retval = '';
		for ( $i=0 ; $i<$len ; $i++ )
			$retval .= chr(rand(0,255));
		return $retval;
	}
	
	public static function use_sheet ($a, $b)
	{
		if (strlen($a)!=strlen($b))
			throw new TE_Crypto_Exception("Input not valid. Pad sheet and crypted text differ in length.");

		$retval = '';
		while (strlen($a) && strlen($b))
		{
			$A = substr($a, 0, 1); $a = substr($a, 1);
			$B = substr($b, 0, 1); $b = substr($b, 1);
			
			$retval .= $A ^ $B;
		}
		
		return $retval;
	}
}

class TrivialEncoder_OneTimePad_Mod extends TrivialEncoder_OneTimePad_Xor
{
	const NAME     = 'One Time Pad (Modulo)';
	const CODE     = 'otp';
	const STRENGTH = STRENGTH_WEAK;

	public function encode ($text)
	{
		$sheet = self::get_sheet(strlen($text));
		return $sheet . self::use_sheet($text, $sheet);
	}
	
	public function decode ($code)
	{
		if (strlen($code)%2 != 0)
			throw new TE_Crypto_Exception("Input not valid. Wrong length: ".strlen($code).".");
	
		$sheet = substr($code, 0, strlen($code)/2);
		$code  = substr($code, strlen($code)/2);

		return self::use_sheet($code, $sheet, TRUE);
	}

	public function explain ()
	{
		return wordwrap("The one time pad is a very old, but incredibly effective form of encryption. The only problem is that it's symmetric, with a very big key. To implement it in a library such as this, the key needs to be included as part of the result, which makes this algorithm as secure as a locked door where they key has been left in the lock.\n\n"
				."This algorithm combines the plaintext with the pad sheet using modulo 256 addition. See also 'otpx'.\n\n"
				."For a one time pad algorithm with very good security, see 'multi'."
				);
	}
	
	public static function use_sheet ($a, $b, $subtract=FALSE)
	{
		if (strlen($a)!=strlen($b))
			throw new TE_Crypto_Exception("Input not valid. Pad sheet and crypted text differ in length.");

		$retval = '';
		while (strlen($a) && strlen($b))
		{
			$A = substr($a, 0, 1); $a = substr($a, 1);
			$B = substr($b, 0, 1); $b = substr($b, 1);
			
			if ($subtract)
				$retval .= chr((ord($A) - ord($B)) % 256);
			else
				$retval .= chr((ord($A) + ord($B)) % 256);
		}
		
		return $retval;
	}
}

class TrivialEncoder_Multi_Mod extends TrivialEncoder
{
	const NAME     = 'Multi-OTP (Modulo)';
	const CODE     = 'multi';
	const LINK     = 'http://en.wikipedia.org/wiki/Superencryption';
	const PARAMS   = PARAMS_MISC;
	const STRENGTH = STRENGTH_MEDIUM;

	protected $methods = array();
	protected $otp = 'TrivialEncoder_OneTimePad_Mod';
	
	public function __construct ($options)
	{
		if (!class_exists('TE_Machine'))
			throw new TE_Init_Exception("This encoding method can only be used in conjunction with TE_Machine, not TrivialEncoderManager.");
	
		foreach ($options as $i=>$x)
		{
			if ($x instanceof TE_Ast_Script)
				$this->methods[] = $x;
			else
				throw new TE_Init_Exception("Wrong parameter type: parameter $i should be of type TE_Ast_Script.");
		}
	}
	
	public function encode ($text)
	{
		$count = count($this->methods);
		$textlen = strlen($text);
		
		$encrypted = $text;
		$pads = array();

		/* For each method */
		for ($i=0; $i<$count; $i++)
		{
			/* Get a one time pad sheet. */
			$pads[$i]   = call_user_func(array($this->otp, 'get_sheet'), $textlen);
			
			/* Encrypt the data using the sheet. */
			$encrypted  = call_user_func(array($this->otp, 'use_sheet'), $encrypted, $pads[$i]);

			/* Encrypt that sheet using the method. */
			$machine = TE_Machine_Encoder;
			$machine->set_buffer($pads[$i]);
			$this->methods[$i]->evaluate($machine);
			$cpads[$i]  = $machine->get_buffer();

			/* Store the length of the sheet. */
			$lcpads[$i] = strlen($cpads[$i]);
		}
		
		/* Return concatenated data. */
		return '['.join(' ', $lcpads).']'.join('',$cpads).$encrypted;
	}
	
	public function decode ($code)
	{
		if (!preg_match('/\[([0-9 ]+)\]/', $code, $matches))
			throw new TE_Crypto_Exception('No part length codes included.');
		$code = substr($code, strlen($matches[0]));
		$lcpads = explode(' ', $matches[1]);

		for ($i=0; isset($lcpads[$i]); $i++)
		{
			$length    = $lcpads[$i];
			$cpads[$i] = substr($code, 0, $length);
			$code      = substr($code, $length);

			/* Encrypt the sheet using the method. */
			$machine = new TE_Machine_Decoder;
			$machine->set_buffer($cpads[$i]);
			$this->methods[$i]->evaluate($machine);
			$pads[$i]  = $machine->get_buffer();
		}
		foreach ($pads as $p)
			$code = call_user_func(array($this->otp, 'use_sheet'), $code, $p, TRUE);
		return $code;
	}

	public function calc_strength ($base)
	{
		$retval = $base;
		foreach ($this->methods as $method)
		{
			$machine = new TE_Machine_StrengthCalculator;
			$machine->set_buffer(1);
			$method->evaluate($machine);
			$retval *= $machine->get_buffer();
		}
		return $retval;
	}
	
	public function explain ()
	{
		return wordwrap("This algorithm allows you to \"fork\" the encryption in two different directions. While normally cryptographic algorithms are pipelined with each other, this uses an algorithm described in Bruce Schneier's _Applied Cryptography, Second Edition: Protocols, Algorithms, and Source Code in C_ to apply two or more algorithms to the message simultaneously.\n\n"
				."Two or more one time pad sheets are applied to the message. The sheets are then encrypted using different cryptographic algorithms, which must be specified in parentheses, and then the encrypted pads and encrypted message are all concatenated together. A small header is prepended to the result to record the lengths of the encrypted keys to aid with decryption.\n\n"
				."Examples:\n\n"
				."'multi (twofish), (morse; memfrob); base64'\n"
				."Will apply two one time pad sheets to the message. The first sheet will be encrypted with Twofish; the second sheet will be encrypted by morse code and then memfrob. The two encrypted sheets will be concatenated with the encrypted message. The result will be encoded with Base64.\n\n"
				."'multi (twofish \"Password1\"), (rijndael512 \"Password2\"), (cast256 \"Password3\");'\n"
				."You really don't want this message to be decrypted. Short of cracking three world-class encryption schemes, the only way this code can be broken is by guessing your three passwords.\n\n"
				."(This information applies equally to 'multi' and 'multix'.)"
				);
	}
}

class TrivialEncoder_Multi_Xor extends TrivialEncoder_Multi_Mod
{
	const NAME     = 'Multi-OTP (Xor)';
	const CODE     = 'multix';
	const PARAMS   = PARAMS_MISC;
	const STRENGTH = STRENGTH_MEDIUM;

	protected $methods = array();
	protected $otp = 'TrivialEncoder_OneTimePad_Xor';
}

class TrivialEncoder_Hex extends TrivialEncoder
{
	const NAME           = 'Hexadecimal';
	const CODE           = 'hex';
	const LINK           = 'http://en.wikipedia.org/wiki/Hexadecimal';
	const ASCII_SAFENESS = ASCII_SAFE_ALWAYS;
	const STRENGTH       = STRENGTH_TRIVIAL;
	
	protected $fstr      = '%02x';
	protected $dstr      = '%x';
	protected $chunksize = 2;
	
	public function __construct ($options) { /* no options */ }

	public function encode ($text)
	{
		$code = '';
		while (strlen($text))
		{
			$char = substr($text, 0, 1);
			$text = substr($text, 1);
			
			$code .= sprintf($this->fstr, ord($char));
		}
		
		return $code;
	}
	
	public function decode ($code)
	{
		$text = '';
		while (strlen($code))
		{
			$char = substr($code, 0, $this->chunksize);
			$code = substr($code, $this->chunksize);
			
			list($dechar) = sscanf($char, $this->dstr);
			$text .= chr($dechar);
		}
		
		return $text;
	}

	public function explain ()
	{
		return wordwrap("Converts input from ASCII (base 256) to hexadecimal (base 16). Doubles message length. This is not a strong encoding, but can be used to force the message to be ASCII-safe, as all output will be alphanumeric.");
	}
}

class TrivialEncoder_Decimal extends TrivialEncoder_Hex
{
	const NAME     = 'Decimal';
	const CODE     = 'dec';
	const LINK     = 'http://en.wikipedia.org/wiki/Decimal';
	const STRENGTH = STRENGTH_TRIVIAL;
	
	protected $fstr      = '%03d';
	protected $dstr      = '%d';
	protected $chunksize = 3;

	public function explain ()
	{
		return wordwrap("Converts input from ASCII (base 256) to decimal (base 10). Triples message length. This is not a strong encoding, but can be used to force the message to be ASCII-safe, as all output will be numeric.");
	}
}

class TrivialEncoder_Octal extends TrivialEncoder_Hex
{
	const NAME     = 'Octal';
	const CODE     = 'oct';
	const LINK     = 'http://en.wikipedia.org/wiki/Octal';
	const STRENGTH = STRENGTH_TRIVIAL;
	
	protected $fstr      = '%03o';
	protected $dstr      = '%o';
	protected $chunksize = 3;

	public function explain ()
	{
		return wordwrap("Converts input from ASCII (base 256) to octal (base 8). Triples message length. This is not a strong encoding, but can be used to force the message to be ASCII-safe, as all output will be numeric.");
	}
}

class TrivialEncoder_Binary extends TrivialEncoder_Hex
{
	const NAME     = 'Binary';
	const CODE     = 'bin';
	const LINK     = 'http://en.wikipedia.org/wiki/Binary_numeral_system';
	const STRENGTH = STRENGTH_TRIVIAL;
	
	protected $fstr      = '%08b';
	protected $dstr      = '%b';
	protected $chunksize = 8;
	
	/* sscanf($text, '%b') doesn't seem to work */
	public function decode ($code)
	{	
		$text = '';
		while (strlen($code))
		{
			$char   = substr($code, 0, 8);
			$code   = substr($code, 8);
			$dechar = $this->strbin2n($char);
			$text .= chr($dechar);
		}
		return $text;
	}
	
	private function strbin2n ($bin)
	{
		$last = strlen($bin)-1;
		$x = 0;
		for($i=0; $i<=$last; $i++)
			$x += (substr($bin, $last-$i, 1) * pow(2,$i));
		return $x;
	}

	public function explain ()
	{
		return wordwrap("Converts input from ASCII (base 256) to binary (base 2). Increases message length eight-fold. This is not a strong encoding, but can be used to force the message to be ASCII-safe, as all output will be numeric.");
	}
}

class TrivialEncoder_UU extends TrivialEncoder
{
	const NAME           = 'UUEncoding';
	const CODE           = 'uue';
	const LINK           = 'http://en.wikipedia.org/wiki/Uuencoding';
	const ASCII_SAFENESS = ASCII_SAFE_ALWAYS;
	const STRENGTH       = STRENGTH_TRIVIAL;
	
	public function __construct ($options) { /* no options */ }
	public function encode ($text) { return convert_uuencode($text); }
	public function decode ($code) { return convert_uudecode($code); }
}

class TrivialEncoder_Base64 extends TrivialEncoder
{
	const NAME           = 'Base 64';
	const CODE           = 'base64';
	const LINK           = 'http://en.wikipedia.org/wiki/Base64';
	const ASCII_SAFENESS = ASCII_SAFE_ALWAYS;
	const STRENGTH       = STRENGTH_TRIVIAL;
	
	public function __construct ($options) { /* no options */ }
	public function encode ($text) { return base64_encode($text); }
	public function decode ($code) { return base64_decode($code); }
}

class TrivialEncoder_URL extends TrivialEncoder
{
	const NAME           = 'URL Encoding';
	const CODE           = 'url';
	const LINK           = 'http://www.ietf.org/rfc/rfc1738.txt';
	const ASCII_SAFENESS = ASCII_SAFE_ALWAYS;
	const STRENGTH       = STRENGTH_TRIVIAL;
	
	public function __construct ($options) { /* no options */ }
	public function encode ($text) { return urlencode($text); }
	public function decode ($code) { return urldecode($code); }
}

class TrivialEncoder_HTMLEnt extends TrivialEncoder
{
	const NAME           = 'HTML Entities';
	const CODE           = 'html';
	const LINK           = 'http://www.w3.org/TR/REC-html40/sgml/entities.html';
	const ASCII_SAFENESS = ASCII_SAFE_ALWAYS;
	const STRENGTH       = STRENGTH_TRIVIAL;
	
	public function __construct ($options) { /* no options */ }
	public function encode ($text) { return htmlspecialchars($text); }
	public function decode ($code) { return htmlspecialchars_decode($code); }
}

class TrivialEncoder_Morse extends TrivialEncoder
{
	const NAME           = 'Morse Code';
	const CODE           = 'morse';
	const LINK           = 'http://en.wikipedia.org/wiki/Morse_code';
	const ASCII_SAFENESS = ASCII_SAFE_PASSTHRU;
	const STRENGTH       = STRENGTH_TRIVIAL;

	protected $CAPS_LOCK_STRING = 'CAPSLOCK';
	protected $EXPLODE_STRING   = '/';

	protected $morse = array(
		'A' => '._',
		'B' => '_...',
		'C' => '_._.',
		'D' => '_..',
		'E' => '.',
		'F' => '.._.',
		'G' => '__.',
		'H' => '....',
		'I' => '..',
		'J' => '.___',
		'K' => '_._',
		'L' => '._..',
		'M' => '__',
		'N' => '_.',
		'O' => '___',
		'P' => '.__.',
		'Q' => '__._',
		'R' => '._.',
		'S' => '...',
		'T' => '_',
		'U' => '.._',
		'V' => '..._',
		'W' => '.__',
		'X' => '_.._',
		'Y' => '_.__',
		'Z' => '__..',
		'0' => '_____',
		'1' => '.____',
		'2' => '..___',
		'3' => '...__',
		'4' => '...._',
		'5' => '.....',
		'6' => '_....',
		'7' => '__...',
		'8' => '___..',
		'9' => '____.',
		'.' => '._._._',
		',' => '__..__',
		':' => '___...',
		';' => '_._._.',
		'?' => '..__..',
		"'" => '.____.',
		'!' => '_._.__',
		'+' => '._._.',
		'-' => '_...._',
		'_' => '..__._',
		'/' => '_.._.',
		'(' => '_.__.',
		')' => '_.__._',
		'$' => '..._.._',
		'"' => '._.._.',
		'@' => '.__._.',
		'&' => '._...',
		'=' => '_..._'
	);
	private $unmorse;
	private $caps_lock = FALSE;
	
	public function __construct ($options)
	{
		$this->unmorse = array_flip($this->morse);
	}
	
	public function encode ($text)
	{
		$this->caps_lock = FALSE;
		$retval = array();
		
		while (strlen($text))
		{
			$char = substr($text, 0, 1);
			$text = substr($text, 1);
			
			if (preg_match('/[A-Z]/', $char) && !$this->caps_lock)
			{
				$retval[] = $this->CAPS_LOCK_STRING;
				$this->caps_lock = TRUE;
			}
			elseif (preg_match('/[a-z]/', $char) && $this->caps_lock)
			{
				$retval[] = $this->CAPS_LOCK_STRING;
				$this->caps_lock = FALSE;
			}
			
			if (preg_match('/[a-z]/', $char))
				$char = strtoupper($char);

			if (isset($this->morse[$char]))
				$retval[] = $this->morse[$char];
			elseif ($char==' ')
				$retval[] = '';
			else
				$retval[] = $char;
		}
		
		return implode($this->EXPLODE_STRING, $retval);
	}
	
	public function decode ($code)
	{
		$this->caps_lock = FALSE;
		$retval = '';
		
		foreach(explode($this->EXPLODE_STRING, $code) as $char)
		{
			if ($char==$this->CAPS_LOCK_STRING)
			{
				$this->caps_lock = !$this->caps_lock;
				continue;
			}
			
			if (!strlen($char))
				$retval .= ' ';
			elseif (isset($this->unmorse[$char]))
				$retval .= $this->caps_lock?
					strtoupper($this->unmorse[$char]):
					strtolower($this->unmorse[$char]);
			else
				$retval .= $char;
		}
		
		return $retval;
	}

	public function explain ()
	{
		return wordwrap("Morse code is the encoding scheme used by telegraph operators for years. It's not meant to obscure the message too much, but like the phonetic encoding can add a touch of flair to your encoded message if used as the final step in a chain.\n\n"
				."Examples:\n\n"
				."'otp; vig \"alphabettispaghetti\"; base64; morse;'\n"
				."This should provide a very good encrytion via the combination of a one time pad and Vigenere encoding, make the result ASCII-safe, and print out the result in morse code."
				);
	}

}

class TrivialEncoder_Phonetic extends TrivialEncoder_Morse
{
	const NAME   = 'Phonetic Alphabet';
	const CODE   = 'phonetic';
	const LINK   = 'http://en.wikipedia.org/wiki/NATO_phonetic_alphabet';
	const PARAMS = PARAMS_UCASE;

	protected $CAPS_LOCK_STRING = 'CAPSLOCK';
	protected $EXPLODE_STRING   = ' ';

	protected $case = '';

	protected $morse = array(
		'A' => 'ALPHA',
		'B' => 'BRAVO',
		'C' => 'CHARLIE',
		'D' => 'DELTA',
		'E' => 'ECHO',
		'F' => 'FOXTROT',
		'G' => 'GOLF',
		'H' => 'HOTEL',
		'I' => 'INDIGO',
		'J' => 'JULIET',
		'K' => 'KILO',
		'L' => 'LIMA',
		'M' => 'MIKE',
		'N' => 'NOVEMBER',
		'O' => 'OSCAR',
		'P' => 'PAPA',
		'Q' => 'QUEBEC',
		'R' => 'ROMEO',
		'S' => 'SIERRA',
		'T' => 'TANGO',
		'U' => 'UNIFORM',
		'V' => 'VICTOR',
		'W' => 'WHISKEY',
		'X' => 'X-RAY',
		'Y' => 'YANKEE',
		'Z' => 'ZEBRA',
		'0' => 'NAUGHT',
		'1' => 'ONE',
		'2' => 'TWO',
		'3' => 'THREE',
		'4' => 'FOUR',
		'5' => 'FIVE',
		'6' => 'SIX',
		'7' => 'SEVEN',
		'8' => 'EIGHT',
		'9' => 'NINER',
		'.' => 'STOP',
		',' => 'COMMA',
		'?' => 'QUESTION',
		'!' => 'BANG',
		'#' => 'HASH',
		':' => 'COLON',
		';' => 'SEMICOLON',
		'@' => 'AT',
		'&' => 'AMPERSAND',
		'%' => 'PERCENT',
		'$' => 'DOLLAR',
		'£' => 'POUND',
		'^' => 'CARET',
		'*' => 'ASTERISK',
		'(' => 'OPEN-PARENTHESIS',
		')' => 'CLOSE-PARENTHESIS',
		'[' => 'OPEN-BRACKET',
		']' => 'CLOSE-BRACKET',
		'{' => 'OPEN-BRACE',
		'}' => 'CLOSE-BRACE',
		'~' => 'TILDE',
		'|' => 'PIPE',
		"'" => 'SINGLE-QUOTE',
		'"' => 'DOUBLE-QUOTE',
		'`' => 'BACKTICK',
		'-' => 'DASH',
		'+' => 'PLUS',
		'=' => 'EQUALS',
		'_' => 'UNDERSCORE',
		'/' => 'SLASH',
		"\\"=> 'BACKSLASH',
		'<' => 'LESS-THAN',
		'>' => 'MORE-THAN',
		"\n"=> 'NEW-LINE',
		"\t"=> 'TAB',
		' ' => 'SPACE'
	);
	
	public function __construct ($options)
	{
		if (isset($options[0]))
			$this->case = strtoupper(substr($options[0], 0, 1));
		
		parent::__construct($options);
	}
	
	public function encode ($text)
	{
		$x = parent::encode($text);
		
		if ($this->case=='L')
			return strtolower($x);
		elseif ($this->case=='M')
			return ucwords(strtolower($x));
		else
			return strtoupper($x);
	}

	public function decode ($code)
	{
		return parent::decode(strtoupper($code));
	}
	
	public function explain ()
	{
		return wordwrap("This is the alpha-bravo-charlie alphabet used by radio operators to spell things out over the airwaves and differentiate between similarly sounding letters like B and P. It's trivial as an encrytion scheme, but adds a bit of flair as a final step, or as an early step can make the message look a lot longer than it really is.\n\n"
				."This can take one parameter to indicate the case of the output. \"L\" for lower case, \"M\" for mixed case and \"U\" for upper case.\n\n"
				."Examples:\n\n"
				."'otp; vig \"alphabettispaghetti\"; base64; phonetic \"M\"'\n"
				."This should provide a very good encrytion via the combination of a one time pad and Vigenere encoding, make the result ASCII-safe, and print out the result in a cute radio-friendly alphabet."
				);
	}
}

/*
 * Based on http://fijiwebdesign.com/content/view/101/77/
 */
class TrivialEncoder_Vigenere extends TrivialEncoder
{
	const NAME           = 'Vigenere Cypher';
	const CODE           = 'vig';
	const LINK           = 'http://en.wikipedia.org/wiki/Vigenere_cipher';
	const ASCII_SAFENESS = ASCII_SAFE_PASSTHRU;
	const STRENGTH       = STRENGTH_MEDIUM;

	public $table = 'abcdefghijklmnopqrstuvwxyz';
	public $key   = 'kryptos';
	public $mod   = 26;

	public function __construct ($options)
	{
		if (isset($options[0]))
			$this->key = preg_replace('/[^a-z]/', '', strtolower($options[0]));
	}
	
	private function getKey()
	{
		return $this->key;
	}
	
	public function encode($str)
	{
		$enc_str = '';
		$len = strlen($str);
		for($i = 0; $i < $len; $i++)
		{
			if (preg_match('/[a-z]/', $str[$i]))
			{
				$shift = $this->P($this->charAt($str, $i)) + $this->P($this->charAt($this->key, $i));
				$pos = $this->modulo($shift, $this->mod);
				$enc_str .= strtolower($this->A($pos));
			}
			elseif (preg_match('/[A-Z]/', $str[$i]))
			{
				$shift = $this->P(strtolower($str[$i])) + $this->P($this->charAt($this->key, $i));
				$pos = $this->modulo($shift, $this->mod);
				$enc_str .= strtoupper($this->A($pos));
			}
			else
			{
				$enc_str .= $str[$i];
				continue;
			}
		}
		return $enc_str;
	}
	
	public function decode ($str)
	{
		$txt_str = '';
		$len = strlen($str);
		for($i = 0; $i < $len; $i++)
		{
			if (preg_match('/[a-z]/', $str[$i]))
			{
				$shift = $this->P($this->charAt($str, $i)) - $this->P($this->charAt($this->key, $i));
				$pos = $this->modulo($shift, $this->mod);
				$txt_str .= strtolower($this->A($pos));
			}
			elseif (preg_match('/[A-Z]/', $str[$i]))
			{
				$shift = $this->P(strtolower($str[$i])) - $this->P($this->charAt($this->key, $i));
				$pos = $this->modulo($shift, $this->mod);
				$txt_str .= strtoupper($this->A($pos));
			}
			else
			{
				$txt_str .= $str[$i];
				continue;
			}
		}
		return $txt_str;
	}

	private function P($a)
	{
		return strpos($this->table, $a); 
	}
	
	private function A($p)
	{
		$p = $p >= 0 ? $p : strlen($this->table) + $p; 
		return $this->table{$p};
	}
	
	private function charAt($str, $i)
	{
		$i = $i % strlen($str);
		return $str[$i];
	}
	
	private function modulo($n, $mod)
	{
		return $n % $mod;
	}
	
	public function explain ()
	{
		return wordwrap("The Vigenere cypher, first described by Giovan Batista Belaso in 1553, is a simple but effective variation on Caesar/alphabetic rotation cyphers. It essentially uses a number of different rotation cyphers, cycling though them for each letter of the message.\n\n"
				."It was considered virtually unbreakable until Friedrich Kasiski published an attack in 1863. Charles Babbage had previously broken the cypher in 1854, but hadn't published his technique. Cryptanalysis is based on letter frequency analysis and looking for patterns common in whichever language was encrypted, so it is possible to improve the strength of the cypher by performing an operation to obscure that first: for example by base64 encoding.\n\n"
				."The Vignere cypher requires a password to operate on. This should consist of only alphabetic characters, and is treated case-insensitively. The default password is 'kryptos', but you should choose something else.\n\n"
				."Examples:\n\n"
				."'base64; vig'\n"
				."Applies Base64 and Vigenere to the message, using the default password \"kryptos\". This should stand up fairly well to cryptanalysis, but only if the password isn't guessed.\n\n"
				."'otp; vig \"alphabettispaghetti\"; base64'\n"
				."This will be a lot more effective. The password is hard to guess; and the one time pad makes the input data seem pseudo-random, helping the output stand up against frequency and pattern analysis. The Base64 encoding makes the output ASCII-safe."
				);
	}
}

abstract class TrivialEncoder_MCrypt extends TrivialEncoder
{
	const NAME           = 'Abstract MCrypt Encoding';
	const CODE           = '!m';
	const LINK           = 'http://mcrypt.sourceforge.net/';
	const REQUIRE_MCRYPT = TRUE;
	const PARAMS         = PARAMS_KEYWORD;
	const STRENGTH       = STRENGTH_STRONG;
	
	protected $key    = 'kryptos';
	protected $algo   = NULL;
	
	public function __construct ($options)
	{
		if (isset($options[0]))
			$this->key = trim("{$options[0]}");
	}

	public function encode ($text)
	{
		$text = strlen($text).':'.$text;
		$td = mcrypt_module_open($this->algo, '', MCRYPT_MODE_CBC, '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $this->key, $iv);
		$encrypted_data = mcrypt_generic($td, $text);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $iv.$encrypted_data;
	}
	
	public function decode ($code)
	{
		$td = mcrypt_module_open($this->algo, '', MCRYPT_MODE_CBC, '');
		$ivsize = mcrypt_enc_get_iv_size($td);
		$iv = substr($code, 0, $ivsize);
		$encrypted_data = substr($code, $ivsize);
		mcrypt_generic_init($td, $this->key, $iv);
		$text = mdecrypt_generic($td, $encrypted_data);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		
		$pos = strpos($text, ':');
		$n   = (int)substr($text, 0, $pos);
		return substr($text, $pos+1, $n);
	}
	
	public function explain ()
	{
		return wordwrap("The MCrypt library provides a large variety of different, fairly strong encryption algorithms. We use the ECB encryption mode. Each cypher takes a password as a parameter.");
	}
	
	public function calc_strength ($base)
	{
		if ($this->key=='kryptos')
			return $base * STRENGTH_TRIVIAL;
		elseif (strlen($this->key) < 6)
			return $base * STRENGTH_WEAK;
		elseif (strlen($this->key) < 8)
			return $base * STRENGTH_MEDIUM;
		else
			return $base * STRENGTH_STRONG;
	}
}

class TrivialEncoder_3DES extends TrivialEncoder_MCrypt
{
	const NAME      = 'Triple DES';
	const CODE      = 'tripledes';
	protected $algo = MCRYPT_3DES;
}

class TrivialEncoder_DES extends TrivialEncoder_MCrypt
{
	const NAME      = 'DES';
	const CODE      = 'des';
	protected $algo = MCRYPT_DES;
}

class TrivialEncoder_Blowfish extends TrivialEncoder_MCrypt
{
	const NAME      = 'Blowfish';
	const CODE      = 'blowfish';
	protected $algo = MCRYPT_BLOWFISH;
}

class TrivialEncoder_Twofish extends TrivialEncoder_MCrypt
{
	const NAME      = 'Twofish';
	const CODE      = 'twofish';
	protected $algo = MCRYPT_TWOFISH;
}

class TrivialEncoder_Rijndael_128 extends TrivialEncoder_MCrypt
{
	const NAME      = 'Rijndael 128';
	const CODE      = 'rijndael128';
	protected $algo = MCRYPT_RIJNDAEL_128;
}

class TrivialEncoder_Rijndael_256 extends TrivialEncoder_MCrypt
{
	const NAME      = 'Rijndael 256';
	const CODE      = 'rijndael256';
	protected $algo = MCRYPT_RIJNDAEL_256;
}

class TrivialEncoder_Rijndael_512 extends TrivialEncoder_MCrypt
{
	const NAME      = 'Rijndael 512';
	const CODE      = 'rijndael512';
	protected $algo = MCRYPT_RIJNDAEL_512;
}

class TrivialEncoder_CAST_128 extends TrivialEncoder_MCrypt
{
	const NAME      = 'CAST 128';
	const CODE      = 'cast128';
	protected $algo = MCRYPT_CAST_128;
}

class TrivialEncoder_CAST_256 extends TrivialEncoder_MCrypt
{
	const NAME      = 'CAST 256';
	const CODE      = 'cast256';
	protected $algo = MCRYPT_CAST_256;
}

class TrivialEncoder_GOST extends TrivialEncoder_MCrypt
{
	const NAME      = 'GOST';
	const CODE      = 'gost';
	protected $algo = MCRYPT_GOST;
}

class TrivialEncoder_Loki97 extends TrivialEncoder_MCrypt
{
	const NAME      = 'Loki 97';
	const CODE      = 'loki97';
	protected $algo = MCRYPT_LOKI97;
}

class TrivialEncoder_RC2 extends TrivialEncoder_MCrypt
{
	const NAME      = 'RC2';
	const CODE      = 'rc2';
	protected $algo = MCRYPT_RC2;
}

class TrivialEncoder_SAFER_Sk64 extends TrivialEncoder_MCrypt
{
	const NAME      = 'SAFER-SK64';
	const CODE      = 'safer64';
	protected $algo = MCRYPT_SAFER64;
}

class TrivialEncoder_SAFER_Sk128 extends TrivialEncoder_MCrypt
{
	const NAME      = 'SAFER-SK128';
	const CODE      = 'safer128';
	protected $algo = MCRYPT_SAFER128;
}

class TrivialEncoder_SAFER_Plus extends TrivialEncoder_MCrypt
{
	const NAME      = 'SAFER+';
	const CODE      = 'saferplus';
	protected $algo = MCRYPT_SAFERPLUS;
}

class TrivialEncoder_Serpent extends TrivialEncoder_MCrypt
{
	const NAME      = 'Serpent';
	const CODE      = 'serpent';
	protected $algo = MCRYPT_SERPENT;
}

class TrivialEncoder_3Way extends TrivialEncoder_MCrypt
{
	const NAME      = '3-Way';
	const CODE      = 'threeway';
	protected $algo = MCRYPT_THREEWAY;
}

class TrivialEncoder_XTEA extends TrivialEncoder_MCrypt
{
	const NAME      = 'eXtended TEA';
	const CODE      = 'xtea';
	protected $algo = MCRYPT_XTEA;
}

/* TE_Machine */
require_once 'TE_Machine.class.php';

if (!isset($trivialencoder_auto) || $trivialencoder_auto===TRUE)
{
	if (php_sapi_name()=='cli')
		TE_Machine::ProvideCommandLineInterface($argv);
	elseif ($_SERVER['REQUEST_METHOD']=='GET')
		TE_Machine::ProvideHTTPInterface($_GET);
	elseif ($_SERVER['REQUEST_METHOD']=='POST')
		TE_Machine::ProvideHTTPInterface($_POST);
}

?>
