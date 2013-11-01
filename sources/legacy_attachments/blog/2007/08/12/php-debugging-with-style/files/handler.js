/*
    json.js
    2007-08-05

    Public Domain

    This file adds these methods to JavaScript:

        array.toJSONString()
        boolean.toJSONString()
        date.toJSONString()
        number.toJSONString()
        object.toJSONString()
        string.toJSONString()
            These methods produce a JSON text from a JavaScript value.
            It must not contain any cyclical references. Illegal values
            will be excluded.

            The default conversion for dates is to an ISO string. You can
            add a toJSONString method to any date object to get a different
            representation.

        string.parseJSON(filter)
            This method parses a JSON text to produce an object or
            array. It can throw a SyntaxError exception.

            The optional filter parameter is a function which can filter and
            transform the results. It receives each of the keys and values, and
            its return value is used instead of the original value. If it
            returns what it received, then structure is not modified. If it
            returns undefined then the member is deleted.

            Example:

            // Parse the text. If a key contains the string 'date' then
            // convert the value to a date.

            myData = text.parseJSON(function (key, value) {
                return key.indexOf('date') >= 0 ? new Date(value) : value;
            });

    It is expected that these methods will formally become part of the
    JavaScript Programming Language in the Fourth Edition of the
    ECMAScript standard in 2008.

    This file will break programs with improper for..in loops. See
    http://yuiblog.com/blog/2006/09/26/for-in-intrigue/

    This is a reference implementation. You are free to copy, modify, or
    redistribute.

    Use your own copy. It is extremely unwise to load untrusted third party
    code into your pages.
*/

/*jslint evil: true */

// Augment the basic prototypes if they have not already been augmented.

