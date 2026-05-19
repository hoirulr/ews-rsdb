<?php

use App\Livewire\Actions\Logout;
use App\Livewire\Admin\ManajemenFaskes;
use App\Livewire\Admin\ManajemenUser;
use App\Http\Controllers\RekapRujukanIgdExportController;
use App\Livewire\DaftarRujukanRumahSakit;
use App\Livewire\DashboardIgd;
use App\Livewire\DetailRujukanRumahSakit;
use App\Livewire\FormRujukanEws;
use App\Livewire\MonitoringFaskes;
use App\Livewire\RekapRujukanIgd;
use App\Livewire\RiwayatEws;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::post('logout', function (Logout $logout) {
    $logout();

    return redirect('/');
})->middleware('auth')->name('logout');

Route::middleware(['auth'])->group(function (): void {
    Route::view('/profile', 'profile')->name('profile');

    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->hasRole('admin_sistem')) {
            return redirect()->route('admin.user');
        }

        if ($user->hasRole('admin_rsud')) {
            return redirect()->route('igd.dashboard');
        }

        if ($user->hasAnyRole(['puskesmas', 'rs_perujuk'])) {
            return redirect()->route('ews.form');
        }

        abort(403, 'User does not have the right roles.');
    })->name('dashboard');

    Route::middleware(['role:puskesmas|rs_perujuk|admin_sistem'])->group(function (): void {
        Route::get('/ews/form', FormRujukanEws::class)->name('ews.form');
        Route::get('/ews/riwayat', RiwayatEws::class)->name('ews.riwayat');
    });

    Route::middleware(['role:admin_rsud|admin_sistem'])->group(function (): void {
        Route::get('/rs/dashboard', fn () => redirect()->route('igd.dashboard'))->name('rs.dashboard');
        Route::get('/rs/daftar-rujukan', DaftarRujukanRumahSakit::class)->name('rs.daftar-rujukan');
        Route::get('/rs/rujukan/{assessment}', DetailRujukanRumahSakit::class)->name('rs.rujukan.detail');
        Route::get('/igd/dashboard', DashboardIgd::class)->name('igd.dashboard');
        Route::get('/igd/rekap-rujukan', RekapRujukanIgd::class)->name('igd.rekap-rujukan');
        Route::get('/igd/rekap-rujukan/export', RekapRujukanIgdExportController::class)->name('igd.rekap-rujukan.export');
        Route::get('/igd/monitoring', MonitoringFaskes::class)->name('igd.monitoring');
    });

    Route::middleware(['role:admin_sistem'])->group(function (): void {
        Route::get('/admin/user', ManajemenUser::class)->name('admin.user');
        Route::get('/admin/faskes', ManajemenFaskes::class)->name('admin.faskes');
    });
});

require __DIR__.'/auth.php';
