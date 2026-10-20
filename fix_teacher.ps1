$path = 'd:\xammpp\htdocs\edu-institution\js\teacher.js'
$text = [System.IO.File]::ReadAllText($path)
$text = $text.Replace('loadTeacherLessons();', 'void loadTeacherLessons();')
$text = $text.Replace('loadTeacherAnnouncements();', 'void loadTeacherAnnouncements();')
[System.IO.File]::WriteAllText($path, $text)
Write-Output "DONE"
