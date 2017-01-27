<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';
require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel('test_behat', true);
$kernel->loadClassCache();
$request = Request::createFromGlobals();

if ('OPTIONS' === $request->getMethod()) {
    $response = new Response();
} else {
    $response = $kernel->handle($request);
}
$response->headers->set('Access-Control-Allow-Origin', '*');
$response->headers->set('Access-Control-Allow-Headers', 'content-disposition, accept, origin, x-requested-with, authorization, content-type');
$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');

$response->send();

$kernel->terminate($request, $response);
