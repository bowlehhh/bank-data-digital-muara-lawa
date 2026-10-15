<?php

return [
    // 50 MiB = 51,200 KiB. PHP's 50M shorthand uses the same binary unit.
    'max_size_kb' => (int) env('DOCUMENT_MAX_SIZE_KB', 50 * 1024),
    'mime_types' => [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/CDFV2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/CDFV2'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'csv' => ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ],
    'version_optional_types' => ['SURAT-MASUK', 'SURAT-KELUAR', 'BPK', 'INSPEKTORAT', 'BPKP'],
    'versions' => [...array_map(fn (int $version): string => sprintf('V%02d', $version), range(1, 99)), 'FINAL', 'FINAL_TTD'],
    'periods' => ['JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER', 'TRIWULAN-I', 'TRIWULAN-II', 'TRIWULAN-III', 'TRIWULAN-IV', 'SEMESTER-I', 'SEMESTER-II', 'TAHUNAN'],
];
