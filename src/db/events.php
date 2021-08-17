<?php
$observers = array(
    array(
        'eventname'   => 'core\event\course_module_completion_updated',
        'callback'    => 'treetopics_completion_callback',
    ),
);

