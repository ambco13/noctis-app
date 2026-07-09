<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Cloudinary\Cloudinary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
            $data['image_path'] = $this->storeImage($request->file('image'));
        }
        unset($data['image']);

        return $data;
    }

    /**
     * "public" (local, non persistant sur Render) tant que CLOUDINARY_URL n'est
     * pas defini ; upload direct via le SDK Cloudinary sinon -- on utilise
     * l'URL renvoyee TELLE QUELLE par la reponse d'upload plutot que de la
     * reconstruire (le driver Flysystem du package Laravel reconstruit une
     * URL a partir du chemin local, qui ne correspond pas toujours au
     * public_id reellement stocke cote Cloudinary quand le nom de fichier
     * contient une extension -- 404 constate en prod).
     */
    private function storeImage(UploadedFile $file): string
    {
        if (config('filesystems.vehicle_images_disk') !== 'cloudinary') {
            return 'storage/'.$file->store('vehicles', 'public');
        }

        $cloud = config('filesystems.disks.cloudinary');
        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloud['cloud_name'],
                'api_key' => $cloud['api_key'],
                'api_secret' => $cloud['api_secret'],
            ],
            'url' => ['secure' => true],
        ]);

        $response = $cloudinary->uploadApi()->upload($file->getRealPath(), ['folder' => 'vehicles']);

        return (string) $response['secure_url'];
    }
}
