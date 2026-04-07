$PhpExe = (Get-Command php -ErrorAction Stop).Source
$TaskName = "LondonLabels-Maintenance"
$RunTime = "03:30"
$ScriptPath = Join-Path $PSScriptRoot "run_maintenance.php"

$Action = New-ScheduledTaskAction -Execute $PhpExe -Argument "`"$ScriptPath`""
$Trigger = New-ScheduledTaskTrigger -Daily -At $RunTime
$Principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive -RunLevel Limited

Register-ScheduledTask -TaskName $TaskName -Action $Action -Trigger $Trigger -Principal $Principal -Force
Write-Host "Scheduled task '$TaskName' created to run daily at $RunTime"
