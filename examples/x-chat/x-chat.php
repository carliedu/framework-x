<?php
//	*==========================================================================*
//	This program x-chat.php is based on Based on https://github.com/clue/php-sse-react/examples/11-chet.php
//	Use: # cd /usr/local/src/framework-x\
//		    # php x-chat.php {port}
//
//	Development:
//	Created on ........:  08/06/2023 - CARLIEDU - Fork from clue/framework-x/examples/index.php
//  Last changed on....:  09/06/2023 - CARLIEDU - Copied 10-eventsource.html from clue/php-sse-react/examples/
//  Last changed on....:  09/06/2023 - CARLIEDU - Copied 10-styles.css from clue/php-sse-react/examples/

	ini_set("display_errors","On"); //activate display_error	
	error_reporting(E_ALL);

	require __DIR__ . '/vendor/autoload.php';
	include_once("HSMNbiblio.php");	// Carrega biblioteca de funcoes padrao Huesmann

	date_default_timezone_set('America/Sao_Paulo');
	echo(date("d/m/Y-H:i:s")." (/x-chat.php) ".f_strCor("*---".
		"INI---INI---INI---INI---INI---INI---INI---INI---INI---INI---INI---", "AZ")."\n");

	use Clue\React\Sse\BufferedChannel;
	use Psr\Http\Message\ServerRequestInterface;
	use React\Stream\ThroughStream;
	
	// example uses `@include` for test suite only, real-world examples should use `require` instead
	// require __DIR__ . '/vendor/autoload.php';
	
	foreach ($argv as $k => $arg) {
		if($k <> 0) {	// O primeiro argumento eh o proprio nome do prograna
			if(is_numeric($arg)) {
				$portaIP = $arg;
			} else {
				$argCodUsuario = $arg;
			}
		}
	}

//	Se nao for fornecido argumento numerico na linha de comando, portaIP eh assumida 53031 (testes)
	$portaIP	= isset($portaIP) ? $portaIP : 53031;

	$container = new FrameworkX\Container([
	    'X_LISTEN' => fn(string $PORT = '8080') => '0.0.0.0:' . $portaIP
	]);

//------Begin of LOOP
	$app = new FrameworkX\App($container);

//============= / --> Envia 10-eventsource.html==================
		$app->get('/', function () {
			echo(date("d/m/Y-H:i:s")." (/X-chat.php) ".f_strCor("getUri /:> Send HTML", "AZ")."\n");
			return React\Http\Message\Response::html(
				file_get_contents(__DIR__ . '/10-eventsource.html')
			);
		});

//============= /styles.css --> Envia 10-styles.css==============
		$app->get('/styles.css', function (Psr\Http\Message\ServerRequestInterface $request) {
			echo(date("d/m/Y-H:i:s")." (/X-chat.php) ".f_strCor("getUri css: Send CSS", "AZ")."\n");
			return React\Http\Message\Response::html(
				file_get_contents(__DIR__ . '/10-styles.css')
			);
		});

//============= /chat --> ???????????==============
		$app->get('/chat', function (Psr\Http\Message\ServerRequestInterface $request) {
			$stream = new ThroughStream();
//			die('<pre>'.print_r($stream,1).'</pre>');
			
			$id = $request->getHeaderLine('Last-Event-ID');
//			$loop->futureTick(function () use ($channel, $stream, $id) {
//			    $channel->connect($stream, $id);
//			});
			
			$serverParams = $request->getServerParams();
			echo(date("d/m/Y-H:i:s")." (/X-chat.php) ".f_strCor("getUri chat: Called from ".$serverParams['REMOTE_ADDR'], "AZ")."\n");
			$message = array('message' => 'Novo navegador conectado de '. $serverParams['REMOTE_ADDR']);
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

//============= /message --> ???????????==============
		$app->get('/message', function (Psr\Http\Message\ServerRequestInterface $request) {
		            $query = $request->getQueryParams();
		            $serverParams = $request->getServerParams();
		            echo(date("d/m/Y-H:i:s")." (/11-chat.php) ".f_strCor("getUri message received: [".$query['message']."] from: [".$query['username']."] at: [".$serverParams['REMOTE_ADDR']."]", "AZ")."\n");
		            if (isset($query['username'], $query['message'])) {
		                $message = array('message' => '('.$serverParams['REMOTE_ADDR'].') > '.$query['message'], 'username' => $query['username']);
		                $channel->writeMessage(json_encode($message));
		            }
		
		            return new Response(
		                '201',
		                array('Content-Type' => 'text/json')
		            );
		});



























			$app->run();
?>
