<?php

namespace Tests\Unit\Services;

use App\Services\DocumentFilenameService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DocumentFilenameServiceTest extends TestCase
{
    private function data(array $changes = []): array
    {
        return [...['year' => 2026, 'document_type' => 'RENJA', 'title' => 'Kecamatan Muara Lawa', 'version_code' => 'V01'], ...$changes];
    }

    #[DataProvider('validNames')]
    public function test_names_follow_sop(array $changes, string $extension, string $expected): void
    {
        $this->assertSame($expected, (new DocumentFilenameService)->generate($this->data($changes), $extension));
    }

    public static function validNames(): array
    {
        return [
            'normalisasi' => [['title' => '  kecamatan   muara__lawa  '], 'DOCX', '2026_RENJA_KECAMATAN-MUARA-LAWA_V01.docx'],
            'periode' => [['document_type' => 'SPJ', 'title' => 'Belanja ATK', 'period' => 'JANUARI', 'version_code' => 'FINAL'], 'pdf', '2026_SPJ_BELANJA-ATK_JANUARI_FINAL.pdf'],
            'tanggal' => [['document_type' => 'NOTULEN', 'title' => 'Rapat Evaluasi', 'document_date' => '2026-10-05', 'version_code' => 'FINAL'], 'pdf', '2026_NOTULEN_RAPAT-EVALUASI_20261005_FINAL.pdf'],
            'surat tanpa versi' => [['document_type' => 'SURAT_MASUK', 'title' => 'BPK', 'document_date' => '2026-10-05', 'version_code' => null], 'pdf', '2026_SURAT-MASUK_BPK_20261005.pdf'],
            'pemeriksaan tanpa versi' => [['document_type' => 'BPK', 'title' => 'Permintaan Pemeriksaan', 'document_date' => '2026-10-05', 'version_code' => null], 'pdf', '2026_BPK_PERMINTAAN-PEMERIKSAAN_20261005.pdf'],
            'V02' => [['version_code' => 'V02'], 'doc', '2026_RENJA_KECAMATAN-MUARA-LAWA_V02.doc'],
            'V10' => [['version_code' => 'V10'], 'xlsx', '2026_RENJA_KECAMATAN-MUARA-LAWA_V10.xlsx'],
            'V25' => [['version_code' => 'V25'], 'xls', '2026_RENJA_KECAMATAN-MUARA-LAWA_V25.xls'],
            'final ttd' => [['version_code' => 'FINAL_TTD'], 'pdf', '2026_RENJA_KECAMATAN-MUARA-LAWA_FINAL_TTD.pdf'],
            'hyphen' => [['title' => 'Belanja---ATK'], 'csv', '2026_RENJA_BELANJA-ATK_V01.csv'],
        ];
    }

    #[DataProvider('invalidNames')]
    public function test_invalid_names_are_rejected(array $changes, string $field): void
    {
        try {
            (new DocumentFilenameService)->generate($this->data($changes), 'pdf');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());

            return;
        }
        $this->fail('Nama yang tidak valid seharusnya ditolak.');
    }

    public static function invalidNames(): array
    {
        $cases = [];
        foreach (['BARU', 'SCAN001', 'FILE-LAMA', 'DOKUMEN', 'FIX', 'FIX-2', 'PALING-FINAL', 'FINAL-BARU', '../rahasia', 'A/B', 'A\\B', 'A:B', 'A*B', 'A?B', 'A"B', 'A<B', 'A>B', 'A|B', '___', '   ', "A\nB"] as $title) {
            $cases['title '.$title] = [['title' => $title], 'title'];
        }
        foreach (['V00', 'V1', 'V001', 'V-1', 'v01', 'FINAL-BARU', 'FINAL_TTD/../../', 'V1000000000', null] as $version) {
            $cases['versi '.($version ?? 'kosong')] = [['version_code' => $version], 'version_code'];
        }
        $cases['double timing'] = [['period' => 'JANUARI', 'document_date' => '2026-10-05'], 'period'];
        $cases['bad date'] = [['document_date' => '2026-02-30'], 'document_date'];
        $cases['other year date'] = [['document_date' => '2027-01-01'], 'document_date'];
        $cases['bad period'] = [['period' => '../RAHASIA'], 'period'];
        $cases['long title'] = [['title' => str_repeat('A', 250)], 'title'];
        $cases['bad year'] = [['year' => 2025], 'year'];

        return $cases;
    }
}