if (!Object.prototype.toJSONString) {

    Array.prototype.toJSONString = function () {
        var a = [],     // The array holding the partial texts.
            i,          // Loop counter.
            l = this.length,
            v;          // The value to be stringified.


// For each value in this array...

        for (i = 0; i < l; i += 1) {
            v = this[i];
            switch (typeof v) {
            case 'object':

// Serialize a JavaScript object value. Ignore objects thats lack the
// toJSONString method. Due to a specification error in ECMAScript,
// typeof null is 'object', so watch out for that case.

                if (v) {
                    if (typeof v.toJSONString === 'function') {
                        a.push(v.toJSONString());
                    }
                } else {
                    a.push('null');
                }
                break;

            case 'string':
            case 'number':
            case 'boolean':
                a.push(v.toJSONString());

// Values without a JSON representation are ignored.

            }
        }

// Join all of the member texts together and wrap them in brackets.

        return '[' + a.join(',') + ']';
    };


    Boolean.prototype.toJSONString = function () {
        return String(this);
    };


    Date.prototype.toJSONString = function () {

// Eventually, this method will be based on the date.toISOString method.

        function f(n) {

// Format integers to have at least two digits.

            return n < 10 ? '0' + n : n;
        }

        return '"' + this.getUTCFullYear() + '-' +
                f(this.getUTCMonth() + 1) + '-' +
                f(this.getUTCDate()) + 'T' +
                f(this.getUTCHours()) + ':' +
                f(this.getUTCMinutes()) + ':' +
                f(this.getUTCSeconds()) + 'Z"';
    };


    Number.prototype.toJSONString = function () {

// JSON numbers must be finite. Encode non-finite numbers as null.

        return isFinite(this) ? String(this) : 'null';
    };


    Object.prototype.toJSONString = function () {
        var a = [],     // The array holding the partial texts.
            k,          // The current key.
            v;          // The current value.

// Iterate through all of the keys in the object, ignoring the proto chain
// and keys that are not strings.

        for (k in this) {
            if (typeof k === 'string' &&
                    Object.prototype.hasOwnProperty.apply(this, [k])) {
                v = this[k];
                switch (typeof v) {
                case 'object':

// Serialize a JavaScript object value. Ignore objects that lack the
// toJSONString method. Due to a specification error in ECMAScript,
// typeof null is 'object', so watch out for that case.

                    if (v) {
                        if (typeof v.toJSONString === 'function') {
                            a.push(k.toJSONString() + ':' + v.toJSONString());
                        }
                    } else {
                        a.push(k.toJSONString() + ':null');
                    }
                    break;

                case 'string':
                case 'number':
                case 'boolean':
                    a.push(k.toJSONString() + ':' + v.toJSONString());

// Values without a JSON representation are ignored.

                }
            }
        }

// Join all of the member texts together and wrap them in braces.

        return '{' + a.join(',') + '}';
    };


    (function (s) {

// Augment String.prototype. We do this in an immediate anonymous function to
// avoid defining global variables.

// m is a table of character substitutions.

        var m = {
            '\b': '\\b',
            '\t': '\\t',
            '\n': '\\n',
            '\f': '\\f',
            '\r': '\\r',
            '"' : '\\"',
            '\\': '\\\\'
        };


        s.parseJSON = function (filter) {
            var j;

            function walk(k, v) {
                var i;
                if (v && typeof v === 'object') {
                    for (i in v) {
                        if (Object.prototype.hasOwnProperty.apply(v, [i])) {
                            v[i] = walk(i, v[i]);
                        }
                    }
                }
                return filter(k, v);
            }


// Parsing happens in three stages. In the first stage, we run the text against
// a regular expression which looks for non-JSON characters. We are especially
// concerned with '()' and 'new' because they can cause invocation, and '='
// because it can cause mutation. But just to be safe, we will reject all
// unexpected characters.

// We split the first stage into 3 regexp operations in order to work around
// crippling deficiencies in Safari's regexp engine. First we replace all
// backslash pairs with '@' (a non-JSON character). Second we delete all of
// the string literals. Third, we look to see if only JSON characters
// remain. If so, then the text is safe for eval.

            if (/^[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]*$/.test(this.
                    replace(/\\./g, '@').
                    replace(/"[^"\\\n\r]*"/g, ''))) {

// In the second stage we use the eval function to compile the text into a
// JavaScript structure. The '{' operator is subject to a syntactic ambiguity
// in JavaScript: it can begin a block or an object literal. We wrap the text
// in parens to eliminate the ambiguity.

                j = eval('(' + this + ')');

// In the optional third stage, we recursively walk the new structure, passing
// each name/value pair to a filter function for possible transformation.

                return typeof filter === 'function' ? walk('', j) : j;
            }

// If the text is not JSON parseable, then a SyntaxError is thrown.

            throw new SyntaxError('parseJSON');
        };


        s.toJSONString = function () {

// If the string contains no control characters, no quote characters, and no
// backslash characters, then we can simply slap some quotes around it.
// Otherwise we must also replace the offending characters with safe
// sequences.

            if (/["\\\x00-\x1f]/.test(this)) {
                return '"' + this.replace(/[\x00-\x1f\\"]/g, function (a) {
                    var c = m[a];
                    if (c) {
                        return c;
                    }
                    c = a.charCodeAt();
                    return '\\u00' +
                        Math.floor(c / 16).toString(16) +
                        (c % 16).toString(16);
                }) + '"';
            }
            return '"' + this + '"';
        };
    })(String.prototype);
}

/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////




var ALE = function(func)
{    
    var oldonload = window.onload;
    if (typeof window.onload != 'function')
    {
        window.onload = func;
    } 
    else 
    {
        window.onload = function()
        {
            oldonload();
            func();
        }
    }
}

var $debuggerErrors = new Array(0);
var $debugger;
var $debuggerWrapper;
var $debuggerHeading;
var $debuggerPanel;
var $debuggerPanel2;
var $selectedBug;

function debugger_parser ()
{
	var $src = document.documentElement.outerHTML ||
		   document.documentElement.innerHTML ;
	var $errors = $src.match(/ERROR .* RORRE/g);

	for (var $i=0; $i<$errors.length; $i++)
	{
		$e = $errors[$i].substr(6, $errors[$i].length-12);
		$debuggerErrors.push($e.parseJSON());
	}
}

function debugger_show ()
{
	$debugger = document.createElement('ol');

	$debuggerWrapper = document.createElement('div');
	$debuggerWrapper.id = 'debuggerwrapper';
	
	$debuggerHeading = document.createElement('h1');
	$debuggerHeading.innerHTML = 'Bugs';

	$debuggerPanel   = document.createElement('ul');
	$debuggerPanel.id = 'debuggerpanel';

	$debuggerPanel2   = document.createElement('pre');
	$debuggerPanel2.id = 'debuggerpanel2';

	$debuggerWrapper.appendChild($debuggerHeading);
	$debuggerWrapper.appendChild($debugger);
	$debuggerWrapper.appendChild($debuggerPanel);
	$debuggerWrapper.appendChild($debuggerPanel2);
	document.body.appendChild($debuggerWrapper);
}

function debugger_show_bug ($err, $n)
{
	var $li = document.createElement('li');
	$li.id = 'error' + $n;
	$li.innerHTML = '<strong>' + $err.E_TYPE + ': </strong>' + $err.E_STRING + '<br />'
		+ '<cite>' + $err.E_BASENAME + '<small>:' + $err.E_LINE + '</small></cite>';
	$li.onclick = debugger_select_bug;
	$debugger.appendChild($li);
}

function debugger_select_bug ($event)
{
	if (!$event) var $event = window.event;
	if ($event.target) $targ = $event.target;
	else if ($event.srcElement) $targ = $event.srcElement;
	if ($targ.nodeType == 3) // defeat Safari bug
		$targ = $targ.parentNode;
	while ($targ.tagName != 'LI')
		$targ = $targ.parentNode;

	var $nodes = $debugger.childNodes;
	for (var $i=0; $i<$nodes.length; $i++)
		if ($nodes[$i].tagName=='LI')
			$nodes[$i].className = '';
	$targ.className = 'selected-bug';

	var $n = $targ.id.substr('error'.length);
	
	$selectedBug = $debuggerErrors[$n];

	$debuggerPanel.innerHTML = '';
		
	$nodes = Array();
	$nodes[0] = document.createElement('li');
	$nodes[0].innerHTML = 'Backtrace';
	$nodes[1] = document.createElement('li');
	$nodes[1].innerHTML = 'Context';
	$nodes[2] = document.createElement('li');
	$nodes[2].innerHTML = 'Source';
	$nodes[3] = document.createElement('li');
	$nodes[3].innerHTML = '$_GET';
	$nodes[4] = document.createElement('li');
	$nodes[4].innerHTML = '$_COOKIE';
	$nodes[5] = document.createElement('li');
	$nodes[5].innerHTML = '$_POST';
	$nodes[6] = document.createElement('li');
	$nodes[6].innerHTML = '$_SERVER';
	$nodes[7] = document.createElement('li');
	$nodes[7].innerHTML = '$_SESSION';
	$nodes[8] = document.createElement('li');	
	$nodes[8].innerHTML = '$_ENV';
	$nodes[9] = document.createElement('li');
	$nodes[9].innerHTML = '$GLOBALS';
	$nodes[10] = document.createElement('li');
	$nodes[10].innerHTML = 'Declared classes';
	$nodes[11] = document.createElement('li');
	$nodes[11].innerHTML = 'Declared interfaces';
	$nodes[12] = document.createElement('li');
	$nodes[12].innerHTML = 'Defined constants';
	$nodes[13] = document.createElement('li');
	$nodes[13].innerHTML = 'Included files';
	$nodes[14] = document.createElement('li');
	$nodes[14].innerHTML = 'Settings';	
	$nodes[15] = document.createElement('li');
	$nodes[15].innerHTML = 'Loaded extensions';

	for (var $i=0; $i<$nodes.length; $i++)
	{
		$nodes[$i].onclick = debugger_select_aspect;
		$debuggerPanel.appendChild($nodes[$i]);
	}
	$debuggerPanel2.innerHTML = '';	
}

function debugger_select_aspect ($event)
{
	if (!$event) var $event = window.event;
	if ($event.target) $targ = $event.target;
	else if ($event.srcElement) $targ = $event.srcElement;
	if ($targ.nodeType == 3) // defeat Safari bug
		$targ = $targ.parentNode;
	while ($targ.tagName != 'LI')
		$targ = $targ.parentNode;
		
	var $nodes = $debuggerPanel.childNodes;
	for (var $i=0; $i<$nodes.length; $i++)
		if ($nodes[$i].tagName=='LI')
			$nodes[$i].className = '';
	$targ.className = 'selected-aspect';
	
	var $x = 'ARGH';
	switch ($targ.innerHTML)
	{
		case "Context":
			$x = $selectedBug.E_CONTEXT;
			break;
		case "$_COOKIE":
			$x = $selectedBug._COOKIE;
			break;
		case "$_GET":
			$x = $selectedBug._GET;
			break;
		case "$_POST":
			$x = $selectedBug._POST;
			break;
		case "$_SERVER":
			$x = $selectedBug._SERVER;
			break;
		case "$_SESSION":
			$x = $selectedBug._SESSION;
			break;
		case "$_ENV":
			$x = $selectedBug._ENV;
			break;
		case "$GLOBALS":
			$x = $selectedBug.GLOBALS;
			break;
		case "Declared classes":
			$x = $selectedBug.classes;
			break;
		case "Declared interfaces":
			$x = $selectedBug.interfaces;
			break;
		case "Defined constants":
			$x = $selectedBug.constants;
			break;
		case "Included files":
			$x = $selectedBug.includes;
			break;
		case "Settings":
			$x = $selectedBug.ini;
			break;
		case "Loaded extensions":
			$x = $selectedBug.extensions;
			break;
		case "Backtrace":
			$x = $selectedBug.backtrace;
			break;
		case "Source":
			$x = $selectedBug.source;
			break;

	}
	$debuggerPanel2.innerHTML = $x;
	
}
function debugger_init ()
{
	debugger_parser();
	if ($debuggerErrors.length > 0)
		debugger_show();	
	for (var $i=0; $i<$debuggerErrors.length; $i++)
		debugger_show_bug($debuggerErrors[$i], $i);
}

ALE(debugger_init);
