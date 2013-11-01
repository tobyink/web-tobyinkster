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

define('TOK_IDENTIFIER', 0);
define('TOK_INTLITERAL', 12);
define('TOK_STRINGLITERAL', 13);
define('TOK_OPENPAREN', 22);
define('TOK_CLOSEPAREN', 23);
define('TOK_COMMA', 90);
define('TOK_SEMICOLON', 99);

function array_merge_recursive_strict_2 ($a, $b)
{
	$ret = $a;
	if (is_array($b))
	{
		foreach ($b as $k=>$v)
		{
			if ( is_array($ret[$k]) )
				$ret[$k] = array_merge_recursive_strict_2($ret[$k], $v);
			else
				$ret[$k] = $v;
		}
	}
		
	return $ret;
}

class TE_Tokeniser 
{
	public $programme = '';
	public $posx = 1;
	public $posy = 1;
	
	public function __construct ($str)
	{
		if (!preg_match('/\;\s*$/', $str))
			$str .= ';';
	
		$this->programme = $str;
	}
	
	private function matchHead ($pattern)
	{
		$len = strlen($pattern);
		if (strtolower(substr($this->programme, 0, $len)) == strtolower($pattern))
			return TRUE;
		return FALSE;
	}
	
	private function gobble ($pattern)
	{
		if (!$this->matchHead($pattern))
			return FALSE;
		
		$len = strlen($pattern);
		$retval = substr($this->programme, 0, $len);
		$this->programme = substr($this->programme, $len);
		$this->posx += $len;
		
		return $retval;
	}
	
	private function gobble_space ()
	{
		$firstchar = substr($this->programme, 0, 1);
		while ($firstchar==' ' || $firstchar=="\t" || $firstchar=="\r" || $firstchar=="\n")
		{
			if ($firstchar==' ' || $firstchar=="\t")
				$this->posx += 1;
			else
			{
				$this->posy += 1;
				$this->posx = 1;
			}
				
			$this->programme = substr($this->programme, 1);
			$firstchar = substr($this->programme, 0, 1);
		}
	}

	private function gobble_number ()
	{
		$firstchar = substr($this->programme, 0, 1);
		$expr = '/^[0-9\.-]$/';
		$number = '';
		while (preg_match($expr,$firstchar))
		{
			$number .= $firstchar;
			if ($firstchar=='.')
				$expr = '/^[0-9]$/';
			$this->programme = substr($this->programme, 1);
			$firstchar = substr($this->programme, 0, 1);
		}
		if ($expr=='/^[0-9]$/')
			$n = (float)$number;
		else
			$n = (int)$number;
		
		return $n;
	}
	
	private function gobble_identifier ()
	{
		$i = $this->gobble('$');
		$firstchar = substr($this->programme, 0, 1);
		$expr = '/[A-Za-z0-9\_\$]/';
		while (preg_match($expr,$firstchar))
		{
			$i .= $this->gobble($firstchar);;
			$firstchar = substr($this->programme, 0, 1);
		}
		return $i;
	}
	
	private function gobble_bare_identifier ()
	{
		$i = '';
		$firstchar = substr($this->programme, 0, 1);
		$expr = '/[A-Za-z0-9\_\-\$]/';
		while (preg_match($expr,$firstchar))
		{
			$i .= $this->gobble($firstchar);;
			$firstchar = substr($this->programme, 0, 1);
		}
		return $i;
	}
	
	private function gobble_literal ($startchar, $endchar, $escapechar="\\")
	{
		if (!$this->matchHead($startchar))
			return FALSE;
		$this->gobble($startchar);
		
		$literal = '';
		
		while (!$this->matchHead($endchar))
		{
			$char = substr($this->programme, 0, 1);
			$this->programme = substr($this->programme, 1);
			
			if ($char==$escapechar)
			{
				$char = substr($this->programme, 0, 1);
				$this->programme = substr($this->programme, 1);
				$this->posx += 1;
			}
			
			if ($char=="\n" || $char=="\r")
			{
				$this->posy += 1;
				$this->posx = 1;
			}
			else
				$this->posx += 1;
				
			$literal .= $char;
		}
		$this->gobble($endchar);
		
		return $literal;
	}
	
