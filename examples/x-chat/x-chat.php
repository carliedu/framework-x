<?php
//	*==========================================================================*
//	This program x-chat.php is based on https://github.com/clue/php-sse-react/examples/11-chat.php
//	Use: # cd /usr/local/src/framework-x\
//		    # php x-chat.php {port}
//
//	Development:
//	Created on ........:  08/06/2023 - CARLIEDU - Fork from clue/framework-x/examples/index.php
//	Last changed on....:  09/06/2023 - CARLIEDU - Copied 10-eventsource.html from clue/php-sse-react/examples/
//	Last changed on....:  09/06/2023 - CARLIEDU - Copied 10-styles.css from clue/php-sse-react/examples/
//	Last changed on....:  22/06/2023 - CARLIEDU - Added code from 11-chat.php
//	Last changed on....:  28/06/2023 - CARLIEDU - As proposed by SimonFrings, $loop->futureTick code changed
//	Last changed on....:  28/06/2023 - CARLIEDU - Included instantiation of BufferedChannel after instantiation of FrameworkX\App
//	Last changed on....:  28/06/2023 - CARLIEDU - As proposed by SimonFrings, changed React\Http\Message\Response to send 10-syles.css

	ini_set("display_errors","On"); // activate display_error	
	error_reporting(E_ALL);

	require __DIR__ . '/vendor/autoload.php';

	date_default_timezone_set('America/Sao_Paulo');
	echo(date("d/m/Y-H:i:s")." (/x-chat.php) *---INI---INI---INI---INI---INI--\n");

	use Clue\React\Sse\BufferedChannel;
	use Psr\Http\Message\ServerRequestInterface;
	use React\Stream\ThroughStream;
	
	foreach ($argv as $k => $arg) {
		if($k <> 0) {	// First argument is program name: discarded
			if(is_numeric($arg)) {
				$portUsed = $arg;
			}
		}
	}

//	If no PORT provided, portUsed assumes 8080
	$portUsed	= isset($portUsed) ? $portUsed : 8080;

	$container = new FrameworkX\Container([
	    'X_LISTEN' => fn(string $PORT = '8080') => '0.0.0.0:' . $portUsed
	]);

//------Begin of LOOP
	$app = new FrameworkX\App($container);
		$channel = new BufferedChannel();

//=============	/ 		-->	Send file 10-eventsource.html==================
		$app->get('/', function () {
			echo(date("d/m/Y-H:i:s")." (/x-chat.php) getUri /   -> Send 10-eventsource.html \n");
			return React\Http\Message\Response::html(
				file_get_contents(__DIR__ . '/10-eventsource.html')
			);
		});

//============= /styles.css	--> Send file 10-styles.css==============
		$app->get('/styles.css', function (Psr\Http\Message\ServerRequestInterface $request) {
			echo(date("d/m/Y-H:i:s")." (/x-chat.php) getUri /styles.css   -> Send 10-styles.css \n");
			return new React\Http\Message\Response(
				React\Http\Message\Response::STATUS_OK,
				array('Content-Type' => 'text/css; charset=utf-8;'),
				file_get_contents(__DIR__ . '/10-styles.css')
			);
		});

//============= /chat		--> Chat begin/User identification ==============
		$app->get('/chat', function (Psr\Http\Message\ServerRequestInterface $request) {
			$stream = new ThroughStream();
			
			$id = $request->getHeaderLine('Last-Event-ID');
			$loop = Loop::get();

			$loop->futureTick(function () use ($channel, $stream, $id) {
    				$channel->connect($stream, $id);
			});			
			
			$serverParams = $request->getServerParams();
			echo(date("d/m/Y-H:i:s")." (/x-chat.php) getUri /chat   -> Called from ".$serverParams['REMOTE_ADDR']."\n");
			$message = array('message' => 'New Browser connected from '. $serverParams['REMOTE_ADDR']);
			$channel->writeMessage(json_encode($message));
			
			$stream->on('close', function () use ($stream, $channel, $request, $serverParams) {
			    $serverParams = $request->getServerParams();
			    $channel->disconnect($stream);
			
			    $message = array('message' => 'Bye '. $serverParams['REMOTE_ADDR']);
			    $channel->writeMessage(json_encode($message));
			});
			
			return new Response(
			    200,
			    array('Content-Type' => 'text/event-stream'),
			    $stream
			);
		});

//============= /message	--> User typed a message ==============
		$app->get('/message', function (Psr\Http\Message\ServerRequestInterface $request) {
		            $query = $request->getQueryParams();
		            $serverParams = $request->getServerParams();
		            echo(date("d/m/Y-H:i:s")." (/11-chat.php) getUri message received: [".$query['message']."] from: [".$query['username']."] at: [".$serverParams['REMOTE_ADDR']."] \n");
		            if (isset($query['username'], $query['message'])) {
		                $message = array('message' => '('.$serverParams['REMOTE_ADDR'].') > '.$query['message'], 'username' => $query['username']);
		                $channel->writeMessage(json_encode($message));
		            }
		
		            return new Response(
		                '201',
		                array('Content-Type' => 'text/json')
		            );
		});

//------End of LOOP
	$app->run();
?>
