<?php


namespace Whiskey\Bourbon\App\Http;


/**
 * MiddlewareInterface, to be implemented by individual middleware classes
 * @package Whiskey\Bourbon\App\Http
 */
interface MiddlewareInterface
{


	/**
	 * Pass the request through the middleware to be handled
	 * @param Request  $request  HTTP Request object
	 * @param Response $response HTTP Response object
	 */
	public function handle(Request $request, Response $response);
	
	
}