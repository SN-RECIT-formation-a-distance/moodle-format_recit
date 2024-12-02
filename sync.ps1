$from = "moodle-format_recit/src/*"
$to = "shared/recitfad/course/format/recit"
$source = "./src";

try {
    . ("..\sync\watcher.ps1")
}
catch {
    Write-Host "Error while loading sync.ps1 script." 
}