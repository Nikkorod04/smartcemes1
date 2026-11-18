<#
.SYNOPSIS
    Deploy SmartCEMES to the Azure VM.

.DESCRIPTION
    The VM has NO Node, and `public/build` is gitignored, so compiled assets can
    never be produced there. This script builds them here, ships them, and then
    runs the normal Laravel deploy steps over SSH.

    Order matters: assets are uploaded BEFORE `git pull` is verified so a
    half-deployed state still has a matching manifest.

.EXAMPLE
    .\scripts\deploy.ps1

.EXAMPLE
    .\scripts\deploy.ps1 -SkipBuild      # assets already built, just ship them

.EXAMPLE
    .\scripts\deploy.ps1 -Migrate        # also run `migrate --force`
#>
param(
    [string]$Key = "C:\Users\Nikko\Desktop\Proj\laravel-test-key.pem",
    [string]$Target = "azureuser@smartcemes.duckdns.org",
    [string]$RemotePath = "/var/www/laravel",
    [switch]$SkipBuild,
    [switch]$Migrate
)

$ErrorActionPreference = 'Stop'
$repo = Split-Path -Parent $PSScriptRoot

# --- CRLF trap -------------------------------------------------------------
# PowerShell 5.1 rewrites LF to CRLF when piping a string to a native process,
# so `$script | ssh host 'bash -s'` gives every remote line a trailing \r. Bash
# then reads it as part of the last argument on the line ("'1?': not a
# non-negative integer" from `git log -1`). Base64 keeps the whole script on one
# line, so there is no stdin encoding to mangle.
function Invoke-Remote {
    param([string]$Script)

    $normalised = $Script -replace "`r`n", "`n"
    $b64 = [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($normalised))

    & ssh -o BatchMode=yes -o IdentitiesOnly=yes -i $Key $Target "echo $b64 | base64 -d | bash"
    if ($LASTEXITCODE -ne 0) {
        throw "Remote command failed with exit code $LASTEXITCODE"
    }
}

function Write-Step($Message) {
    Write-Host ''
    Write-Host "==> $Message" -ForegroundColor Cyan
}

Push-Location $repo
try {
    # ---------------------------------------------------------------- 1. build
    if (-not $SkipBuild) {
        Write-Step 'Building assets locally (the VM cannot do this)'
        & npm.cmd run build
        if ($LASTEXITCODE -ne 0) { throw 'vite build failed' }
    } else {
        Write-Step 'Skipping build (-SkipBuild)'
    }

    $manifest = Join-Path $repo 'public\build\manifest.json'
    if (-not (Test-Path $manifest)) {
        throw "No $manifest - run without -SkipBuild first."
    }

    # -------------------------------------------------------------- 2. package
    Write-Step 'Packaging public/build'
    $tarball = Join-Path $env:TEMP 'smartcemes-build.tar.gz'
    Remove-Item $tarball -ErrorAction SilentlyContinue
    & tar -czf $tarball -C (Join-Path $repo 'public') build
    if ($LASTEXITCODE -ne 0) { throw 'tar failed' }

    # --------------------------------------------------------------- 3. upload
    Write-Step "Uploading assets to $Target"
    & scp -o BatchMode=yes -o IdentitiesOnly=yes -i $Key $tarball "${Target}:/tmp/smartcemes-build.tar.gz"
    if ($LASTEXITCODE -ne 0) { throw 'scp failed' }

    # -------------------------------------------------------------- 4. release
    Write-Step 'Pulling code and publishing assets'
    Invoke-Remote @"
set -e
cd $RemotePath
git pull --ff-only
tar -xzf /tmp/smartcemes-build.tar.gz -C public
chmod -R a+rX public/build
rm -f /tmp/smartcemes-build.tar.gz
git log --oneline -1
"@

    # -------------------------------------------------------------- 5. migrate
    if ($Migrate) {
        Write-Step 'Running migrations'
        Invoke-Remote "set -e`ncd $RemotePath`nphp artisan migrate --force"
    }

    # ---------------------------------------------------------------- 6. clear
    Write-Step 'Clearing caches'
    Invoke-Remote "set -e`ncd $RemotePath`nphp artisan optimize:clear"

    Write-Step 'Done'
    Write-Host "Mint a share link on the VM (not locally):" -ForegroundColor Yellow
    Write-Host "  ssh -i `"$Key`" $Target `"cd $RemotePath && php artisan assessment:link 9 --days=5`""
}
finally {
    Pop-Location
}
