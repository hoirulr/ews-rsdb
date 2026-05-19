<?php

namespace Tests\Unit;

use App\Models\EwsAssessment;
use PHPUnit\Framework\TestCase;

class EwsAssessmentScoringTest extends TestCase
{
    /**
     * A normal NEWS2 profile should produce score zero and green zone.
     */
    public function test_normal_vital_signs_are_green(): void
    {
        $result = EwsAssessment::kalkulasiLengkap([
            'respirasi' => 16,
            'saturasi_o2' => 98,
            'oksigen_tambahan' => false,
            'suhu' => 36.8,
            'td_sistolik' => 120,
            'nadi' => 76,
            'kesadaran' => 'A',
        ]);

        $this->assertSame(0, $result['total_skor']);
        $this->assertSame('hijau', $result['zona']);
    }

    public function test_single_parameter_score_three_is_yellow_even_when_total_is_low(): void
    {
        $result = EwsAssessment::kalkulasiLengkap([
            'respirasi' => 8,
            'saturasi_o2' => 98,
            'oksigen_tambahan' => false,
            'suhu' => 36.8,
            'td_sistolik' => 120,
            'nadi' => 76,
            'kesadaran' => 'A',
        ]);

        $this->assertSame(3, $result['total_skor']);
        $this->assertSame('kuning', $result['zona']);
    }

    public function test_high_total_score_is_red(): void
    {
        $result = EwsAssessment::kalkulasiLengkap([
            'respirasi' => 28,
            'saturasi_o2' => 90,
            'oksigen_tambahan' => true,
            'suhu' => 39.2,
            'td_sistolik' => 84,
            'nadi' => 136,
            'kesadaran' => 'V',
        ]);

        $this->assertSame(19, $result['total_skor']);
        $this->assertSame('merah', $result['zona']);
    }
}
