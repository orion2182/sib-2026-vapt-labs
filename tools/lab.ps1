#requires -Version 5.1
<#
.SYNOPSIS
Build, run, reset, and test one VAPT target on localhost.

.DESCRIPTION
One laptop runs one target. Every action is local and binds to 127.0.0.1.
Requires PHP 8.1+ (XAMPP PHP is detected automatically) and Git for the build ID.

.EXAMPLE
.\tools\lab.ps1 -Action build   -Target kampusmart -Variant vulnerable
.\tools\lab.ps1 -Action start   -Target kampusmart -Variant vulnerable
.\tools\lab.ps1 -Action health  -Target kampusmart
.\tools\lab.ps1 -Action test    -Target kampusmart -Variant vulnerable
.\tools\lab.ps1 -Action reset   -Target kampusmart
.\tools\lab.ps1 -Action stop    -Target kampusmart
#>
param(
    [ValidateSet('build', 'start', 'stop', 'reset', 'health', 'test', 'package', 'status')]
    [string]$Action = 'health',

    [ValidateSet('kampusmart', 'perpusdigital', 'eventpass')]
    [string]$Target = 'kampusmart',

    [ValidateSet('vulnerable', 'patched')]
    [string]$Variant = 'vulnerable',

    [int]$Port = 8081
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$isPackage = Test-Path (Join-Path $repoRoot 'build.json')
if ($isPackage) {
    # Running from an unpacked student package: app/, shared/, and build.json
    # already sit at the package root.
    $dist = $repoRoot
    $distName = Split-Path -Leaf $repoRoot
} else {
    $distName = "$Target-$Variant"
    $dist = Join-Path $repoRoot "dist\$distName"
}
$runDir = Join-Path $dist 'run'
$pidFile = Join-Path $runDir 'server.pid'
$baseUrl = "http://127.0.0.1:$Port"

function Get-PhpPath {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    foreach ($candidate in @('C:\xampp\php\php.exe', "$env:ProgramFiles\php\php.exe", "${env:ProgramFiles(x86)}\php\php.exe")) {
        if ($candidate -and (Test-Path $candidate)) { return $candidate }
    }
    throw 'PHP 8.1+ not found. Install PHP or XAMPP, or add php.exe to PATH. See docs/WINDOWS-SETUP.md.'
}

function Get-GitPath {
    $cmd = Get-Command git -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    foreach ($candidate in @('C:\Program Files\Git\cmd\git.exe', "$env:LOCALAPPDATA\Programs\Git\cmd\git.exe")) {
        if ($candidate -and (Test-Path $candidate)) { return $candidate }
    }
    return $null
}

function Get-SourceCommit {
    $git = Get-GitPath
    if (-not $git) { return 'no-git' }
    try {
        $commit = & $git -C $repoRoot rev-parse --short HEAD 2>$null
        if ($LASTEXITCODE -ne 0 -or -not $commit) { return 'unknown' }
        return ($commit | Out-String).Trim()
    } catch {
        return 'unknown'
    }
}

function Get-SourceDirty {
    $git = Get-GitPath
    if (-not $git) { return $false }
    try {
        $status = & $git -C $repoRoot status --porcelain 2>$null
        return [bool]($status | Where-Object { $_ -and $_ -notmatch '^\?\? (dist|run|packages|apps/.*/run)/' })
    } catch {
        return $false
    }
}

function Write-Manifest([string]$Root) {
    $lines = New-Object System.Collections.Generic.List[string]
    $files = Get-ChildItem $Root -Recurse -File |
        Where-Object { $_.FullName -notmatch '\\run\\' } |
        Sort-Object { $_.FullName.Substring($Root.Length).Replace('\', '/') }
    foreach ($file in $files) {
        $rel = $file.FullName.Substring($Root.Length).TrimStart('\').Replace('\', '/')
        $hash = (Get-FileHash $file.FullName -Algorithm SHA256).Hash.ToLowerInvariant()
        $lines.Add("$hash  $rel")
    }
    $manifest = Join-Path $Root 'manifest-sha256.txt'
    [System.IO.File]::WriteAllLines($manifest, $lines, (New-Object System.Text.UTF8Encoding($false)))
    return $manifest
}

function Invoke-Build {
    if ($isPackage) { throw 'This is a released package; it is already built. Use -Action start/reset/test.' }
    if (Get-RunningPid) { Invoke-Stop }
    if (-not (Test-Path $dist)) { New-Item -ItemType Directory -Path $dist -Force | Out-Null }
    Get-ChildItem $dist -Force | Remove-Item -Recurse -Force
    New-Item -ItemType Directory -Path $dist -Force | Out-Null

    $appSrc = Join-Path $repoRoot "apps\$Target"
    if (-not (Test-Path (Join-Path $appSrc 'public\index.php'))) {
        throw "Target source is not implemented yet: $appSrc"
    }

    New-Item -ItemType Directory -Path (Join-Path $dist 'app') -Force | Out-Null
    foreach ($extra in @('public', 'src', 'tests', 'svc', 'docs')) {
        if (Test-Path (Join-Path $appSrc $extra)) {
            Copy-Item (Join-Path $appSrc $extra) (Join-Path $dist "app\$extra") -Recurse -Force
        }
    }
    Copy-Item (Join-Path $repoRoot 'shared') (Join-Path $dist 'shared') -Recurse -Force
    Copy-Item (Join-Path $repoRoot 'tools') (Join-Path $dist 'tools') -Recurse -Force
    Copy-Item (Join-Path $appSrc 'seed') (Join-Path $dist 'seed') -Recurse -Force

    foreach ($doc in @('README-mahasiswa.md', 'student-card.md')) {
        $source = if ($doc -eq 'student-card.md') { Join-Path $appSrc 'docs\student-card.md' } else { Join-Path $appSrc $doc }
        if (Test-Path $source) { Copy-Item $source (Join-Path $dist $doc) -Force }
    }

    $build = [ordered]@{
        target             = $Target
        variant            = $Variant
        build_id           = "$Target-$Variant-" + (Get-SourceCommit)
        source_commit      = Get-SourceCommit
        source_dirty       = Get-SourceDirty
        harness_version    = '1.0.0'
        built_at_utc       = (Get-Date).ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ')
        php_version        = (& (Get-PhpPath) -r 'echo PHP_VERSION;')
    }
    $json = $build | ConvertTo-Json -Depth 4
    [System.IO.File]::WriteAllText((Join-Path $dist 'build.json'), $json + "`n", (New-Object System.Text.UTF8Encoding($false)))

    New-Item -ItemType Directory -Path $runDir -Force | Out-Null
    $manifest = Write-Manifest $dist
    Write-Output "Built $distName"
    Write-Output "  build_id: $($build.build_id)"
    Write-Output "  manifest: $manifest"
    Write-Output "  files:    $((Get-Content $manifest).Count)"
}

function Get-RunningPid {
    if (-not (Test-Path $pidFile)) { return $null }
    $raw = (Get-Content $pidFile -Raw).Trim()
    if (-not $raw) { return $null }
    $proc = Get-Process -Id ([int]$raw) -ErrorAction SilentlyContinue
    if ($proc) { return [int]$raw }
    return $null
}

function Wait-Healthy([int]$TimeoutSeconds = 20, [string]$ExpectedBuild = '') {
    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    while ((Get-Date) -lt $deadline) {
        try {
            $health = Invoke-RestMethod -Uri "$baseUrl/healthz" -TimeoutSec 3
            if ($health.status -eq 'ok') {
                if ($ExpectedBuild -ne '' -and -not (([string]$health.build_id).StartsWith($ExpectedBuild + '-'))) {
                    throw "Another lab instance answered at $baseUrl (build '$($health.build_id)'). Stop it or choose another port."
                }
                return $health
            }
        } catch {
            if ($_.Exception.Message -like 'Another lab instance*') { throw }
            Start-Sleep -Milliseconds 350
        }
    }
    throw "Target did not become healthy at $baseUrl within $TimeoutSeconds seconds. See run\server.err.log."
}

function Invoke-Start {
    if (-not (Test-Path (Join-Path $dist 'build.json'))) { Invoke-Build }
    $existing = Get-RunningPid
    if ($existing) {
        Write-Output "Already running (PID $existing) at $baseUrl"
        return
    }
    $listener = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
    if ($listener) {
        throw "Port $Port is already in use (PID $(($listener | Select-Object -First 1).OwningProcess)). Stop the other lab instance or choose another port."
    }
    if (Test-Path $pidFile) { Remove-Item $pidFile -Force }
    New-Item -ItemType Directory -Path $runDir -Force | Out-Null
    New-Item -ItemType Directory -Path (Join-Path $runDir 'sessions') -Force | Out-Null

    $php = Get-PhpPath
    $args = @(
        '-d', 'display_errors=0',
        '-d', 'log_errors=1',
        '-d', ('error_log=' + (Join-Path $runDir 'php-error.log')),
        '-S', "127.0.0.1:$Port",
        '-t', 'app/public',
        'app/public/router.php'
    )
    $process = Start-Process -FilePath $php -ArgumentList $args -WorkingDirectory $dist `
        -RedirectStandardOutput (Join-Path $runDir 'server.out.log') `
        -RedirectStandardError (Join-Path $runDir 'server.err.log') `
        -WindowStyle Hidden -PassThru
    Set-Content -Path $pidFile -Value $process.Id -NoNewline
    $health = Wait-Healthy -ExpectedBuild $distName
    Write-Output "Started $distName (PID $($process.Id)) at $baseUrl"
    Write-Output "  status:  $($health.status)"
    Write-Output "  variant: $($health.variant)"
    Write-Output "  build:   $($health.build_id)"
}

function Invoke-Stop {
    $running = Get-RunningPid
    if (-not $running) {
        Write-Output 'Not running.'
        if (Test-Path $pidFile) { Remove-Item $pidFile -Force }
        return
    }
    Stop-Process -Id $running -Force
    Remove-Item $pidFile -Force
    Write-Output "Stopped PID $running"
}

function Invoke-Reset {
    $wasRunning = [bool](Get-RunningPid)
    if ($wasRunning) { Invoke-Stop }
    foreach ($pattern in @('lab.sqlite', 'lab.sqlite-wal', 'lab.sqlite-shm', 'evidence.ndjson')) {
        $path = Join-Path $runDir $pattern
        if (Test-Path $path) { Remove-Item $path -Force }
    }
    $sessions = Join-Path $runDir 'sessions'
    if (Test-Path $sessions) { Get-ChildItem $sessions -File | Remove-Item -Force }
    $php = Get-PhpPath
    & $php (Join-Path $PSScriptRoot 'reset.php') $dist
    if ($LASTEXITCODE -ne 0) { throw 'Reset failed.' }
    Write-Output 'Reset complete: fixture, markers, and evidence log restored.'
    if ($wasRunning) { Invoke-Start }
}

function Invoke-Test {
    $running = Get-RunningPid
    if (-not $running) { Invoke-Start }
    $php = Get-PhpPath
    $testFile = Join-Path $dist 'app\tests\e2e.php'
    if (-not (Test-Path $testFile)) { throw "No test suite at $testFile" }
    & $php $testFile --base $baseUrl --variant $Variant
    if ($LASTEXITCODE -ne 0) { throw "Test suite failed with exit code $LASTEXITCODE" }
    Write-Output 'Tests passed.'
}

function Invoke-Package {
    Invoke-Build
    $packageDir = Join-Path $repoRoot 'packages'
    New-Item -ItemType Directory -Path $packageDir -Force | Out-Null
    $zip = Join-Path $packageDir "$distName.zip"
    if (Test-Path $zip) { Remove-Item $zip -Force }
    $staging = Join-Path $packageDir ".staging-$distName"
    if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
    New-Item -ItemType Directory -Path $staging -Force | Out-Null
    Get-ChildItem $dist -Force | Where-Object { $_.Name -ne 'run' } |
        Copy-Item -Destination $staging -Recurse -Force
    Compress-Archive -Path (Join-Path $staging '*') -DestinationPath $zip -CompressionLevel Optimal
    Remove-Item $staging -Recurse -Force
    $hash = (Get-FileHash $zip -Algorithm SHA256).Hash.ToLowerInvariant()
    $sidecar = "$zip.sha256"
    [System.IO.File]::WriteAllText($sidecar, "$hash  $distName.zip`n", (New-Object System.Text.UTF8Encoding($false)))
    Write-Output "Packaged $zip"
    Write-Output "  sha256: $hash"
}

switch ($Action) {
    'build'   { Invoke-Build }
    'start'   { Invoke-Start }
    'stop'    { Invoke-Stop }
    'reset'   { Invoke-Reset }
    'health'  { $health = Invoke-RestMethod -Uri "$baseUrl/healthz" -TimeoutSec 5; $health | ConvertTo-Json -Depth 5 }
    'test'    { Invoke-Test }
    'package' { Invoke-Package }
    'status'  {
        $running = Get-RunningPid
        if ($running) { Write-Output "Running (PID $running) at $baseUrl" } else { Write-Output 'Not running.' }
    }
}
