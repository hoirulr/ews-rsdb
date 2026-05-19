<?php

namespace App\Exports;

use App\Models\EwsAssessment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EwsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?string $faskesId = null,
        private readonly ?string $zona = null,
        private readonly ?string $dari = null,
        private readonly ?string $sampai = null,
    ) {}

    public function query(): Builder
    {
        return EwsAssessment::with(['patient', 'faskes', 'petugas'])
            ->when($this->faskesId, fn (Builder $query): Builder => $query->where('faskes_id', $this->faskesId))
            ->when($this->zona, fn (Builder $query): Builder => $query->where('zona', $this->zona))
            ->when($this->dari, fn (Builder $query): Builder => $query->whereDate('waktu_penilaian', '>=', $this->dari))
            ->when($this->sampai, fn (Builder $query): Builder => $query->whereDate('waktu_penilaian', '<=', $this->sampai))
            ->orderByDesc('waktu_penilaian');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'No',
            'Nama Pasien',
            'No RM',
            'Faskes Asal',
            'Waktu Penilaian',
            'RR',
            'SpO2',
            'O2+',
            'Suhu',
            'TD Sistolik',
            'Nadi',
            'Kesadaran',
            'Total Skor',
            'Zona',
            'Status',
            'Petugas',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        static $number = 0;
        $number++;

        return [
            $number,
            $row->patient->nama_pasien,
            $row->patient->no_rm,
            $row->faskes->nama_faskes,
            $row->waktu_penilaian->format('d/m/Y H:i'),
            $row->respirasi,
            $row->saturasi_o2.'%',
            $row->oksigen_tambahan ? 'Ya' : 'Tidak',
            $row->suhu.' C',
            $row->td_sistolik,
            $row->nadi,
            $row->kesadaran,
            $row->total_skor,
            strtoupper($row->zona),
            $row->status,
            $row->petugas->name,
        ];
    }
}
