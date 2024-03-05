<?php declare(strict_types=1); 

use JimSos\Framework\Http\Request;
use JimSos\Framework\Http\Response;

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
$content = '<h1>RAMP Framework</h1>';

$response = new Response(content: $content, status: 200, headers: []);

$response->send();
