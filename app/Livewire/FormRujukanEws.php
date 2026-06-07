<?php

namespace App\Livewire;

use App\Models\EwsAssessment;
use App\Services\EwsRujukanService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class FormRujukanEws extends Component
{
    public string $nama_pasien = '';

    public string $no_rm = '';

    public string $tanggal_lahir = '';

    public string $jenis_kelamin = 'L';

    public string $waktu_penilaian = '';

    public string $respirasi = '';

    public string $saturasi_o2 = '';

    public string $oksigen_tambahan = '0';

    public string $suhu = '';

    public string $td_sistolik = '';

    public string $nadi = '';

    public string $kesadaran = 'A';

    public int $total_skor = 0;

    public string $zona = '';

    /** @var array<string, int|string> */
    public array $skor_per_param = [];

    public string $catatan_rujukan = '';

    public string $tindakan_yang_diberikan = '';

    public bool $sukses = false;

    public string $pesanSukses = '';

    public string $pesanError = '';

    public string $zona_terkirim = '';

    public bool $sedangMengirim = false;

    public function mount(): void
    {
        $this->waktu_penilaian = now()->format('Y-m-d\TH:i');
    }

    public function updated(string $propertyName): void
    {
        $this->sukses = false;

        $vitalFields = [
            'respirasi',
            'saturasi_o2',
            'oksigen_tambahan',
            'suhu',
            'td_sistolik',
            'nadi',
            'kesadaran',
        ];

        if (in_array($propertyName, $vitalFields, true)) {
            $this->hitungSkor();
        }
    }

    public function hitungSkor(): void
    {
        if (! $this->semuaVitalTerisi()) {
            return;
        }

        $hasil = EwsAssessment::kalkulasiLengkap($this->vitalData());

        $this->total_skor = $hasil['total_skor'];
        $this->zona = $hasil['zona'];
        $this->skor_per_param = $hasil;
    }

    public function kirimRujukan(EwsRujukanService $service): void
    {
        $this->pesanError = '';
        $this->sukses = false;
        $this->sedangMengirim = true;

        $this->validate([
            'nama_pasien' => ['required', 'string', 'max:255'],
            'no_rm' => ['required', 'string', 'max:50'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'waktu_penilaian' => ['required', 'date'],
            'respirasi' => ['required', 'integer', 'min:1', 'max:60'],
            'saturasi_o2' => ['required', 'integer', 'min:70', 'max:100'],
            'oksigen_tambahan' => ['required', 'in:0,1'],
            'suhu' => ['required', 'numeric', 'min:30.0', 'max:45.0'],
            'td_sistolik' => ['required', 'integer', 'min:50', 'max:300'],
            'nadi' => ['required', 'integer', 'min:20', 'max:250'],
            'kesadaran' => ['required', 'in:A,V,P,U'],
        ]);

        try {
            $assessment = $service->kirimRujukan(
                data: [
                    'nama_pasien' => $this->nama_pasien,
                    'no_rm' => $this->no_rm,
                    'tanggal_lahir' => $this->tanggal_lahir,
                    'jenis_kelamin' => $this->jenis_kelamin,
                    'waktu_penilaian' => $this->waktu_penilaian,
                    'respirasi' => $this->respirasi,
                    'saturasi_o2' => $this->saturasi_o2,
                    'oksigen_tambahan' => $this->oksigen_tambahan,
                    'suhu' => $this->suhu,
                    'td_sistolik' => $this->td_sistolik,
                    'nadi' => $this->nadi,
                    'kesadaran' => $this->kesadaran,
                    'catatan_rujukan' => $this->catatan_rujukan,
                    'tindakan_yang_diberikan' => $this->tindakan_yang_diberikan,
                ],
                petugas: Auth::user(),
            );

            $zonaLabel = match ($assessment->zona) {
                'merah' => 'ZONA MERAH - Gawat Darurat',
                'kuning' => 'ZONA KUNING - Waspada',
                default => 'ZONA HIJAU - Normal',
            };

            $this->pesanSukses = sprintf(
                'Rujukan berhasil dikirim. Skor EWS: %d (%s). %s',
                $assessment->total_skor,
                $zonaLabel,
                in_array($assessment->zona, ['kuning', 'merah'], true) ? 'Alert sedang dikirim ke IGD RSUD.' : '',
            );

            $this->zona_terkirim = $assessment->zona;
            $this->sukses = true;
            $this->resetForm();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->pesanError = 'Gagal mengirim rujukan. Silakan coba lagi atau hubungi admin. ('.$exception->getMessage().')';

            Log::error('FormRujukanEws: gagal', [
                'user_id' => Auth::id(),
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        } finally {
            $this->sedangMengirim = false;
        }
    }

    public function render()
    {
        return view('livewire.form-rujukan-ews')
            ->layout('layouts.app', ['title' => 'Form Rujukan EWS']);
    }

    private function semuaVitalTerisi(): bool
    {
        return $this->respirasi !== ''
            && $this->saturasi_o2 !== ''
            && $this->suhu !== ''
            && $this->td_sistolik !== ''
            && $this->nadi !== '';
    }

    /**
     * @return array{respirasi:int,saturasi_o2:int,oksigen_tambahan:bool,suhu:float,td_sistolik:int,nadi:int,kesadaran:string}
     */
    private function vitalData(): array
    {
        return [
            'respirasi' => (int) $this->respirasi,
            'saturasi_o2' => (int) $this->saturasi_o2,
            'oksigen_tambahan' => $this->oksigen_tambahan === '1' || $this->oksigen_tambahan === 1 || $this->oksigen_tambahan === true,
            'suhu' => (float) $this->suhu,
            'td_sistolik' => (int) $this->td_sistolik,
            'nadi' => (int) $this->nadi,
            'kesadaran' => $this->kesadaran,
        ];
    }

    private function resetForm(): void
    {
        $this->nama_pasien = '';
        $this->no_rm = '';
        $this->tanggal_lahir = '';
        $this->jenis_kelamin = 'L';
        $this->waktu_penilaian = now()->format('Y-m-d\TH:i');
        $this->respirasi = '';
        $this->saturasi_o2 = '';
        $this->oksigen_tambahan = '0';
        $this->suhu = '';
        $this->td_sistolik = '';
        $this->nadi = '';
        $this->kesadaran = 'A';
        $this->total_skor = 0;
        $this->zona = '';
        $this->skor_per_param = [];
        $this->catatan_rujukan = '';
        $this->tindakan_yang_diberikan = '';
    }
}
