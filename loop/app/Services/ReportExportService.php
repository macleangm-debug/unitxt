<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ReportExportService
{
    /**
     * @param  list<string>  $headers
     * @param  Collection<int, array<int|string, mixed>>|iterable<int, array<int|string, mixed>>  $rows
     */
    public function download(string $basename, array $headers, iterable $rows, string $format = 'csv'): StreamedResponse
    {
        $file = $this->buildFile($basename, $headers, $rows, $format);

        return response()->streamDownload(function () use ($file) {
            echo $file['contents'];
        }, $file['filename'], [
            'Content-Type' => $file['content_type'],
        ]);
    }

    /**
     * @param  list<array{basename: string, headers: list<string>, rows: iterable<int, array<int|string, mixed>>}>  $datasets
     * @param  list<string>  $formats
     */
    public function downloadMany(array $datasets, array $formats): StreamedResponse|BinaryFileResponse
    {
        $formats = array_values(array_unique(array_map(
            fn ($f) => in_array(strtolower((string) $f), ['csv', 'tsv', 'json'], true) ? strtolower((string) $f) : 'csv',
            $formats
        )));

        if ($formats === []) {
            $formats = ['csv'];
        }

        $files = [];
        foreach ($datasets as $dataset) {
            foreach ($formats as $format) {
                $files[] = $this->buildFile(
                    $dataset['basename'],
                    $dataset['headers'],
                    $dataset['rows'],
                    $format
                );
            }
        }

        if (count($files) === 1) {
            $file = $files[0];

            return response()->streamDownload(function () use ($file) {
                echo $file['contents'];
            }, $file['filename'], [
                'Content-Type' => $file['content_type'],
            ]);
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'loop-reports-');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::OVERWRITE);

        $usedNames = [];
        foreach ($files as $file) {
            $name = $file['filename'];
            if (isset($usedNames[$name])) {
                $usedNames[$name]++;
                $name = pathinfo($file['filename'], PATHINFO_FILENAME)
                    .'-'.$usedNames[$file['filename']]
                    .'.'.pathinfo($file['filename'], PATHINFO_EXTENSION);
            } else {
                $usedNames[$name] = 0;
            }
            $zip->addFromString($name, $file['contents']);
        }
        $zip->close();

        $downloadName = 'loop-reports-'.now()->format('Y-m-d-His').'.zip';

        return response()->download($zipPath, $downloadName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @param  list<string>  $headers
     * @param  Collection<int, array<int|string, mixed>>|iterable<int, array<int|string, mixed>>  $rows
     * @return array{filename: string, content_type: string, contents: string}
     */
    public function buildFile(string $basename, array $headers, iterable $rows, string $format = 'csv'): array
    {
        $format = strtolower($format);
        if (! in_array($format, ['csv', 'tsv', 'json'], true)) {
            $format = 'csv';
        }

        $filename = $basename.'-'.now()->format('Y-m-d-His').'.'.$format;

        if ($format === 'json') {
            $payload = [];
            foreach ($rows as $row) {
                $values = array_values((array) $row);
                $payload[] = array_combine($headers, array_pad($values, count($headers), null));
            }

            return [
                'filename' => $filename,
                'content_type' => 'application/json; charset=UTF-8',
                'contents' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]',
            ];
        }

        $delimiter = $format === 'tsv' ? "\t" : ',';
        $buffer = fopen('php://temp', 'r+');
        // UTF-8 BOM helps Excel open CSV correctly
        fwrite($buffer, "\xEF\xBB\xBF");
        fputcsv($buffer, $headers, $delimiter);
        foreach ($rows as $row) {
            fputcsv($buffer, array_values((array) $row), $delimiter);
        }
        rewind($buffer);
        $contents = stream_get_contents($buffer) ?: '';
        fclose($buffer);

        return [
            'filename' => $filename,
            'content_type' => $format === 'tsv'
                ? 'text/tab-separated-values; charset=UTF-8'
                : 'text/csv; charset=UTF-8',
            'contents' => $contents,
        ];
    }
}
