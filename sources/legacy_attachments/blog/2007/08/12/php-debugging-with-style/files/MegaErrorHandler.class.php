<?php

/**
 * The Mega Error Handling.
 *
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
 * @package demiblog
 * @subpackage Core
 * @author Toby Inkster <demiblog@tobyinkster.co.uk>
 * @copyright Copyright (C) 2007 Toby Inkster
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public Licence
 */

/* This is used in PHP<5.2 */
if (!function_exists('json_encode'))
	require_once 'Services/JSON.php';

/**
 * Damn Nice Error Handling
 *
 * This class is able to output errors in a variety of styles, such as
 * HTML, e-mail and syslog. However, it's raison d'etre is to be able
 * to output errors as specially-formatted HTML comments, which can be
 * collected and manipulated by a nifty piece of Javascript and displayed
 * as an interactive console.
 */
class MegaErrorHandler
{
	const DEBUG_SILENT =   1;
	const DEBUG_HTML   =   2;
	const DEBUG_HTMLX  =   4;
	const DEBUG_JSON   =  32;
	const DEBUG_MAIL   =  64;
	const DEBUG_SYSLOG = 256;

	private static $instance = NULL;
	private $current_mime_type = NULL;
	private $json_encoder = NULL;
	private $queue = array();
	private static $debug_mode = 1;
	
	private function __construct ()
	{
	}
	
	public function __clone ()
	{
		throw new Exception('MegaErrorHandler must not be cloned.');
	}
	
	public function __destruct ()
	{
		$this->handle_queue();
	}
	
	/**
	 * Get instance of MegaErrorHandler.
	 *
	 * MegaErrorHandler is a singleton, so should not be constructed
	 * directly. Call this method to get an instance.
	 */
	public function singleton ()
	{
		if (!isset(self::$instance))
			self::$instance = new MegaErrorHandler;
		return self::$instance;
	}
	
	/**
	 * Get/set the debugging mode of the handler.
	 *
	 * Use the defined class constants to set your preferred output mode. They
	 * may be combined additively. May be called without any parameters to
	 * avoid changing the mode at all.
	 *
	 * @param int $mode Preferred mode. Optional.
	 * @return int Old mode.
	 */
	public static function set_debug_mode ($mode=NULL)
	{
		$retval = self::$debug_mode;
		if (isset($mode))
			self::$debug_mode = $mode;
		return $retval;
	}

	/**
	 * Call this function to start using this error handler.
	 * 
	 * May be passed a debugging mode so that you don't need to call
	 * set_debug_mode seperately.
	 *
	 * @param int $mode Preferred mode. Optional.
	 * @return void
	 */
	public static function set_error_handler ($mode=NULL)
	{
		set_error_handler(MegaErrorHandler__error_handler);
		if (isset($mode))
			self::set_debug_mode($mode);
	}
	
	/**
	 * Print out an error message if certain conditions are met.
	 *
	 * Will not print out the message if error_reporting doesn't match it.
	 * Will delay the printing of errors until after headers have been sent.
	 *
	 * @param int $errno Error severity number
	 * @param string $errstr Error message
	 * @param string $errfile File in which error occurred
	 * @param int $errline Line number of file which caused error
	 * @param array $errcontext Context information
	 * @return boolean Should error be considered handled?
	 */ 
	public function handle_error_conditional ($errno, $errstr, $errfile, $errline, $errcontext, $bt)
	{
		if (!($errno & error_reporting()))
			return TRUE;
		if (!$this->sent_headers())
			return $this->enqueue($errno, $errstr, $errfile, $errline, $errcontext, $bt);
		$this->handle_queue();
		return $this->handle_error($errno, $errstr, $errfile, $errline, $errcontext, $bt);
	}
	
	/**
	 * Check if PHP has sent HTTP header block yet.
	 *
	 * @return boolean Has it?
	 */
	private function sent_headers ()
	{
		return headers_sent();
	}
	
	/**
	 * Queue up an error message to be printed later.
	 *
	 * @param int $errno Error severity number
	 * @param string $errstr Error message
	 * @param string $errfile File in which error occurred
	 * @param int $errline Line number of file which caused error
	 * @param array $errcontext Context information
	 * @return boolean Successfully queued?
	 */ 
	private function enqueue ($errno, $errstr, $errfile, $errline, $errcontext, $bt)
	{
		$this->queue[] = array($errno, $errstr, $errfile, $errline, $errcontext, $bt);
		return TRUE;
	}
	
	/**
	 * Empty out queue of messages.
	 *
	 * Prints out any messages in the queue and removes them from it.
	 *
	 * @return bool Did anything actually need to be done?
	 */ 
	private function handle_queue ()
	{
		if (!count($this->queue) || !$this->sent_headers())
			return FALSE;
		
		while ($E = array_shift($this->queue))
		{
			list ($errno, $errstr, $errfile, $errline, $errcontext, $bt) = $E;
			$this->handle_error($errno, $errstr, $errfile, $errline, $errcontext, $bt);
		}
		return TRUE;
	}
	
