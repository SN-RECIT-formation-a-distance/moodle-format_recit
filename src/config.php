<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'moodledev3';
$CFG->dbuser    = 'moodledev3';
$CFG->dbpass    = 'AS923kaPck8se45jz9kw';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => 3306,
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_general_ci',
);

$CFG->wwwroot   = 'https://moodledev3.recitfad.ca';
$CFG->dataroot  = '/var/www/virtual/moodledev3.recitfad.ca/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

@error_reporting(E_ALL | E_STRICT);   // NOT FOR PRODUCTION SERVERS!
@ini_set('display_errors', '1');         // NOT FOR PRODUCTION SERVERS!
$CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = 1;    

require_once(__DIR__ . '/lib/setup.php');


//DEBUG
//$CFG->debug = 6143;
//$CFG->debugdisplay = 1; 
//// Force a debugging mode regardless the settings in the site administration
//@error_reporting(E_ALL | E_STRICT); // NOT FOR PRODUCTION SERVERS!
//@ini_set('display_errors', '1');    // NOT FOR PRODUCTION SERVERS!
//$CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
//$CFG->debugdisplay = 1;             // NOT FOR PRODUCTION SERVERS!
//
