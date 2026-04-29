<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GatewayInstance;
use App\Services\Contracts\BaileysGatewayClientInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GatewayController extends Controller
{
    /**
     * Display a listing of gateway instances.
     */
    public function index(): View
    {
        $gateways = GatewayInstance::orderBy('name')
            ->paginate(15);

        return view('admin.gateways.index', [
            'gateways' => $gateways,
        ]);
    }

    /**
     * Display the specified gateway instance.
     */
    public function show(GatewayInstance $gateway): View
    {
        return view('admin.gateways.show', [
            'gateway' => $gateway,
        ]);
    }

    /**
     * Restart the specified gateway instance.
     */
    public function restart(GatewayInstance $gateway, BaileysGatewayClientInterface $client): RedirectResponse
    {
        try {
            $client->restartInstance((string) $gateway->id);

            $gateway->update([
                'status' => 'active',
                'last_error' => null,
                'last_checked_at' => now(),
            ]);

            return redirect()
                ->back()
                ->with('success', "Gateway '{$gateway->name}' berhasil di-restart.");
        } catch (\Exception $e) {
            $gateway->update([
                'status' => 'error',
                'last_error' => $e->getMessage(),
                'last_checked_at' => now(),
            ]);

            return redirect()
                ->back()
                ->with('error', "Gagal me-restart gateway: {$e->getMessage()}");
        }
    }

    /**
     * Remove the specified gateway instance.
     */
    public function destroy(GatewayInstance $gateway): RedirectResponse
    {
        $name = $gateway->name;
        $gateway->delete();

        return redirect()
            ->route('admin.gateways.index')
            ->with('success', "Gateway '{$name}' berhasil dihapus.");
    }
}
