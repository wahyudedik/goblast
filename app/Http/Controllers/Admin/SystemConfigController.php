<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemConfigRequest;
use App\Models\SystemConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SystemConfigController extends Controller
{
    /**
     * Display a listing of system configurations.
     */
    public function index(): View
    {
        $configs = SystemConfig::orderBy('key')->get();

        return view('admin.configs.index', [
            'configs' => $configs,
        ]);
    }

    /**
     * Show the form for editing the specified system configuration.
     */
    public function edit(SystemConfig $systemConfig): View
    {
        return view('admin.configs.edit', [
            'config' => $systemConfig,
        ]);
    }

    /**
     * Update the specified system configuration.
     */
    public function update(UpdateSystemConfigRequest $request, SystemConfig $systemConfig): RedirectResponse
    {
        $systemConfig->update([
            'value' => $request->validated()['value'],
            'updated_by' => $request->user()->id,
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.configs.index')
            ->with('success', "Konfigurasi '{$systemConfig->key}' berhasil diperbarui.");
    }
}
