<?php

require_once '../core/Router.php';
$router = new Router();

$router->get('users/manage', 'Users@manage');
$router->get('users/activate/{id}', 'Users@activate');
$router->get('users/deactivate/{id}', 'Users@deactivate');
$router->get('users/changeRole/{id}/{role}', 'Users@changeRole');
$router->get('activitylogs', 'ActivityLogs@index');
$router->post('users/search', 'Users@search');
$router->post('activityLogs/search', 'ActivityLogs@search');


?>
