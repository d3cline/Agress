<?php

/** @var \Laravel\Lumen\Routing\Router $router */

//$router->post('/send-message', 'SignalController@sendMessage');

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/products', 'ProductController@index');
$router->get('/product/{id}', 'ProductController@show');
$router->post('/checkout', 'CheckoutController@processCheckout');
$router->get('/order-status/{orderId}', 'CheckoutController@getOrderStatus');


$router->group(['middleware' => 'admin.auth'], function () use ($router) {
    $router->post('/product', ['uses' => 'ProductController@store']);
    $router->patch('/product/{id}', 'ProductController@update');
    $router->delete('/product/{id}', 'ProductController@destroy');
});

