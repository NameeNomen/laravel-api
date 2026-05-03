<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdukRequest;
use App\Http\Resources\ProdukResource;
use Illuminate\Http\Request;
use App\Models\Produk;
use App\Models\ProdukImage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $query = Produk::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_barang', 'like', "%{$request->search}%")
                  ->orWhere('kode_barang', 'like', "%{$request->search}%");
            });
        }

        if ($request->kategori) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->sort == 'harga_asc') {
            $query->orderBy('harga', 'asc');
        } elseif ($request->sort == 'harga_desc') {
            $query->orderBy('harga', 'desc');
        } else {
            $query->latest();
        }

        $produk = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => ProdukResource::collection($produk),
            'pagination' => [
                'current_page' => $produk->currentPage(),
                'last_page' => $produk->lastPage(),
            ]
        ]);
    }

    public function store(StoreProdukRequest $request)
    {
        $data = $request->validated();

        // HANDLE GAMBAR
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');

            if ($file && $file->isValid()) {
                try {
                    $manager = new ImageManager(new Driver());
                    $image = $manager->read($file->getRealPath());

                    if ($image->width() > 800) {
                        $image->scale(width: 800);
                    }

                    $encoded = (string) $image->encode('jpg', 80);

                    $result = cloudinary()->upload($encoded);

                    if ($result) {
                        $data['gambar'] = $result->getSecurePath();
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Upload gambar gagal',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }
        }

        $produk = Produk::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dibuat',
            'data' => new ProdukResource($produk)
        ], 201);
    }

    public function show($id)
    {
        $produk = Produk::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ProdukResource($produk)
        ]);
    }

    public function update(StoreProdukRequest $request, $id)
    {
        $produk = Produk::findOrFail($id);
        $data = $request->validated();

        // HANDLE GAMBAR
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');

            if ($file && $file->isValid()) {
                try {
                    $manager = new ImageManager(new Driver());
                    $image = $manager->read($file->getRealPath());

                    if ($image->width() > 800) {
                        $image->scale(width: 800);
                    }

                    $encoded = (string) $image->encode('jpg', 80);

                    $result = cloudinary()->upload($encoded);

                    if ($result) {
                        $data['gambar'] = $result->getSecurePath();
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Upload gambar gagal',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }
        }

        $produk->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diupdate',
            'data' => new ProdukResource($produk)
        ]);
    }

    public function destroy($id)
    {
        $produk = Produk::findOrFail($id);
        $produk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus'
        ]);
    }

    public function uploadImages(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);

        $request->validate([
            'gambar' => 'required|array',
            'gambar.*' => 'image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $manager = new ImageManager(new Driver());
        $images = [];

        foreach ($request->file('gambar') as $file) {
            if ($file && $file->isValid()) {
                try {
                    $image = $manager->read($file->getRealPath());

                    if ($image->width() > 800) {
                        $image->scale(width: 800);
                    }

                    $encoded = (string) $image->encode('jpg', 80);

                    $result = cloudinary()->upload($encoded);

                    if ($result) {
                        $img = ProdukImage::create([
                            'produk_id' => $produk->id,
                            'path' => $result->getSecurePath()
                        ]);

                        $images[] = $img;
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Upload multiple gagal',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Multiple image berhasil diupload',
            'data' => $images
        ]);
    }
}