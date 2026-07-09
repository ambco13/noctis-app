<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VehicleAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.vehicles', [
            'vehicles' => Vehicle::orderBy('sort_order')->get(),
            'editing' => null,
        ]);
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('admin.vehicles', [
            'vehicles' => Vehicle::orderBy('sort_order')->get(),
            'editing' => $vehicle,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Vehicle::create($this->validated($request));

        return redirect()->route('admin.vehicles')->with('ok', __('Véhicule créé.'));
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($this->validated($request));

        return redirect()->route('admin.vehicles')->with('ok', __('Véhicule mis à jour.'));
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return redirect()->route('admin.vehicles')->with('ok', __('Véhicule supprimé.'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'base_fare' => ['required', 'numeric', 'min:0'],
            'price_per_km' => ['required', 'numeric', 'min:0'],
            'price_per_min' => ['required', 'numeric', 'min:0'],
            'min_price' => ['required', 'numeric', 'min:0'],
            'capacity' => ['required', 'integer', 'min:1', 'max:99'],
            'luggage' => ['required', 'integer', 'min:0', 'max:99'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            // Disque "public" local par defaut (dev) ; sur Render (disque
            // ephemere), FILESYSTEM_DISK=s3 pointe vers un bucket R2 persistant
            // -- image_path stocke alors l'URL publique complete plutot qu'un
            // chemin relatif, asset() la renvoie inchangee dans ce cas.
            $disk = config('filesystems.default');
            $path = $request->file('image')->store('vehicles', $disk);

            $data['image_path'] = $disk === 'public'
                ? 'storage/'.$path
                : Storage::disk($disk)->url($path);
        }
        unset($data['image']);

        return $data;
    }
}
