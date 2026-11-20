<#
.SYNOPSIS
    Deploy SmartCEMES to the Azure VM.

.DESCRIPTION
    The VM now has Node 22 (installed 2026-11-18 at /usr/local/bin), so assets
    are built there, against the code that was just pulled. If Node ever
    disappears, the script falls back to building here and uploading the
    compiled `public/build` (which is gitignored and therefore never travels
    with `git pull`).

.EXAMPLE
    .\scripts\deploy.ps1

.EXAMPLE
    .\scripts\deploy.ps1 -Migrate        # also run `migrate --force`

.EXAMPLE
    .\scripts\deploy.ps1 -SkipBuild      # code only, leave assets alone

.EXAMPLE
    .\scripts\deploy.ps1 -LocalBuild     # force the local-build fallback
#>
param(
    [string]$Key = "C:\Users\Nikko\Desktop\Proj\laravel-test-key.pem",
    [string]$Target = "azureuser@smartcemes.duckdns.org",
    [string]$RemotePath = "/var/www/laravel",
    [switch]$SkipBuild,
    [switch]$Migrate,
    [switch]$LocalBuild
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
    param([string]$Script, [switch]$AllowFailure)

    $normalised = $Script -replace "`r`n", "`n"
    $b64 = [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($normalised))

    # Native commands write progress to stderr, and with ErrorActionPreference
    # = Stop PowerShell 5.1 turns the merged 2>&1 records into terminating
    # errors ("NativeCommandError") — which is how a perfectly good `git pull`
    # failed the whole deploy. Scope the preference down for this call.
    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    try {
        $output = & ssh -o BatchMode=yes -o IdentitiesOnly=yes -i $Key $Target "echo $b64 | base64 -d | bash" 2>&1
        $script:LastRemoteExit = $LASTEXITCODE
    }
    finally {
        $ErrorActionPreference = $previous
    }

    foreach ($line in $output) {
        $text = if ($line -is [System.Management.Automation.ErrorRecord]) {
            $line.Exception.Message
        }
        else {
            [string]$line
        }

        Write-Host "    $text"
    }

    if ($script:LastRemoteExit -ne 0 -and -not $AllowFailure) {
        throw "Remote command failed with exit code $script:LastRemoteExit"
    }
}

function Write-Step($Message) {
    Write-Host ''
    Write-Host "==> $Message" -ForegroundColor Cyan
}

Write-Step 'Pulling code'
Invoke-Remote @"
set -e
cd $RemotePath
git pull --ff-only
git log --oneline -1
"@

# ------------------------------------------------------------ php dependencies
Write-Step 'Installing PHP dependencies'
Invoke-Remote @"
set -e
cd $RemotePath
if [ ! -f vendor/composer/installed.json ] || [ composer.lock -nt vendor/composer/installed.json ]; then
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
fi
"@

# --------------------------------------------------------------- build assets
if ($SkipBuild) {
    Write-Step 'Skipping asset build (-SkipBuild)'
}
else {
    $hasNode = -not $LocalBuild

    if ($hasNode) {
        Write-Step 'Checking for Node on the VM'
        Invoke-Remote -Script 'command -v node || exit 1' -AllowFailure
        $hasNode = ($script:LastRemoteExit -eq 0)

        if (-not $hasNode) {
            Write-Host '    node not found - falling back to a local build' -ForegroundColor Yellow
        }
    }

    if ($hasNode) {
        Write-Step 'Building assets on the VM'
        Invoke-Remote @"
set -e
cd $RemotePath
if [ ! -d node_modules ] || [ package-lock.json -nt node_modules/.package-lock.json ]; then
    echo "installing dependencies..."
    npm ci --no-audit --no-fund
fi
npm run build
"@
    }
    else {
        Write-Step 'Building assets locally (the VM has no Node)'
        Push-Location $repo
        try {
            & npm.cmd run build
            if ($LASTEXITCODE -ne 0) { throw 'vite build failed' }
        }
        finally {
            Pop-Location
        }

        Write-Step 'Uploading compiled assets'
        $tarball = Join-Path $env:TEMP 'smartcemes-build.tar.gz'
        Remove-Item $tarball -ErrorAction SilentlyContinue
        & tar -czf $tarball -C (Join-Path $repo 'public') build
        if ($LASTEXITCODE -ne 0) { throw 'tar failed' }

        & scp -o BatchMode=yes -o IdentitiesOnly=yes -i $Key $tarball "${Target}:/tmp/smartcemes-build.tar.gz"
        if ($LASTEXITCODE -ne 0) { throw 'scp failed' }

        Invoke-Remote @"
set -e
cd $RemotePath
tar -xzf /tmp/smartcemes-build.tar.gz -C public
chmod -R a+rX public/build
rm -f /tmp/smartcemes-build.tar.gz
"@
    }
}

# -------------------------------------------------------------------- migrate
if ($Migrate) {
    Write-Step 'Running migrations'
    Invoke-Remote "set -e`ncd $RemotePath`nphp artisan migrate --force"
}

# ---------------------------------------------------------------------- clear
Write-Step 'Clearing caches'
Invoke-Remote "set -e`ncd $RemotePath`nphp artisan optimize:clear"

Write-Step 'Done'
Write-Host ''
Write-Host 'Mint a share link ON THE VM (tokens are per-database):' -ForegroundColor Yellow
Write-Host "  ssh -i `"$Key`" $Target `"cd $RemotePath && php artisan assessment:link 9 --days=5`""