	private function scanToken ()
	{
		$char = $this->posx;
		$line = $this->posy;
	
		if ($this->matchHead(';'))
			return array(TOK_SEMICOLON, $this->gobble(';'), $line, $char);
		elseif ($this->matchHead('/*'))
		{
			$comment = $this->gobble_literal('/*', '*/');
			return FALSE;
		}
		elseif ($this->matchHead('"'))
			return array(TOK_STRINGLITERAL, $this->gobble_literal('"', '"'), $line, $char);
		elseif ($this->matchHead("'"))
			return array(TOK_STRINGLITERAL, $this->gobble_literal("'", "'"), $line, $char);
		elseif (  preg_match('/[0-9-]/', substr($this->programme, 0, 1))  )
			return array(TOK_INTLITERAL, $this->gobble_number(), $line, $char);
		elseif ($this->matchHead(','))
			return array(TOK_COMMA, $this->gobble(','), $line, $char);
		elseif ($this->matchHead('('))
			return array(TOK_OPENPAREN, $this->gobble('('), $line, $char);
		elseif ($this->matchHead(')'))
			return array(TOK_CLOSEPAREN, $this->gobble(')'), $line, $char);
		elseif (preg_match('/^[A-Za-z]/', $this->programme))
			return array(TOK_IDENTIFIER, $this->gobble_bare_identifier(), $line, $char);
		elseif ($this->programme == '')
			return FALSE;
		else
			return FALSE;
	}
	
	public function tokenise ()
	{
		$tokens = array();
	
		while ($this->programme != '')
		{
			$this->gobble_space();
			$t = $this->scanToken();
			if ($t!==FALSE)
				$tokens[] = $t;
		}
		
		return $tokens;
	}
}

class TE_Ast
{
	public $pos_line;
	public $pos_char;
	public $spelling;
	
	public function __construct ($token=FALSE)
	{
		if (isset($token[1]))
			$this->spelling = $token[1];
		if (isset($token[2]))
			$this->pos_line = $token[2];
		if (isset($token[3]))
			$this->pos_char = $token[3];
	}
	
	public function evaluate ($machine)
	{
		throw new TE_Manager_Exception("Unimplemented token at line {$this->pos_line}, char {$this->pos_char}.\n");
	}
}

class TE_Ast_Error
{
	public function evaluate ($machine)
	{
		throw new TE_Manager_Exception("Unspecified error at line {$this->pos_line}, char {$this->pos_char}."); 
	}
}

class TE_Ast_Number extends TE_Ast
{
	public function evaluate ($machine)
	{
		return (float)$this->spelling;
	}
}

class TE_Ast_String extends TE_Ast
{
	public function evaluate ($machine)
	{
		return $this->spelling;
	}
}

class TE_Ast_Script extends TE_Ast
{
	public $statements = array();
	
	public function evaluate ($machine)
	{
		if ($machine->get_direction()==TE_Machine::DIRECTION_BACKWARDS)
			$STAT = array_reverse($this->statements);
		else
			$STAT = $this->statements;
			
		$r = 0;
		if (isset($STAT[0]))
		{
			foreach ($STAT as $s)
			{
				$x = $s->evaluate($machine);
				if ($x!==NULL)
					$r = $x;
			}
			
		}

		return $r;
	}
}

class TE_Ast_FunctionCall extends TE_Ast
{
	public $function_name;
	public $params;
	public $param_count;
	
	public function evaluate ($machine)
	{
		$evaluated_params = array();
		if ($this->param_count)
			foreach ($this->params as $x)
			{
				if ($x instanceof TE_Ast_Script)
					$evaluated_params[] = $x;
				elseif (!is_null($x))
					$evaluated_params[] = $x->evaluate($this);
			}
	
		$machine->action_buffer($this->function_name, $evaluated_params);
		return TRUE;
	}
}

class TE_Ast_Statement extends TE_Ast
{
	public $statement;
	
	public function evaluate ($machine)
	{
		return $this->statement->evaluate($machine);
	}
}

class TE_Parser
{
	private $tokens = array();
	private $stack = 0;
	
	public function __construct ($t)
	{
		$this->tokens = $t;
	}
	
