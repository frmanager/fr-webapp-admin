<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('student_index', new Route(
    '/',
    array('_controller' => 'AppBundle:Student:index'),
    array(),
    array(),
    '',
    array(),
    array('GET')
));

$collection->add('student_show', new Route(
    '/{id}/show',
    array('_controller' => 'AppBundle:Student:show'),
    array(),
    array(),
    '',
    array(),
    array('GET')
));

$collection->add('student_new', new Route(
    '/new',
    array('_controller' => 'AppBundle:Student:new'),
    array(),
    array(),
    '',
    array(),
    array('GET', 'POST')
));

$collection->add('student_edit', new Route(
    '/{id}/edit',
    array('_controller' => 'AppBundle:Student:edit'),
    array(),
    array(),
    '',
    array(),
    array('GET', 'POST')
));

$collection->add('student_delete', new Route(
    '/{id}/delete',
    array('_controller' => 'AppBundle:Student:delete'),
    array(),
    array(),
    '',
    array(),
    array('DELETE')
));

return $collection;
