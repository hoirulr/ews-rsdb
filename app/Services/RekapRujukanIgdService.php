<?php

namespace App\Services;

use App\Models\Faskes;
use Illuminate\Database\Eloquent\Collection;

class RekapRujukanIgdService
{
    public function get(?string $tanggalMulai = null, ?string $tanggalSelesai = null): Collection
    {
        return Faskes::query()
            ->leftJoin('ews_assessments', function ($join) use ($tanggalMulai, $tanggalSelesai): void {
                $join->on('faskes.id', '=', 'ews_assessments.faskes_id')
                    ->whereNull('ews_assessments.deleted_at');

                if ($tanggalMulai !== null && $tanggalMulai !== '') {
                    $join->whereDate('ews_assessments.waktu_penilaian', '>=', $tanggalMulai);
                }

                if ($tanggalSelesai !== null && $tanggalSelesai !== '') {
                    $join->whereDate('ews_assessments.waktu_penilaian', '<=', $tanggalSelesai);
                }
            })
            ->whereIn('faskes.tipe', ['puskesmas', 'rs_perujuk'])
            ->select('faskes.id', 'faskes.nama_faskes', 'faskes.kode_faskes', 'faskes.tipe')
            ->selectRaw('COUNT(ews_assessments.id) as total_rujukan')
            ->selectRaw("SUM(CASE WHEN ews_assessments.zona = 'hijau' THEN 1 ELSE 0 END) as total_hijau")
            ->selectRaw("SUM(CASE WHEN ews_assessments.zona = 'kuning' THEN 1 ELSE 0 END) as total_kuning")
            ->selectRaw("SUM(CASE WHEN ews_assessments.zona = 'merah' THEN 1 ELSE 0 END) as total_merah")
            ->groupBy('faskes.id', 'faskes.nama_faskes', 'faskes.kode_faskes', 'faskes.tipe')
            ->orderByDesc('total_rujukan')
            ->orderBy('faskes.nama_faskes')
            ->get();
    }
}
