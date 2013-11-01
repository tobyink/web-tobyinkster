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
 * See: http://tobyinkster.co.uk/blog/2007/08/01/php-encryption-class/
 *
 * @author Toby Inkster
 * @copyright Copyright (C) 2007 Toby Inkster
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public Licence
 */

class TE_Init_Exception extends Exception { }
class TE_Crypto_Exception extends Exception { }
class TE_Manager_Exception extends Exception { }

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

	public function list_encoders ()
	{
		$col1 = 15;
		$col2 = 30;
		$col3 = 30;

		$out    = str_repeat('=', $col1+$col2+$col3+4) . "\n";
		$out   .= sprintf("% -{$col1}s| % -{$col2}s| %s\n",
				'Code',
				'Class',
				'Description'
				);
		$out   .= str_repeat('-', $col1+0) . '+'
			. str_repeat('-', $col2+1) . '+'
			. str_repeat('-', $col3+1) . "\n";
		foreach ($this->encoders as $k=>$v)
			$out .= sprintf("% -{$col1}s| % -{$col2}s| %s\n",
					$k,
					$v,
					eval("return $v::NAME;")
					);
		$out   .= str_repeat('=', $col1+$col2+$col3+4) . "\n";

		return $out;
	}

	public static function provide_cli ($A)
	{
		$manager  = new TrivialEncoderManager;

		if (preg_match('/^\-*d(ecode)?/i', $A[1]))
		{
			$mode     = 'decode';
			$encoding = $A[2];
			$f_in     = $A[3];
			$f_out    = $A[4];
		}
		else
		{
			$mode     = 'encode';
			$encoding = $A[1];
			$f_in     = $A[2];
			$f_out    = $A[3];
		}

		if (preg_match('/^\-*h(elp)?/i', $encoding))
		{
			print "usage: TrivialEncoder.class.php method infile outfile\n";
			print "       TrivialEncoder.class.php -d method infile outfile\n\n";
			print "Multiple methods may be chained with semicolons.\n\n";
			print $manager->list_encoders();
			exit;
		}

		if (!strlen($f_in) || $f_in=='-')
			$f_in = 'php://stdin';
		
		if (!strlen($f_out) || $f_out=='-')
			$f_out = 'php://stdout';
		elseif (file_exists($f_out))
			die("Cowardly refusing to overwrite '$f_out'.\n");

		$in = file_get_contents($f_in);
		if ($mode=='encode')
			$out = $manager->encode($encoding, $in);
		else
			$out = $manager->decode($encoding, $in);
		file_put_contents($f_out, $out);
	}

	public static function provide_httpi ($A)
	{
		$action     = strtolower($A['action']);
		$method     = $A['method'];
		$plaintext  = $A['plaintext'];
		$cyphertext = $A['cyphertext'];

		$manager    = new TrivialEncoderManager;
		if ($action=='encode')
			$cyphertext = $manager->encode($method, $plaintext);
		elseif ($action=='decode')
			$plaintext = $manager->decode($method, $cyphertext);
		elseif ($action=='help')
		{
			print "<p>List of encryption methods:</p>";
			print "<pre>".$manager->list_encoders()."</pre>\n";
			print "<p>(Multiple methods may be chained using semicolons.)</p>\n";
		}
		else
			$method='rot13';

		print "<form method=\"post\" action=\"\">\n";

		print "<table class=\"trivialencoder\">\n";
		print "<tr>\n";
		print "<th>\n";
		print "<label for=\"plaintext\">Plain Text</label>:\n";
		print "</th>\n";
		print "<td>\n";
		print "<textarea rows=\"8\" cols=\"40\" name=\"plaintext\" id=\"plaintext\">";
		print htmlspecialchars($plaintext);
		print "</textarea>\n";
		print "</td>\n";
		print "</tr>\n";
		print "<tr>\n";
		print "<th>\n";
		print "<label for=\"method\">Encryption Method</label>:\n";
		print "</th>\n";
		print "<td>\n";
		print "<input type=\"name\" id=\"method\" name=\"method\" value=\"".htmlspecialchars($method)."\" />\n";
		print "</td>\n";
		print "</tr>\n";
		print "<tr>\n";
		print "<th>\n";
		print "<label for=\"cyphertext\">Cypher Text</label>:\n";
		print "</th>\n";
		print "<td>\n";
		print "<textarea rows=\"8\" cols=\"40\" name=\"cyphertext\" id=\"cyphertext\">";
		print htmlspecialchars($cyphertext);
		print "</textarea>\n";
		print "</td>\n";
		print "</tr>\n";
		print "<tr>\n";
		print "<th>Action:</th>\n";
		print "<td>\n";
		print "<input type=\"submit\" name=\"action\" value=\"Encode\" />\n";
		print "<input type=\"submit\" name=\"action\" value=\"Decode\" />\n";
		print "<input type=\"submit\" name=\"action\" value=\"Help\" />\n";
		print "</td>\n";
		print "</tr>\n";
		print "</table>\n";
		print "</form>\n";
	}
}