	private function acceptIt ()
	{
		#print 'Accept: '.$this->tokens[0][1]."\n";
		return array_shift($this->tokens);
	}
	
	private function nextIs ($t)
	{
		if ($this->tokens[0][0] == $t)
			return TRUE;
		return FALSE;
	}
	
	public function parseScript ()
	{
		$retval = new TE_Ast_Script($this->tokens[0]);
		$retval->statements[] = $this->parseCommand();		

		while ($this->nextIs(TOK_SEMICOLON))
		{
			while ($this->nextIs(TOK_SEMICOLON))
				$this->acceptIt();
			
			if ($this->stack && $this->nextIs(TOK_CLOSEPAREN))
				break;
				
			if (isset($this->tokens[0]))
				$retval->statements[] = $this->parseCommand();
		}
		return $retval;
	}
	
	private function parseCommand ()
	{
		$retval = new TE_Ast_Statement($this->tokens[0]);
		$retval->statement = $this->parseStatement();
		return $retval;
	}
	
	private function parseStatement ()
	{
		if ($this->nextIs(TOK_IDENTIFIER))
		{
			$t = $this->acceptIt();
			$retval = new TE_Ast_FunctionCall($t);
			$retval->function_name = $t[1];
			while (!$this->nextIs(TOK_SEMICOLON))
			{
				$retval->params[] = $this->parseExpression();
				if ($this->nextIs(TOK_COMMA))
					$this->acceptIt();
				elseif ($this->nextIs(TOK_CLOSEPAREN))
					break;
			}
			
			$retval->param_count = count($retval->params);
			return $retval;
		}
		else
			return new TE_Ast_Error($this->tokens[0]);
	}
	
	private function parseExpression ($greedy=1)
	{
		if ($this->nextIs(TOK_INTLITERAL))
			$exp1 = new TE_Ast_Number($this->acceptIt());
		
		elseif ($this->nextIs(TOK_STRINGLITERAL))
			$exp1 = new TE_Ast_String($this->acceptIt());
		
		elseif ($this->nextIs(TOK_OPENPAREN))
		{
			$this->acceptIt();
			$this->stack++;
			$exp1 = $this->parseScript();
			if ($this->nextIs(TOK_CLOSEPAREN))
			{
				$this->acceptIt();
				$this->stack--;
			}
			else
				return new TE_Ast_Error($this->tokens[0]);
		}
		
		return $exp1;
	}
}

abstract class TE_Machine
{
	const DIRECTION_BACKWARDS = -1;
	const DIRECTION_FORWARDS  =  1;

	private $mode;
	private $buffer;
	private $encoders;
	
	public function __construct()
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
	
	public function get_direction ()
	{
		return self::DIRECTION_FORWARDS;
	}
	
	public function set_buffer ($x)
	{
		$this->buffer = $x;
	}
	
	public function get_buffer ()
	{
		return $this->buffer;
	}
	
	protected function get_encoder_class ($code)
	{
		$class = $this->encoders[$code];
		if (!strlen($class))
			throw new TE_Manager_Exception("Encoder '{$code}' not found.");
		return $class;
	}
	
	abstract public function action_buffer ($encoding_method, $params);	
	
