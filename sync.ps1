$from = "moodle-format_recit/src/*"
$to = "shared/recitfad2/course/format/recit"

try {
    . ("..\sync\watcher.ps1")
}
catch {
    Write-Host "Error while loading sync.ps1 script." 
}