	/**
	 * Print out an error message unconditionally.
	 *
	 * This should never be called directly. Use the conditional function
	 * instead.
	 *
	 * @param int $errno Error severity number
	 * @param string $errstr Error message
	 * @param string $errfile File in which error occurred
	 * @param int $errline Line number of file which caused error
	 * @param array $errcontext Context information
	 * @return boolean Should error be considered handled?
	 */ 
	private function handle_error ($errno, $errstr, $errfile, $errline, $errcontext, $bt)
	{
		for ($i=0; $i<1; $i++)
			array_shift($bt);

		$errorType = array (
			E_ERROR              => 'ERROR',
			E_WARNING            => 'WARNING',
			E_PARSE              => 'PARSING ERROR',
			E_NOTICE             => 'NOTICE',
			E_CORE_ERROR         => 'CORE ERROR',
			E_CORE_WARNING       => 'CORE WARNING',
			E_COMPILE_ERROR      => 'COMPILE ERROR',
			E_COMPILE_WARNING    => 'COMPILE WARNING',
			E_USER_ERROR         => 'USER ERROR',
			E_USER_WARNING       => 'USER WARNING',
			E_USER_NOTICE        => 'USER NOTICE',
			E_STRICT             => 'STRICT NOTICE',
			4096                 => 'RECOVERABLE ERROR'
			);
			      
		$E = array(
			'E_NUMBER'  => $errno,
			'E_TYPE'    => ucfirst(strtolower($errorType[$errno])),
			'E_STRING'  => $errstr,
			'E_FILE'    => $errfile,
			'E_BASENAME'=> basename($errfile),
			'E_LINE'    => $errline,
			'E_CONTEXT' => print_r($errcontext, TRUE),
			'_SERVER'   => print_r($_SERVER, TRUE),
			'_GET'      => print_r($_GET, TRUE),
			'_POST'     => print_r($_POST, TRUE),
			'_COOKIE'   => print_r($_COOKIE, TRUE),
			'_ENV'      => print_r($_ENV, TRUE),
			'GLOBALS'   => print_r($GLOBALS, TRUE),
			'classes'   => print_r(get_declared_classes(), TRUE),
			'interfaces'=> print_r(get_declared_interfaces(), TRUE),
			'constants' => print_r(get_defined_constants(), TRUE),
			'includes'  => print_r(get_included_files(), TRUE),
			'ini'       => print_r(ini_get_all(), TRUE),
			'extensions'=> print_r(get_loaded_extensions(), TRUE),
			'backtrace' => print_r($bt, TRUE),
			'source'    => highlight_file($errfile, TRUE)
			);

		if (strlen(session_id()))
			$E['_SESSION'] = print_r($_SESSION, TRUE);

		if ((self::DEBUG_JSON & self::$debug_mode) && $this->is_html())
		{
			print '<!--ERROR ' . 
				str_replace('--','=-', $this->json_encode($E)) . 
				" RORRE-->\n";
		}

		if ((self::DEBUG_HTMLX & self::$debug_mode) && $this->is_html())
		{
			printf("<p><b>Error (%d)</b> at <tt>%s</tt><small>:%s</small><br>%s.</p>\n",
				$errno, $errfile, $errline, $errstr);
			print "<pre>\n";
			print htmlentities(print_r($E, TRUE));
			print "</pre>\n";			
		}
		elseif ((self::DEBUG_HTML & self::$debug_mode) && $this->is_html())
		{
			printf("<p><b>Error (%d)</b> at <tt>%s</tt><small>:%s</small><br>%s.</p>\n",
				$errno, $errfile, $errline, $errstr);
		}

		return TRUE;
	}
	
	/**
	 * Detects whether the currently output page is in HTML.
	 *
	 * Or rather, doesn't at the moment. Needs working on.
	 *
	 * @return bool TRUE.
	 */
	private function is_html()
	{
		return TRUE;
	}

	/**
	 * Detects whether the currently output page is in HTML.
	 *
	 * Or rather, doesn't at the moment. Needs working on.
	 *
	 * @return bool TRUE.
	 */
	private function json_encode ($struct)
	{
		/* PHP 5.2 includes JSON abilities natively. If available
		 * then use them, because they're really fast.
		 */
		if (function_exists('json_encode'))
			return json_encode($struct);
	
		/* We need to support earlier versions of PHP 5.x though, so try
		 * using PEAR library too.
		 */
		if (isset($this->json_encoder) && ($this->json_encoder instanceof Services_JSON))
			return $this->json_encoder->encode($struct);
		
		$this->json_encoder = new Services_JSON;
		return $this->json_encoder->encode($struct);
	}	
}

/**
 * This function should be MegaErrorHandler::error_handler().
 *
 * Seems to be a bug in PHP 5.0.x (and possibly other versions of PHP) in dealing
 * with error handlers which are static class methods. It doesn't always seem to
 * cause a problem, but does so enough for it to *be* a problem.
 *
 * Anyway, this is the function which should be made the error_handler. You shouldn't
 * use set_error_handler directly. Call MegaErrorHandler::set_error_handler()
 * instead.
 *
 * @param int $errno Error severity number
 * @param string $errstr Error message
 * @param string $errfile File in which error occurred
 * @param int $errline Line number of file which caused error
 * @param array $errcontext Context information
 * @return boolean Should error be considered handled?
 */
function MegaErrorHandler__error_handler ($errno, $errstr, $errfile, $errline, $errcontext)
{
	if (!isset($errfile))
		$errfile = '';
	if (!isset($errline))
		$errline = 0;
	if (!isset($errcontext))
		$errcontext = array();

	$eh = MegaErrorHandler::singleton();
	return $eh->handle_error_conditional(	$errno,
						$errstr,
						$errfile, 
						$errline,
						$errcontext,
						debug_backtrace()
					    );
}

/**
 * Quick debugging function.
 */
function barf ($param)
{
	if (is_array($param))
		$param = print_r($param, TRUE);
	if (class_exists('DemiblogException'))
		throw new DemiblogException($param);
	throw new Exception($param);
}

?>
