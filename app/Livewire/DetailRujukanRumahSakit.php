<?php

namespace App\Livewire;

use App\Models\EwsAssessment;
use App\Services\EwsRujukanService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class DetailRujukanRumahSakit extends Component
{
    public EwsAssessment $assessment;

    public string $feedbackHasil = '';

    public string $feedbackCatatan = '';

    public bool $sukses = false;

    public string $pesanSukses = '';

    public string $pesanError = '';

    public function mount(EwsAssessment $assessment): void
    {
        $this->assessment = $assessment->load(['patient.faskesAsal', 'faskes', 'petugas']);
        $this->feedbackHasil = $this->assessment->feedback_hasil ?? '';
        $this->feedbackCatatan = $this->assessment->feedback_catatan ?? '';
    }

    public function simpanFeedback(EwsRujukanService $service): void
    {
        $this->pesanError = '';
        $this->sukses = false;

        $this->validate([
            'feedbackHasil' => [
                'required',
                Rule::in(['meninggal', 'icu_lebih_24_jam', 'rawat_inap_lebih_24_jam']),
            ],
            'feedbackCatatan' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->assessment = $service->simpanFeedback(
                assessmentId: $this->assessment->id,
                petugasRs: Auth::user(),
                feedbackHasil: $this->feedbackHasil,
                feedbackCatatan: $this->feedbackCatatan !== '' ? $this->feedbackCatatan : null,
            )->load(['patient.faskesAsal', 'faskes', 'petugas', 'feedbackOleh']);

            $this->pesanSukses = 'Feedback berhasil disimpan dan rujukan ditandai selesai.';
            $this->sukses = true;
        } catch (Throwable $exception) {
            $this->pesanError = 'Gagal menyimpan feedback: '.$exception->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.detail-rujukan-rumah-sakit')
            ->layout('layouts.app', ['title' => 'Detail Rujukan Rumah Sakit']);
    }
}
