<?php

namespace Tests\Feature;

use App\FolderType;
use App\Models\Folder;
use Database\Seeders\FolderSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Tests\TestCase;

class FolderSeederTest extends TestCase
{
    public function test_official_2026_structure_has_exact_groups_children_and_temporary_archive(): void
    {
        $this->seed(FolderSeeder::class);

        $this->assertDatabaseCount('folders', 48);
        $this->assertSame(5, Folder::query()->whereNull('year')->count());
        $this->assertSame(43, Folder::query()->where('year', 2026)->count());
        $expected = [
            '00_PEDOMAN_DAN_SOP' => ['01_SOP', '02_STANDAR_STRUKTUR_FOLDER', '03_STANDAR_PENAMAAN_FILE', '04_DAFTAR_INVENTARIS'],
            '01_PERENCANAAN_DAN_PROGRAM' => ['01_RENSTRA', '02_RENJA', '03_RKT', '04_PROGRAM_DAN_KEGIATAN', '05_DATA_PENDUKUNG_PERENCANAAN'],
            '02_PENGANGGARAN' => ['01_RKA', '02_DPA', '03_DPPA', '04_DATA_PENDUKUNG_ANGGARAN'],
            '03_PENATAUSAHAAN_KEUANGAN' => ['01_SPP', '02_SPM', '03_SP2D', '04_SPJ', '05_BUKU_KAS', '06_PAJAK', '07_BUKTI_PENDUKUNG'],
            '04_LAPORAN_KEUANGAN' => ['01_LAPORAN_BULANAN', '02_LAPORAN_TRIWULAN', '03_LAPORAN_SEMESTER', '04_LAPORAN_TAHUNAN', '05_REKONSILIASI'],
            '05_AKUNTABILITAS_DAN_PELAPORAN' => ['01_SAKIP', '02_LKJIP', '03_LPPD', '04_MONITORING_DAN_EVALUASI', '05_LAPORAN_LAINNYA'],
            '06_PEMERIKSAAN_DAN_TINDAK_LANJUT' => ['01_BPK', '02_INSPEKTORAT', '03_BPKP', '04_TINDAK_LANJUT'],
            '07_DATA_PENDUKUNG' => ['01_SURAT_MASUK', '02_SURAT_KELUAR', '03_NOTULEN', '04_DOKUMENTASI', '05_DATA_LAINNYA'],
            '99_ARSIP_SEMENTARA' => [],
        ];
        $roots = Folder::query()->whereNull('parent_id')->with('children')->orderBy('code')->get();
        $this->assertSame(array_keys($expected), $roots->pluck('code')->all());
        foreach ($roots as $folder) {
            $this->assertSame($expected[$folder->code], $folder->children->pluck('code')->all());
            foreach ($folder->children as $child) {
                $this->assertSame($folder->year, $child->year);
                $this->assertTrue($child->parent->is($folder));
                $this->assertSame(FolderType::DocumentType, $child->folder_type);
            }
        }
        $archive = Folder::query()->where('is_temporary', true)->sole();
        $this->assertSame('99_ARSIP_SEMENTARA', $archive->code);
        $this->assertSame(2026, $archive->year);
        $this->assertSame(FolderType::Temporary, $archive->folder_type);
        $this->assertSame(48, Folder::query()->where('is_official', true)->where('is_active', true)->count());
    }

    public function test_reseeding_preserves_folder_ids_and_existing_changes(): void
    {
        $this->seed(FolderSeeder::class);
        $folder = Folder::query()->where('code', '01_SOP')->sole();
        $folder->update(['description' => 'Catatan pengguna', 'is_active' => false]);
        $ids = Folder::query()->orderBy('id')->pluck('id')->all();

        $this->seed(FolderSeeder::class);

        $this->assertSame($ids, Folder::query()->orderBy('id')->pluck('id')->all());
        $this->assertSame('Catatan pengguna', $folder->fresh()->description);
        $this->assertFalse($folder->fresh()->is_active);
    }

    public function test_duplicate_global_root_is_rejected_even_with_null_scope(): void
    {
        $folder = Folder::factory()->create(['year' => null]);

        $this->expectException(UniqueConstraintViolationException::class);
        Folder::factory()->create(['year' => null, 'code' => $folder->code]);
    }
}