abstract class TrivialEncoder
{
	const NAME = 'Abstract';
	const CODE = '!';
	
	abstract public function __construct ($options);
	abstract public function encode ($text);
	abstract public function decode ($code);
}

class TrivialEncoder_Rot extends TrivialEncoder
{
	const NAME = 'Alphabetic Rotation';
	const CODE = 'rot';
	
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
}

class TrivialEncoder_Rot13 extends TrivialEncoder_Rot
{
	const NAME = 'ROT-13';
	const CODE = 'rot13';
	
	protected $n = 13;
	public function __construct ($options) { /* no options */ }
}

class TrivialEncoder_Caesar extends TrivialEncoder_Rot
{
	const NAME = 'Caesar\'s Cypher';
	const CODE = 'caesar';
	
	protected $n = 3;
	public function __construct ($options) { /* no options */ }
}

class TrivialEncoder_Rot47 extends TrivialEncoder
{
	const NAME = 'ROT-47';
	const CODE = 'rot47';
	
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
}

class TrivialEncoder_XOR extends TrivialEncoder
{
	const NAME = 'Simple XOR Encryption';
	const CODE = 'xor';

	protected $n = 0;
	
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
}

class TrivialEncoder_Memfrob extends TrivialEncoder_XOR
{
	const NAME = 'Memfrob Frobnication';
	const CODE = 'memfrob';

	protected $n = 42;

	public function __construct ($options) { /* no options */ }
}

class TrivialEncoder_Keyword extends TrivialEncoder
{
	const NAME = 'Keyword Cypher';
	const CODE = 'key';
	
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

class TrivialEncoder_Hex extends TrivialEncoder
{
	const NAME = 'Hexadecimal';
	const CODE = 'hex';
	
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
}

class TrivialEncoder_Decimal extends TrivialEncoder_Hex
{
	const NAME = 'Decimal';
	const CODE = 'dec';
	
	protected $fstr      = '%03d';
	protected $dstr      = '%d';
	protected $chunksize = 3;
}

class TrivialEncoder_Octal extends TrivialEncoder_Hex
{
	const NAME = 'Octal';
	const CODE = 'oct';
	
	protected $fstr      = '%03o';
	protected $dstr      = '%o';
	protected $chunksize = 3;
}

class TrivialEncoder_Binary extends TrivialEncoder_Hex
{
	const NAME = 'Binary';
	const CODE = 'bin';
	
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
}

class TrivialEncoder_UU extends TrivialEncoder
{
	const NAME = 'UUEncoding';
	const CODE = 'uue';
	
	public function __construct ($options) { /* no options */ }
	public function encode ($text) { return convert_uuencode($text); }
	public function decode ($code) { return convert_uudecode($code); }
}

class TrivialEncoder_Base64 extends TrivialEncoder
{
	const NAME = 'Base 64';
	const CODE = 'base64';
	
