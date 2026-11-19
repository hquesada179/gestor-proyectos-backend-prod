# Script asistente para demostración del 3.er Canal (API Pública y CLI)
param (
    [string]$Action = "status",
    [int]$Project = 0,
    [string]$Title = ""
)

Write-Host "Ejecutando cliente CLI de Scrumter (3.er Canal)..." -ForegroundColor Cyan

$params = @("artisan", "scrumter:cli", "--action=$Action")
if ($Project -gt 0) { $params += "--project=$Project" }
if ($Title -ne "") { $params += "--title=$Title" }

php @params
