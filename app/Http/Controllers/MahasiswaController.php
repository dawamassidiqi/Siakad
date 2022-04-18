<?php

namespace App\Http\Controllers;

use PDF;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Mahasiswa_MataKuliah;
use Illuminate\Support\Facades\Storage;

class MahasiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //  public function index()
    //  {
    //  fungsi eloquent menampilkan data menggunakan pagination
    //  $mahasiswa = Mahasiswa::with('kelas')->get(); // Mengambil semua isi tabel
    //  $paginate = Mahasiswa::orderBy('id_mahasiswa', 'asc')->paginate(3);
    //  return view('mahasiswa.index', ['mahasiswa' => $mahasiswa,'paginate'=>$paginate]);
    // $mahasiswa = DB::table('mahasiswa')->simplepaginate(4);
    //     return view ('mahasiswa.index',compact('mahasiswa'));
    //  }

    public function index(Request $request)
    {
        $simplePaginate  = 3;
        $mahasiswa = Mahasiswa::with('kelas')->get();
        $mahasiswa   = Mahasiswa::when($request->keyword, function ($query) use ($request) {
            $query
                ->where('nama', 'like', "%{$request->keyword}%");
        })->orderBy('created_at', 'asc')->simplePaginate($simplePaginate);

        $mahasiswa->appends($request->only('keyword'));

        return view('mahasiswa.index', [
            'nama'    => 'Mahasiswa',
            'mahasiswa' => $mahasiswa,
        ])->with('i', ($request->input('simplePaginate', 1) - 1) * $simplePaginate);
    }
    public function create()
    {
        $kelas = Kelas::all(); // mendapatkan data dari tabel kelas
        return view('mahasiswa.create', ['kelas' => $kelas]);
    }
    public function store(Request $request)
    {
        //melakukan validasi data
        $request->validate([
            'Nim' => 'required',
            'Nama' => 'required',
            'Kelas' => 'required',
            'Jurusan' => 'required',
            'userfile' => 'required'
        ]);

        if ($request->file('userfile')) {
            $image_name = $request->file('userfile')->store('image', 'public');
        }

        $mahasiswa = new Mahasiswa;
        $mahasiswa->nim = $request->get('Nim');
        $mahasiswa->nama = $request->get('Nama');
        $mahasiswa->jurusan = $request->get('Jurusan');
        $mahasiswa->foto = $image_name;
        $mahasiswa->alamat = '';
        $mahasiswa->tanggal_lahir = '';
        $mahasiswa->save();

        $kelas = new Kelas;
        $kelas->id = $request->get('Kelas');

        //fungsi eloquent untuk menambah data
        $mahasiswa->kelas()->associate($kelas);
        $mahasiswa->save();
        // Mahasiswa::create($request->all());

        //jika data berhasil ditambahkan, akan kembali ke halaman utama
        return redirect()->route('mahasiswa.index')
            ->with('success', 'Mahasiswa Berhasil Ditambahkan');
    }
    public function show($nim)
    {
        //menampilkan detail data dengan menemukan/berdasarkan Nim Mahasiswa
        //  $Mahasiswa = Mahasiswa::where('nim', $nim)->first();
        $Mahasiswa = Mahasiswa::with('kelas')->where('nim', $nim)->first();
        return view('mahasiswa.detail', ['Mahasiswa' => $Mahasiswa]);
    }
    public function edit($nim)
    {
        //menampilkan detail data dengan menemukan berdasarkan Nim Mahasiswa untuk diedit
        $Mahasiswa = Mahasiswa::with('kelas')->where('nim', $nim)->first();
        $kelas = Kelas::all();
        // $Mahasiswa = DB::table('mahasiswa')->where('nim', $nim)->first();
        return view('mahasiswa.edit', compact('Mahasiswa', 'kelas'));
    }
    public function update(Request $request, $nim)
    {

        //ddd($request);
        //melakukan validasi data
        $request->validate([
            'Nim' => 'required',
            'Nama' => 'required',
            'Kelas' => 'required',
            'Jurusan' => 'required',
            'userfile' => 'required'
        ]);
        $mahasiswa = Mahasiswa::with('kelas')->where('nim', $nim)->first();
        $mahasiswa->nim = $request->get('Nim');
        $mahasiswa->nama = $request->get('Nama');
        $mahasiswa->jurusan = $request->get('Jurusan');

        if ($mahasiswa->foto && file_exists(storage_path('./app/public/' . $mahasiswa->foto))) {
            Storage::delete(['./public/', $mahasiswa->foto]);
        }

        $image_name = $request->file('userfile')->store('image', 'public');
        $mahasiswa->foto = $image_name;

        $mahasiswa->alamat = '';
        $mahasiswa->tanggal_lahir = '';
        $mahasiswa->save();

        $kelas = new Kelas;
        $kelas->id = $request->get('Kelas');

        //fungsi eloquent untuk menambah data
        $mahasiswa->kelas()->associate($kelas);
        $mahasiswa->save();
        // Mahasiswa::create($request->all());

        //jika data berhasil ditambahkan, akan kembali ke halaman utama
        return redirect()->route('mahasiswa.index')
            ->with('success', 'Mahasiswa Berhasil Diupdate');
    }
    public function destroy($nim)
    {
        //fungsi eloquent untuk menghapus data
        Mahasiswa::where('nim', $nim)->delete();
        return redirect()->route('mahasiswa.index')
            ->with('success', 'Mahasiswa Berhasil Dihapus');
    }
    public function khs($nim)
    {
        $mhs = Mahasiswa::where('nim', $nim)->first();
        $nilai = Mahasiswa_MataKuliah::where('mahasiswa_id', $mhs->id_mahasiswa)
            ->with('matakuliah')
            ->with('mahasiswa')
            ->get();
        $nilai->mahasiswa = Mahasiswa::with('kelas')->where('nim', $nim)->first();
        // dd($nilai);

        return view('mahasiswa.khs', compact('nilai'));
    }
    public function cetak_pdf($nim)
    {
        // dd('tetsing');
        $mhs = Mahasiswa::where('nim', $nim)->first();
        $nilai = Mahasiswa_MataKuliah::where('mahasiswa_id', $mhs->id_mahasiswa)
            ->with('matakuliah')
            ->with('mahasiswa')
            ->get();
        $nilai->mahasiswa = Mahasiswa::with('kelas')->where('nim', $nim)->first();
        $pdf = PDF::loadview('mahasiswa.nilai_pdf', compact('nilai'));
        return $pdf->stream();
    }
};