	public function __construct ($options) { /* no options */ }
	public function encode ($text) { return base64_encode($text); }
	public function decode ($code) { return base64_decode($code); }
}

class TrivialEncoder_URL extends TrivialEncoder
{
	const NAME = 'URL Encoding';
	const CODE = 'url';
	
	public function __construct ($options) { /* no options */ }
	public function encode ($text) { return urlencode($text); }
	public function decode ($code) { return urldecode($code); }
}

class TrivialEncoder_HTMLEnt extends TrivialEncoder
{
	const NAME = 'HTML Entities';
	const CODE = 'html';
	
	public function __construct ($options) { /* no options */ }
	public function encode ($text) { return htmlspecialchars($text); }
	public function decode ($code) { return htmlspecialchars_decode($code); }
}

class TrivialEncoder_Morse extends TrivialEncoder
{
	const NAME = 'Morse Code';
	const CODE = 'morse';

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
}

class TrivialEncoder_Phonetic extends TrivialEncoder_Morse
{
	const NAME = 'Phonetic Alphabet';
	const CODE = 'phonetic';

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
}

abstract class TrivialEncoder_MCrypt extends TrivialEncoder
{
	const NAME = 'Abstract MCrypt Encoding';
	const CODE = '!m';
	
	protected $key    = 'secret';
	protected $algo   = NULL;
	
	public function __construct ($options)
	{
		if (isset($options[0]))
			$this->key = "{$options[0]}";
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
}

class TrivialEncoder_3DES extends TrivialEncoder_MCrypt
{
	const NAME      = 'Triple DES';
	const CODE      = '3des';
	protected $key  = 'secret';
	protected $algo = MCRYPT_3DES;
}

class TrivialEncoder_DES extends TrivialEncoder_MCrypt
{
	const NAME      = 'DES';
	const CODE      = 'des';
	protected $key  = 'secret';
	protected $algo = MCRYPT_DES;
}

class TrivialEncoder_Blowfish extends TrivialEncoder_MCrypt
{
	const NAME      = 'Blowfish';
	const CODE      = 'blowfish';
	protected $key  = 'secret';
	protected $algo = MCRYPT_BLOWFISH;
}

class TrivialEncoder_Rijndael_128 extends TrivialEncoder_MCrypt
{
	const NAME      = 'Rijndael 128';
	const CODE      = 'rijndael128';
	protected $key  = 'secret';
	protected $algo = MCRYPT_RIJNDAEL_128;
}

class TrivialEncoder_Rijndael_256 extends TrivialEncoder_MCrypt
{
	const NAME      = 'Rijndael 256';
	const CODE      = 'rijndael256';
	protected $key  = 'secret';
	protected $algo = MCRYPT_RIJNDAEL_256;
}

class TrivialEncoder_Rijndael_512 extends TrivialEncoder_MCrypt
{
	const NAME      = 'Rijndael 512';
	const CODE      = 'rijndael512';
	protected $key  = 'secret';
	protected $algo = MCRYPT_RIJNDAEL_512;
}

class TrivialEncoder_CAST_128 extends TrivialEncoder_MCrypt
{
	const NAME      = 'CAST 128';
	const CODE      = 'cast128';
	protected $key  = 'secret';
	protected $algo = MCRYPT_CAST_128;
}

class TrivialEncoder_CAST_256 extends TrivialEncoder_MCrypt
{
	const NAME      = 'CAST 256';
	const CODE      = 'cast256';
	protected $key  = 'secret';
	protected $algo = MCRYPT_CAST_256;
}

if (!isset($trivialencoder_auto) || $trivialencoder_auto===TRUE)
{
	if (php_sapi_name()=='cli')
		TrivialEncoderManager::provide_cli($argv);
	elseif ($_SERVER['REQUEST_METHOD']=='GET')
		TrivialEncoderManager::provide_httpi($_GET);
	elseif ($_SERVER['REQUEST_METHOD']=='POST')
		TrivialEncoderManager::provide_httpi($_POST);
}

?>
