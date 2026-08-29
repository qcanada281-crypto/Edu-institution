$files = @(
'admin_auth.php','admin_lessons.php','admin_messages.php','admin_records.php','admin_students.php','blog_api.php','contact_process.php','dashboard.php','db_health.php','db_test.php','enroll.php','gallery_api.php','get_courses.php','health_check.php','lessons_list.php','login.php','login_diagnostic.php','logout.php','migrate_blog_media.php','migrate_gallery_photos.php','migrate_student_status.php','parent_attendance.php','portal_auth.php','promote_students.php','public_teachers.php','rbac_middleware.php','rbac_schema.php','register.php','reset_password.php','seed.php','student_register.php','subscribe_newletter.php','teacher_absence.php','teacher_auth.php','teacher_homework.php','teacher_profile.php','teacher_register.php','teacher_timetable.php','transcript_lookup.php'
)
$out = 'd:/xammpp/htdocs/edu-institution/backend/probe_results.txt'
if (Test-Path $out) { Remove-Item $out }
foreach ($f in $files) {
    $url = "http://localhost:8080/edu-institution/backend/$f"
    try {
        $resp = Invoke-WebRequest -Uri $url -Method Post -Body @{ action='list' } -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 30
        $status = $resp.StatusCode
        $bodyFull = $resp.Content
    } catch {
        $err = $_.Exception.Response
        if ($err -ne $null) {
            $status = [int]$err.StatusCode.value__
            $sr = $err.GetResponseStream()
            $reader = New-Object System.IO.StreamReader($sr)
            $bodyFull = $reader.ReadToEnd()
        } else {
            $status = 0
            $bodyFull = $_.Exception.Message
        }
    }
    $snippet = ''
    if ($null -ne $bodyFull) {
        $len = [Math]::Min(200,$bodyFull.Length)
        $snippet = $bodyFull.Substring(0,$len).Replace("`r`n"," ").Replace("`n"," ")
    }
    "$f|$status|$snippet" | Out-File -FilePath $out -Append
}
Get-Content $out | Write-Output
