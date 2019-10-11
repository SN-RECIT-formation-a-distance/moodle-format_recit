echo off
set pluginPath=..\moodle\course\format\treetopics

rem remove the current link
..\outils\junction -d src

rem set the link
..\outils\junction src %pluginPath%

pause