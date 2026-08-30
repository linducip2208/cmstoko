<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class Csv
{
    /**
     * Stream a CSV download. Rows are consumed lazily (cursor/chunk safe)
     * so large exports never blow the memory limit.
     *
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function streamDownload(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows): void {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, $header);

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($value) => is_bool($value) ? ($value ? '1' : '0') : $value, array_values($row)));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, must-revalidate',
        ]);
    }
}
