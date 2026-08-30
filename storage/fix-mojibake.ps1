$files = Get-ChildItem resources\views -Recurse -Filter *.blade.php
$count = 0
foreach ($f in $files) {
    $c = Get-Content $f.FullName -Raw
    $fixed = $c -replace 'â€"', '—' -replace 'â€˜', ''' -replace 'â€™', ''' -replace 'â€œ', '"' -replace 'â€\x9d', '"' -replace 'â€"', '—'
    if ($fixed -ne $c) {
        Set-Content $f.FullName -Value $fixed -Encoding UTF8 -NoNewline
        $count++
    }
}
Write-Output "fixed $count files"
