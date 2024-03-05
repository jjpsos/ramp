<?php declare(strict_types=1); 

use JimSos\Framework\Http\Request;
use JimSos\Framework\Http\Response;
use JimSos\Framework\Http\Kernel;

// public/index.php
/**
*  PHP Framework for Web Applications
*  Step by step by numbered steps (#)   
*/

// Debugging 
(new Phalcon\Support\Debug())->listen();

$rootPath = realpath('..');
require_once $rootPath . '/vendor/autoload.php';

// request received (1)
$request = Request::createFromGlobals();
//dd($request);

// perform some logic
// send response (string of content) (2)
// $content = '<h1>RAMP Framework</h1>';
// $response = new Response(content: $content, status: 200, headers: []);
// $response->send();

// perform some logic (3)
$kernel = new Kernel();
// send response (string of content)
$response = $kernel->handle($request);
/* dd($response);
JimSos\Framework\Http\Response {#7 ▼
    -content: "<h1>RAMP KERNEL</h1>"
    -status: 200
    -headers: []
  }
*/
$response->send();