<?php
$observers = array(
    array(
        'eventname'   => 'core\event\course_module_completion_updated',
        'callback'    => 'treetopics_completion_callback',
        'internal'    => false,
        'includefile' => 'course/format/treetopics/locallib.php',
    ),
);

