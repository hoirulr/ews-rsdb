<?php

namespace Database\Seeders;

use App\Models\Faskes;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FaskesUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = 'Rujukan@2026*/';

        $rsud = Faskes::firstOrCreate(
            ['kode_faskes' => 'RSUD-DB-001'],
            [
                'nama_faskes' => 'RSUD Depati Bahrin',
                'tipe' => 'rsud',
                'alamat' => 'Jl. Depati Bahrin, Bangka Barat',
                'no_telp' => '(0716) 123456',
            ],
        );

        $adminSistem = User::updateOrCreate(
            ['email' => 'admin@rsuddepatibahrin.id'],
            [
                'name' => 'Administrator Sistem',
                'password' => Hash::make('Rsuddb*&2026'),
                'faskes_id' => $rsud->id,
                'email_verified_at' => now(),
            ],
        );
        $adminSistem->assignRole('admin_sistem');

        $dokterIgd = User::updateOrCreate(
            ['email' => 'igd@rsuddepatibahrin.id'],
            [
                'name' => 'Dokter IGD RSUD',
                'password' => Hash::make('Rsuddb*&2026'),
                'faskes_id' => $rsud->id,
                'email_verified_at' => now(),
            ],
        );
        $dokterIgd->assignRole('admin_rsud');

        $faskesRujukan = [
            ['kode_faskes' => 'PKM-SUN-001', 'nama_faskes' => 'Puskesmas Sungailiat', 'tipe' => 'puskesmas', 'email' => 'puskesmas.sungailiat@rsuddepatibahrin.id'],
            ['kode_faskes' => 'PKM-KEN-001', 'nama_faskes' => 'Puskesmas Kenanga', 'tipe' => 'puskesmas', 'email' => 'puskesmas.kenanga@rsuddepatibahrin.id'],
            ['kode_faskes' => 'PKM-SIB-001', 'nama_faskes' => 'Puskesmas Sinar Baru', 'tipe' => 'puskesmas', 'email' => 'puskesmas.sinarbaru@rsuddepatibahrin.id'],
            ['kode_faskes' => 'PKM-PEM-001', 'nama_faskes' => 'Puskesmas Pemali', 'tipe' => 'puskesmas', 'email' => 'puskesmas.pemali@rsuddepatibahrin.id'],
            ['kode_faskes' => 'PKM-MER-001', 'nama_faskes' => 'Puskesmas Merawang', 'tipe' => 'puskesmas', 'email' => 'puskesmas.merawang@rsuddepatibahrin.id'],
            ['kode_faskes' => 'PKM-BTR-001', 'nama_faskes' => 'Puskesmas Baturusa', 'tipe' => 'puskesmas', 'email' => 'puskesmas.baturusa@rsuddepatibahrin.id'],
            ['kode_faskes' => 'PKM-BB-001', 'nama_faskes' => 'Puskesmas Belinyu', 'tipe' => 'puskesmas', 'email' => 'puskesmas.belinyu@rsuddepatibahrin.id'],
            ['kode_faskes' => 'PKM-RIS-001', 'nama_faskes' => 'Puskesmas Riau Silip', 'tipe' => 'puskesmas', 'email' => 'puskesmas.riausilip@rsuddepatibahrin.id'],
            ['kode_faskes' => 'PKM-PUB-001', 'nama_faskes' => 'Puskesmas Puding Besar', 'tipe' => 'puskesmas', 'email' => 'puskesmas.pudingbesar@rsuddepatibahrin.id'],
            ['kode_faskes' => 'PKM-BAK-001', 'nama_faskes' => 'Puskesmas Bakam', 'tipe' => 'puskesmas', 'email' => 'puskesmas.bakam@rsuddepatibahrin.id'],
            ['kode_faskes' => 'PKM-PET-001', 'nama_faskes' => 'Puskesmas Petaling', 'tipe' => 'puskesmas', 'email' => 'puskesmas.petaling@rsuddepatibahrin.id'],
            ['kode_faskes' => 'PKM-PEN-001', 'nama_faskes' => 'Puskesmas Penagan', 'tipe' => 'puskesmas', 'email' => 'puskesmas.penagan@rsuddepatibahrin.id'],
            ['kode_faskes' => 'RS-BT-001', 'nama_faskes' => 'RS Bakti Timah', 'tipe' => 'rs_perujuk', 'email' => 'rs.baktitimah@rsuddepatibahrin.id'],
            ['kode_faskes' => 'RS-MS-001', 'nama_faskes' => 'RS Medika Stannia', 'tipe' => 'rs_perujuk', 'email' => 'rs.medikastannia@rsuddepatibahrin.id'],
            ['kode_faskes' => 'RSIA-ARS-001', 'nama_faskes' => 'RSIA Arsani', 'tipe' => 'rs_perujuk', 'email' => 'rsia.arsani@rsuddepatibahrin.id'],
            ['kode_faskes' => 'PSC-BANGKA-001', 'nama_faskes' => 'PSC Kabupaten Bangka', 'tipe' => 'rs_perujuk', 'email' => 'psc.kabupatenbangka@rsuddepatibahrin.id'],
        ];

        foreach ($faskesRujukan as $data) {
            $faskes = Faskes::updateOrCreate(
                ['kode_faskes' => $data['kode_faskes']],
                [
                    'nama_faskes' => $data['nama_faskes'],
                    'tipe' => $data['tipe'],
                ],
            );

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['nama_faskes'],
                    'password' => Hash::make($defaultPassword),
                    'faskes_id' => $faskes->id,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            $user->assignRole($data['tipe'] === 'puskesmas' ? 'puskesmas' : 'rs_perujuk');
        }
    }
}
