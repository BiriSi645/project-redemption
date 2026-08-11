<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Auth::index');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::storeLogin');
$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::storeRegister');

$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->post('logout', 'Auth::logout');
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('tasks', 'Tasks::index');
    $routes->get('tasks/create', 'Tasks::create');
    $routes->post('tasks', 'Tasks::store');
    $routes->get('tasks/(:num)/edit', 'Tasks::edit/$1');
    $routes->post('tasks/(:num)', 'Tasks::update/$1');
    $routes->post('tasks/(:num)/toggle', 'Tasks::toggle/$1');
    $routes->post('tasks/(:num)/delete', 'Tasks::delete/$1');
    $routes->get('journal', 'Journal::index');
    $routes->get('journal/create', 'Journal::create');
    $routes->post('journal', 'Journal::store');
    $routes->get('journal/(:num)', 'Journal::show/$1');
    $routes->get('journal/(:num)/edit', 'Journal::edit/$1');
    $routes->post('journal/(:num)', 'Journal::update/$1');
    $routes->post('journal/(:num)/delete', 'Journal::delete/$1');
    $routes->get('timer', 'Timer::index');
    $routes->get('notes', 'Notes::index');
    $routes->get('notes/create', 'Notes::create');
    $routes->post('notes', 'Notes::store');
    $routes->get('notes/(:num)', 'Notes::show/$1');
    $routes->get('notes/(:num)/edit', 'Notes::edit/$1');
    $routes->post('notes/(:num)', 'Notes::update/$1');
    $routes->post('notes/(:num)/delete', 'Notes::delete/$1');
});
