<#
  scripts/init_workspace.ps1
  - Creates C:\Development\tiredprod
  - Optional: clones a remote repo if REMOTE_URL provided
  - Usage: .\init_workspace.ps1 -RemoteUrl "git@github.com:Saulgoodmantm/tired.git"
#>

param(
  [string]$RemoteUrl = ""
)

$root = "C:\Development\tiredprod"
Write-Host "Creating workspace at $root ..."
New-Item -ItemType Directory -Force -Path $root | Out-Null

# create basic folders
$folders = @("Requests", "Requests\All Past Prompts", "Requests\Features.md", "Website", "agents", "instructions", "scripts", "skeleton")
foreach ($f in $folders) {
  $path = Join-Path $root $f
  New-Item -ItemType Directory -Force -Path $path -ErrorAction SilentlyContinue | Out-Null
}

# create placeholder Features.md
$featuresFile = Join-Path $root "Requests\Features.md"
if (-not (Test-Path $featuresFile)) {
  "## Features - list and numbering" | Out-File -FilePath $featuresFile -Encoding utf8
}

# optional clone
if ($RemoteUrl -ne "") {
  Write-Host "Cloning remote repo $RemoteUrl into $root\Website ..."
  git clone $RemoteUrl (Join-Path $root "Website")
} else {
  Write-Host "No remote URL provided. Please clone your repo manually into $root\Website"
}

Write-Host "Workspace initialized. Open Copilot IDE and load $root as workspace."