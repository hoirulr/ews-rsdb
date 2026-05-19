<?php

namespace App\Http\Controllers;

use App\Services\RekapRujukanIgdService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapRujukanIgdExportController extends Controller
{
    public function __invoke(Request $request, RekapRujukanIgdService $service): StreamedResponse
    {
        $validated = $request->validate([
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $tanggalMulai = $validated['tanggal_mulai'] ?? null;
        $tanggalSelesai = $validated['tanggal_selesai'] ?? null;
        $rekap = $service->get($tanggalMulai, $tanggalSelesai);
        $filename = 'rekap-rujukan-igd-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rekap, $tanggalMulai, $tanggalSelesai): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Rekap Rujukan IGD RSUD Depati Bahrin'], ';');
            fputcsv($handle, ['Periode', ($tanggalMulai ?: '-') .' s/d '.($tanggalSelesai ?: '-')], ';');
            fputcsv($handle, [], ';');
            fputcsv($handle, ['No', 'Kode Faskes', 'Nama Faskes', 'Tipe', 'Zona Hijau', 'Zona Kuning', 'Zona Merah', 'Total Rujukan'], ';');

            foreach ($rekap as $index => $item) {
                fputcsv($handle, [
                    $index + 1,
                    $item->kode_faskes,
                    $item->nama_faskes,
                    $item->tipe_label,
                    (int) $item->total_hijau,
                    (int) $item->total_kuning,
                    (int) $item->total_merah,
                    (int) $item->total_rujukan,
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, [
                '',
                '',
                'TOTAL',
                '',
                (int) $rekap->sum('total_hijau'),
                (int) $rekap->sum('total_kuning'),
                (int) $rekap->sum('total_merah'),
                (int) $rekap->sum('total_rujukan'),
            ], ';');

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
