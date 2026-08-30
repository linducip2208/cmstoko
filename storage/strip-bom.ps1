$paths = @('app', 'database', 'config', 'tests', 'routes', 'resources', 'docs')
$files = Get-ChildItem $paths -Recurse -File -Include *.php, *.blade.php, *.md, *.json | Where-Object { $_.FullName -notmatch '\\vendor\\' }
$fixed = 0
foreach ($f in $files) {
    $bytes = [System.IO.File]::ReadAllBytes($f.FullName)
    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        [System.IO.File]::WriteAllBytes($f.FullName, $bytes[3..($bytes.Length - 1)])
        $fixed++
    }
}
Write-Output "BOM stripped from $fixed files"
