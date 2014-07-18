<?php

header('content-type: application/json; charset=utf-8');

// default port
$port = 80;

// request url
extract(parse_url($_REQUEST['url']));
unset($_REQUEST['url']);

if (!isset($path))
	$path = '/';

if (isset($query))
	$path .= '?'. $query;

$request = [
	"GET $path HTTP/1.1",
	"Host: $host",
];

// flag to fetch content
$content = !empty($_REQUEST['content']);

// unset system vars
unset($_REQUEST['interval'], $_REQUEST['content']);
// prepares request header
foreach ($_REQUEST as $k => $v)
	if ($v !== '')
		$request[] = $k .': '. $v;

// request header end
$request[] = PHP_EOL;

// request time
$time = microtime(true);

// creates a connection
$fp = fsockopen($host, $port, $errno, $errstr, 30);

// sends header
fwrite($fp, implode(PHP_EOL, $request));

// response time
$time = microtime(true) - $time;

$response = [];
$header = true;

// reads response
while (!feof($fp)) {
	$buffer = trim(fgets($fp, 4096));
	// end of header
	if ($header && $buffer == '') {
		// fetch content if flag is true
		if ($content) {
			$response['content'] = '';
			$header = false;
			continue;
		}
		// just header
		else
			break;
	}
	
	// response status
	if (!$response)
		$response['status'] = (int) explode(' ', $buffer)[1];
	// fetching header
	elseif ($header) {
		$temp = preg_split('#:\s*#', $buffer, 2);
		$response[$temp[0]] = $temp[1];
	}
	// fetching content
	else {
		$response['content'] .= $buffer;
	}
}

// closes connection
fclose($fp);

// content-length
// converts to number
if (isset($response['Content-Length']))
	$response['Content-Length'] = (int) $response['Content-Length'];
// if is not set tries to get the content length manually
else if ($content && empty($response['Content-Length']))
	$response['Content-Length'] = strlen($response['content']);

// response time in milliseconds
$response['time'] = round($time * 1000);

echo json_encode($response);

?>