	public function ascii_list_encoders ()
	{
		$col1 = 12;
		$col2 = 30;
		$col3 = 24;
		$col4 = 6;

		$out    = str_repeat('=', $col1+$col2+$col3+$col4+6) . "\n";
		$out   .= sprintf("% -{$col1}s| % -{$col2}s| % -{$col3}s| %s\n",
				'Code',
				'Class',
				'Description',
				'Flags'
				);
		$out   .= str_repeat('-', $col1+0) . '+'
			. str_repeat('-', $col2+1) . '+'
			. str_repeat('-', $col3+1) . '+'
			. str_repeat('-', $col4+1) . "\n";
		foreach ($this->encoders as $k=>$v)
		{
			$flags = '';
			$flags .= (eval("return $v::ASCII_SAFENESS;")==ASCII_SAFE_ALWAYS)?'A':'';
			$flags .= (eval("return $v::ASCII_SAFENESS;")==ASCII_SAFE_PASSTHRU)?'a':'';
			$flags .= (eval("return $v::REQUIRE_MCRYPT;"))?'M':'';
			$flags .= (eval("return $v::PARAMS;")==PARAMS_NUMBER)?'#':'';
			$flags .= (eval("return $v::PARAMS;")==PARAMS_KEYWORD)?'$':'';
			$flags .= (eval("return $v::PARAMS;")==PARAMS_MISC)?'*':'';
			$out .= sprintf("% -{$col1}s| % -{$col2}s| % -{$col3}s| %s\n",
					$k,
					$v,
					eval("return $v::NAME;"),
					$flags
					);
		}
		$out   .= str_repeat('=', $col1+$col2+$col3+$col4+6) . "\n";

		return $out . "\n"
			  . "A = Returns ASCII-safe output.\n"
			  . "a = Returns ASCII-safe output if input was ASCII-safe.\n"
			  . "M = Requires MCrypt support to be built into PHP.\n"
			  . "# = Optional numeric parameter.\n"
			  . "$ = Optional string parameter (pass phrase).\n"
			  . "* = Miscellaneous parameter(s).\n"
			;
	}
	
