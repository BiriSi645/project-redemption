<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('notes', 'Notes::index');
$routes->get('notes/create', 'Notes::create');
$routes->post('notes', 'Notes::store');
$routes->get('notes/(:num)/edit', 'Notes::edit/$1');
$routes->post('notes/(:num)', 'Notes::update/$1');
$routes->post('notes/(:num)/delete', 'Notes::delete/$1');
