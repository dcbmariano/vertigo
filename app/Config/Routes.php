<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::entrada');
$routes->post('/run', 'Run::index');
$routes->get('/documentation', 'Home::documentation');
$routes->get('project/(:any)/alignments','Project::alignments/$1');
$routes->get('/project/(:any)', 'Project::index/$1');
$routes->post('project/(:segment)/save-alignments', 'Project::saveAlignments/$1');
$routes->get('/project-example', 'Project::example');