	public static function ProvideCommandLineInterface ($A)
	{
		for ($i=0;$i<5;$i++)
			if (!isset($A[$i]))
				$A[$i] = NULL;

		if (preg_match('/^\-+?/i', $A[1]))
		{
			$mode     = strtolower(str_replace('-', '', $A[1]));
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
		
		if (preg_match('/^(h|help|usage)$/', $mode))
		{
			$mgr = new TE_Machine_Explainer;
			print "usage: php TrivialEncoder.class.php method infile outfile\n";
			print "       php TrivialEncoder.class.php --decode method infile outfile\n";
			print "       php TrivialEncoder.class.php --explain method\n";
			print "       php TrivialEncoder.class.php --strength method\n";
			print "       php TrivialEncoder.class.php --ascii method\n";
			print "       php TrivialEncoder.class.php --help\n\n";
			print "Multiple methods may be chained with semicolons.\n\n";
			print $mgr->ascii_list_encoders();
			exit;
		}
		
		elseif (preg_match('/^(x|explain)$/', $mode))
		{
			print TE_Machine_Explainer::Go($encoding);
		}
		
		elseif (preg_match('/^(s|strength)$/', $mode))
		{
			print 'Relative strength: '
				.TE_Machine_StrengthCalculator::Go($encoding)."\n";
		}

		elseif (preg_match('/^(a|ascii)$/', $mode))
		{
			$safeness = TE_Machine_AsciiSafenessCalculator::Go($encoding);
			$inputs   = array('ASCII input:  ', 'Binary input: ');
			for ($i=0; $i<2; $i++)
			{
				print $inputs[$i] . ($safeness[$i]?'ASCII output':'binary output') . "\n";
			}
		}

		else // mode is encode or decode
		{
			if (!strlen($f_in) || $f_in=='-')
				$f_in = 'php://stdin';
			if (!strlen($f_out) || $f_out=='-')
				$f_out = 'php://stdout';
			elseif (file_exists($f_out))
				die("Cowardly refusing to overwrite '$f_out'.\n");

			$in = file_get_contents($f_in);
			if (preg_match('/^e(ncode)?/', $mode))
				$out = TE_Machine_Encoder::Go($encoding, $in);
			else
				$out = TE_Machine_Decoder::Go($encoding, $in);
			file_put_contents($f_out, $out);
		}
	}

	public static function ProvideHTTPInterface ($A)
	{
		$action     = strtolower($A['action']);
		$method     = $A['method'];
		$plaintext  = $A['plaintext'];
		$cyphertext = $A['cyphertext'];

		if ($action=='encode')
			$cyphertext = TE_Machine_Encoder::Go($method, $plaintext);
		elseif ($action=='decode')
			$plaintext = TE_Machine_Decoder::Go($method, $cyphertext);
		elseif ($action=='help')
		{
			$manager = new TE_Machine_Explainer;
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

	protected static function Go ($method, $text, $machine_class=NULL)
	{
		if (is_null($machine_class))
			throw new TE_Manager_Exception('Machine class needs to be given.');
		
		/* Tokenise and parse programme */
		$tokeniser = new TE_Tokeniser($method);
		$parser = new TE_Parser($tokeniser->tokenise());
		$abstract_syntax_tree = $parser->parseScript();

		/* Run the abstract syntax tree on a machine */
		$machine = new $machine_class;
		$machine->set_buffer($text);
		$abstract_syntax_tree->evaluate($machine);
		
		return $machine->get_buffer();
	}
}

class TE_Machine_Explainer extends TE_Machine
{
	public function action_buffer ($encoding_method, $params)
	{
		$class = $this->get_encoder_class($encoding_method);

		$ascii    = eval("return $class::ASCII_SAFENESS;");
		$mcrypt   = eval("return $class::REQUIRE_MCRYPT;");
		$params   = eval("return $class::PARAMS;");
		$strength = eval("return $class::STRENGTH;");
		$link     = eval("return $class::LINK;");
		$name     = eval("return $class::NAME;");
		$obj      = new $class($params);
		$desc     = $obj->explain();

		$x = sprintf("[%s] %s\n"
				."Strength:        %s.\n"
				."MCrypt Required? %s.\n"
				."ASCII Safe?      %s.\n"
				."<%s>\n\n"
				."%s\n\n"
				, strtolower(trim($encoding_method))
				, $name
				, $strength
				, ($mcrypt?'Yes':'No')
				, ($ascii==ASCII_SAFE_NEVER?'No':($ascii==ASCII_SAFE_ALWAYS?'Yes':'If input is'))
				, $link
				, $desc
				);

		$this->set_buffer($this->get_buffer() . $x);
	}

	public static function Go ($method, $text='')
	{
		return parent::Go($method, '', 'TE_Machine_Explainer');
	}
}

class TE_Machine_Encoder extends TE_Machine
{
	public function action_buffer ($encoding_method, $params)
	{
		$class = $this->get_encoder_class($encoding_method);
		$encoder = new $class($params);
		$this->set_buffer($encoder->encode($this->get_buffer()));
	}
	
	public static function Go ($method, $text)
	{
		return parent::Go($method, $text, 'TE_Machine_Encoder');
	}
}

class TE_Machine_Decoder extends TE_Machine
{
	public function get_direction ()
	{
		return self::DIRECTION_BACKWARDS;
	}

	public function action_buffer ($encoding_method, $params)
	{
		$class = $this->get_encoder_class($encoding_method);		
		$encoder = new $class($params);
		$this->set_buffer($encoder->decode($this->get_buffer()));
	}

	public static function Go ($method, $text)
	{
		return parent::Go($method, $text, 'TE_Machine_Decoder');
	}
}

class TE_Machine_StrengthCalculator extends TE_Machine
{
	public function action_buffer ($encoding_method, $params)
	{
		$class = $this->get_encoder_class($encoding_method);
		
		// Allow these to calculate strength themselves!
		if (in_array('calc_strength', get_class_methods($class)))
		{
			$encoder = new $class($params);
			$this->set_buffer($encoder->calc_strength($this->get_buffer()));
		}
		else
		{			
			$this->set_buffer(eval("return {$class}::STRENGTH;") * $this->get_buffer());
		}
	}

	public static function Go ($method, $text='')
	{
		return parent::Go($method, 1.0, 'TE_Machine_StrengthCalculator');
	}
}

class TE_Machine_AsciiSafenessCalculator extends TE_Machine
{
	public function action_buffer ($encoding_method, $params)
	{
		$class    = $this->get_encoder_class($encoding_method);
		$safeness = eval("return {$class}::ASCII_SAFENESS;");
		$current  = $this->get_buffer();
		
		for ($i=0; $i<2; $i++)
		{
			if ($safeness==ASCII_SAFE_ALWAYS)
				$current[$i] = TRUE;
			elseif ($safeness==ASCII_SAFE_NEVER)
				$current[$i] = FALSE;
		}
		
		$this->set_buffer($current);
	}

	public static function Go ($method, $text='')
	{
		return parent::Go($method, array(TRUE, FALSE), 'TE_Machine_AsciiSafenessCalculator');
	}
}

?>
