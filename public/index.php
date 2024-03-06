<?php declare(strict_types=1); 

use JimSos\Framework\Http\Request;
use JimSos\Framework\Http\Response;
use JimSos\Framework\Http\Kernel;

/**
*  PHP Framework for Web Applications
*  Request -> Response Cycle   
*/

// Debugging 
(new Phalcon\Support\Debug())->listen();

$rootPath = realpath('..');
require_once $rootPath . '/vendor/autoload.php';

// request received (1)
$request = Request::createFromGlobals();

// perform some logic (3)
$kernel = new Kernel();

// send response (string of content)
$response = $kernel->handle($request);
$response->send();