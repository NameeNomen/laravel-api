<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdukRequest;
use App\Http\Resources\ProdukResource;
use Illuminate\Http\Request;
use App\Models\Produk;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\ProdukImage;


class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $query = Produk::query();

        // SEARCH
        if ($request->has('search')) {
            $search = $request->search;

            $query->where('nama_barang', 'like', "%{$search}%")
                ->orWhere('kode_barang', 'like', "%{$search}%");
        }

        // FILTER KATEGORI
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // SORTING HARGA
        if ($request->has('sort')) {
            $sort = $request->sort;

            if ($sort == 'harga_asc') {
                $query->orderBy('harga', 'asc');
            }

            if ($sort == 'harga_desc') {
                $query->orderBy('harga', 'desc');
            }
        } else {
            $query->latest();
        } 

        // $produk = Produk::paginate(10);

        $produk = $query->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'List Produk',
            'data' => ProdukResource::collection($produk),
            'pagination' => [
                'current_page' => $produk->currentPage(),
                'last_page' => $produk->lastPage(),
                'per_page' => $produk->perPage(),
                'total' => $produk->total(),

                'first_page_url' => $produk->url(1),
                'last_page_url' => $produk->url($produk->lastPage()),
                'next_page_url' => $produk->nextPageUrl(),
                'prev_page_url' => $produk->previousPageUrl(),

                'from' => $produk->firstItem(),
                'to' => $produk->lastItem()
            ]
        ]);
    }
    
    public function store(StoreProdukRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('gambar')) {

            $file = $request->file('gambar');

            $filename = time().'_'.$file->getClientOriginalName();
            $destinationPath = storage_path('app/public/produk/'.$filename);
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());

            $image->scale(width: 800);
            $image->save($destinationPath);


            $data['gambar'] = 'produk/'.$filename;
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

        if (empty($data)) {
            return response()->json([
                'message' => 'Tidak ada data yang diupdate'
            ]);
        }
       
        // cek apakah ada gambar baru
        if ($request->hasFile('gambar')) {

            // hapus gambar lama jika ada
            if ($produk->gambar) {
                Storage::disk('public')->delete($produk->gambar);
            }
            $file = $request->file('gambar');
            
            $filename = time().'_'.$file->getClientOriginalName();
            $destinationPath = storage_path('app/public/produk/'.$filename);
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());
        if ($image->width() > 800) {
            $image->scale(width: 800);
        }
        $image->save($destinationPath,quality:80);

            $data['gambar'] = 'produk/'.$filename;
        }

        $produk->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Produk Berhasil Diupdate',
            'data' => new ProdukResource($produk)
        ]);
    }

    public function destroy($id)
    {
        $produk = Produk::findOrFail($id);

        if ($produk->gambar) {
            Storage::disk('public')->delete($produk->gambar);
        }

        $produk->delete();

        return response()->json([
            'success' => true,
            'message' => 'data berhasil dihapus'
        ]);
    }
    public function uploadImages(Request $request, $id){
        $produk = Produk::findOrFail($id);
        $request->validate([
            'gambar'=> 'required|array',
            'gambar.*'=>'image|mimes:jpg,jpeg,png|max:2048'
        ]);
        $manager = new ImageManager(new Driver());
        $images = [];
        foreach ($request->file('gambar') as $file) {
              $filename = time().'_'.$file->getClientOriginalName();
            $destinationPath = storage_path('app/public/produk/'.$filename);
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());
        
        if ($image->width() > 800) {
            
            $image->scale(width: 800);
        }
        $image->save($destinationPath,quality:80);

            $path = 'produk/'.$filename;
            $img = ProdukImage::create([
                'produk_id' => $produk->id,
                'path'=>$path
            ]);
            $images[] = $img;
        }

             return response()->json([
            'success' => true,
            'message' => 'Multiple image berhasil di upload',
            'data' => $images
        ]);
        
    }   
}
