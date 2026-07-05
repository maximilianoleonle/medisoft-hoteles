<#
.SYNOPSIS
Runs PHP syntax lint through Docker, without requiring PHP installed on Windows.

.EXAMPLE
powershell -ExecutionPolicy Bypass -File tools/lint-php.ps1

.EXAMPLE
powershell -ExecutionPolicy Bypass -File tools/lint-php.ps1 -Changed

.EXAMPLE
powershell -ExecutionPolicy Bypass -File tools/lint-php.ps1 src/app/services/NotificacionService.php

.EXAMPLE
powershell -ExecutionPolicy Bypass -File tools/lint-php.ps1 -Image php:8.2-cli src/app
#>
param(
    [Parameter(Position = 0, ValueFromRemainingArguments = $true)]
    [string[]]$Targets = @(),

    [switch]$Changed,

    [switch]$All,

    [string]$Image = 'php:8.3-cli'
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$repoRoot = (Resolve-Path -LiteralPath (Join-Path $scriptDir '..')).Path
Push-Location $repoRoot

try {
    function Convert-ToRepoPath {
        param([string]$FullPath)

        $resolved = (Resolve-Path -LiteralPath $FullPath).Path
        if (-not $resolved.StartsWith($repoRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
            throw "Target is outside the repository: $resolved"
        }

        $relative = $resolved.Substring($repoRoot.Length).TrimStart('\', '/')
        return ($relative -replace '\\', '/')
    }

    function Add-TargetPhpFiles {
        param([string]$Target)

        $items = @()

        if (Test-Path -LiteralPath $Target -PathType Container) {
            $items = Get-ChildItem -LiteralPath $Target -Recurse -File -Filter '*.php' |
                Where-Object { $_.FullName -notmatch '\\(\.git|vendor|node_modules)\\' }
        } elseif (Test-Path -LiteralPath $Target -PathType Leaf) {
            $items = @(Get-Item -LiteralPath $Target)
        } else {
            $items = @(Get-ChildItem -Path $Target -File -ErrorAction SilentlyContinue)
        }

        foreach ($item in $items) {
            if ($item.Extension -eq '.php') {
                Convert-ToRepoPath $item.FullName
            }
        }
    }

    function Get-GitPhpFiles {
        param([switch]$OnlyChanged)

        $insideGit = $false
        try {
            $insideGit = ((& git rev-parse --is-inside-work-tree 2>$null) -eq 'true')
        } catch {
            $insideGit = $false
        }

        if (-not $insideGit) {
            return @(
                Get-ChildItem -LiteralPath $repoRoot -Recurse -File -Filter '*.php' |
                    Where-Object { $_.FullName -notmatch '\\(\.git|vendor|node_modules|dist|exports|backups)\\' } |
                    ForEach-Object { Convert-ToRepoPath $_.FullName }
            )
        }

        if ($OnlyChanged) {
            $files = @()
            $files += & git diff --name-only --diff-filter=ACMRTUXB -- '*.php'
            $files += & git diff --cached --name-only --diff-filter=ACMRTUXB -- '*.php'
            $files += & git ls-files --others --exclude-standard -- '*.php'
            return $files
        }

        $tracked = & git ls-files -- '*.php'
        $untracked = & git ls-files --others --exclude-standard -- '*.php'
        return @($tracked + $untracked)
    }

    $phpFiles = @()

    if ($Targets.Count -gt 0) {
        foreach ($target in $Targets) {
            $phpFiles += Add-TargetPhpFiles $target
        }
    } elseif ($Changed) {
        $phpFiles = Get-GitPhpFiles -OnlyChanged
    } else {
        $phpFiles = Get-GitPhpFiles
    }

    $phpFiles = @(
        $phpFiles |
            Where-Object { $_ -and $_.Trim() -ne '' } |
            ForEach-Object { ($_ -replace '\\', '/').TrimStart('./') } |
            Sort-Object -Unique
    )

    if ($phpFiles.Count -eq 0) {
        Write-Host 'No PHP files found to lint.' -ForegroundColor Yellow
        exit 0
    }

    $docker = Get-Command docker -ErrorAction SilentlyContinue
    if (-not $docker) {
        throw 'Docker was not found in PATH. Install Docker Desktop or add docker.exe to PATH.'
    }

    $tempFile = [System.IO.Path]::GetTempFileName()
    try {
        [System.IO.File]::WriteAllText($tempFile, (($phpFiles -join "`n") + "`n"), [System.Text.Encoding]::ASCII)

        Write-Host "Linting $($phpFiles.Count) PHP file(s) with $Image..." -ForegroundColor Cyan

        $repoMount = "${repoRoot}:/app"
        $fileMount = "${tempFile}:/tmp/php-lint-files.txt:ro"
        $containerScript = "failed=0`nwhile IFS= read -r file; do`n  [ -z `"`$file`" ] && continue`n  php -l `"`$file`" || failed=1`ndone < /tmp/php-lint-files.txt`nexit `$failed"

        & docker run --rm -v $repoMount -v $fileMount -w /app $Image sh -lc $containerScript
        if ($LASTEXITCODE -ne 0) {
            exit $LASTEXITCODE
        }
    } finally {
        Remove-Item -LiteralPath $tempFile -Force -ErrorAction SilentlyContinue
    }
} finally {
    Pop-Location
}
