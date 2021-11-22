<?php
$observers = array(
    array(
        'eventname'   => 'core\event\course_module_completion_updated',
        'callback'    => 'formatrecit_completion_callback',
        'internal'    => false,
        'includefile' => 'course/format/recit/locallib.php',
    ),
);

