#requires -Version 5.1
<#
Measure per-target start/reset/RAM/disk on this host. Writes docs/pilot-results.json.
Ports start at 8401 to avoid the student pilot instances.
#>
param([int]$BasePort = 8401)

$ErrorActionPreference = 'Stop'
$repo = Split-Path -Parent $PSScriptRoot
$php = if (Get-Command php -ErrorAction SilentlyContinue) { (Get-Command php).Source } else { 'C:\xampp\php\php.exe' }
$results = New-Object System.Collections.Generic.List[object]
$targets = @('kampusmart', 'perpusdigital', 'eventpass')
$index = 0

foreach ($target in $targets) {
    $port = $BasePort + $index
    $index++
    $variant = 'vulnerable'
    $dist = Join-Path $repo "dist\$target-$variant"

    & (Join-Path $PSScriptRoot 'lab.ps1') -Action build -Target $target -Variant $variant -Port $port | Out-Null

    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    & (Join-Path $PSScriptRoot 'lab.ps1') -Action start -Target $target -Variant $variant -Port $port | Out-Null
    $sw.Stop()
    $startSeconds = [math]::Round($sw.Elapsed.TotalSeconds, 2)

    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    & (Join-Path $PSScriptRoot 'lab.ps1') -Action reset -Target $target -Variant $variant -Port $port | Out-Null
    $sw.Stop()
    $resetSeconds = [math]::Round($sw.Elapsed.TotalSeconds, 2)

    # Reset restarts the server, so resolve the new process before sampling.
    $listener = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction Stop | Select-Object -First 1
    $procId = [int]$listener.OwningProcess
    $proc = Get-Process -Id $procId
    $cpu1 = $proc.TotalProcessorTime.TotalMilliseconds
    Start-Sleep -Milliseconds 1500
    $proc.Refresh()
    $cpu2 = $proc.TotalProcessorTime.TotalMilliseconds
    $cpuPercent = [math]::Round((($cpu2 - $cpu1) / 1500.0) * 100 / [Environment]::ProcessorCount, 1)
    $proc.Refresh()
    $distBytes = (Get-ChildItem $dist -Recurse -File | Measure-Object Length -Sum).Sum
    $dbBytes = (Get-Item (Join-Path $dist 'run\lab.sqlite') -ErrorAction SilentlyContinue).Length

    $results.Add([ordered]@{
        target = $target
        variant = $variant
        port = $port
        start_seconds = $startSeconds
        reset_seconds = $resetSeconds
        rss_mb = [math]::Round($proc.WorkingSet64 / 1MB, 1)
        private_mb = [math]::Round($proc.PrivateMemorySize64 / 1MB, 1)
        cpu_percent_one_core = $cpuPercent
        dist_mb = [math]::Round($distBytes / 1MB, 2)
        db_kb = [math]::Round($dbBytes / 1KB, 1)
        host_ram_gb = [math]::Round((Get-CimInstance Win32_ComputerSystem).TotalPhysicalMemory / 1GB, 1)
        php = (& $php -r 'echo PHP_VERSION;')
    })

    & (Join-Path $PSScriptRoot 'lab.ps1') -Action stop -Target $target -Variant $variant | Out-Null
}

$out = Join-Path $repo 'docs\pilot-results.json'
$results | ConvertTo-Json -Depth 5 | Set-Content -Path $out -Encoding UTF8
foreach ($r in $results) {
    Write-Output ("{0}: start={1}s reset={2}s rss={3}MB private={4}MB cpu={5}% dist={6}MB db={7}KB" -f `
        $r.target, $r.start_seconds, $r.reset_seconds, $r.rss_mb, $r.private_mb, $r.cpu_percent_one_core, $r.dist_mb, $r.db_kb)
}
Write-Output "written: $out"
