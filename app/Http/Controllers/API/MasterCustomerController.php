<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\MasterCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Validator;

class MasterCustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = MasterCustomer::where('flag_stat', 1)->get();

        return new PostResource('T', 'Berhasil Ambil Data Customer..', $data);
    }

    public function getDataForAllFlag()
    {
        $data = MasterCustomer::all();

        return new PostResource('T', 'Berhasil Ambil Data All Customer..', $data);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $customer = MasterCustomer::find($id);

        if(!is_null($customer)){
            return new PostResource('T', 'Berhasil Mendapatkan Data Customer '.$customer['nama_customer'], $customer);
        }

        return response([
            'status' => 'F',
            'message' => 'Data Customer tidak ditemukan!'
        ], 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $data = Auth::user();
        $id = $data['id'];

        if($data['id_role']){ //buat jaga-jaga aja, walau kl bukan customer dah langsung kehalang di auth nya (checkRole nya dulu soalnya). Kalo mau diilangi bole
            return response([
                'status' => 'F',
                'message' => 'Anda bukan customer ya?'
            ], 404);
        }

        $updateData = $request->all();

        $validasi = [
            'jenis_customer' => 'required|in:G,P',
            'nama_customer' => 'required|string|max:150',
            'no_identitas' => 'required|numeric|max_digits:50',
            'jenis_identitas' => 'required|string|max:50',
            'no_telp' => 'required|numeric|min_digits: 10|max_digits:15',
            'email' => [
                'required',
                'email:rfc,dns',
                Rule::unique('App\Models\MasterCustomer', 'email'),
                Rule::unique('App\Models\MasterPegawai', 'email'),
            ],
            'alamat' => 'required'
        ];
        if($updateData['jenis_customer'] === 'G'){
            // cek jika jenis customer = G maka nama institusi wajib diisi!
            $validasi['nama_institusi'] = 'required';
        }

        $validate = Validator::make($updateData, $validasi, [
            'required' => ':Attribute wajib diisi!',
            'email' => 'Email tidak valid!',
            'nama_customer.max' => 'Nama customer terlalu panjang! (max: 150 karakter)',
            'jenis_identitas' => 'Jenis identitas terlalu panjang (max: 50 karakter)',
            'max_digit' => ':Attribute terlalu pajang (max digit: 50)',
            'unique' => 'Email ini sudah digunakan oleh pengguna lain!',
            'in' => 'Inputan hanya G atau P!'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        if(($data['jenis_customer'] !== $updateData['jenis_customer']) && $updateData['jenis_customer'] === 'G'){
            // jika perubahan dari personal ke grup, set password menjadi null
            $data['password'] = NULL;
        }else{
            // ini perubahan dari group ke personal
            // set password default "12345678"
            $data['password'] = Hash::make('12345678');
        }

        if(is_null($updateData['flag_stat']) || $updateData['flag_stat'] === ""){
            $updateData['flag_stat'] = 1;
        }

        $data['nama_customer'] = $updateData['nama_customer'];
        $data['no_identitas'] = $updateData['no_identitas'];
        $data['jenis_identitas'] = $updateData['jenis_identitas'];
        $data['no_telp'] = $updateData['no_telp'];
        $data['email'] = $updateData['email'];
        $data['alamat'] = $updateData['alamat'];
        $data['flag_stat'] = $updateData['flag_stat'];

        if(!$data->save()){
            return response([
                'status' => 'F',
                'message' => 'Gagal mengubah data!'
            ], 400);
        }

        return new PostResource('T', 'Berhasil Mengubah Data', $data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $customer = MasterCustomer::find($id);

        if(is_null($customer) || $customer->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Data Customer tidak ditemukan!'
            ], 404);
        }

        $deleted = DB::table('customers')->where('id', $customer->id)->update(['flag_stat' => 0]);

        if($deleted){
            return new PostResource('T', 'Berhasil Menghapus Data Customer '.$customer['nama_customer'], $deleted);
        }

        return response([
            'status' => 'F',
            'message' => 'Gagal menghapus data!'
        ], 400);
    }

    public function getProfile(){
        $customer = Auth::user();

        if(!$customer || $customer->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => "Customer tidak diketahui!"
            ], 404);
        }

        return response([
            'status' => 'T',
            'message' => $customer
        ], 200);
    }

    public function ubahPassword(Request $request)
    {
        $customer = Auth::user();

        $updatePass = $request->all();

        $validate = Validator::make($updatePass, [
            'password_lama' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)]
        ], [
            'required' => ':Attribute wajib diisi!',
            'confirmed' => 'Pasword tidak cocok!',
            'min' => 'Password minimal 8 karakter!'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        $cek = Hash::check($updatePass['password_lama'], $customer->password);

        if($cek){
            $customer->password = Hash::make($updatePass['password']);
            $customer->save();

            return response([
                'status' => 'T',
                'message' => 'Ubah Password berhasil'
            ], 200);
        }else if(!$cek){
            return response([
                'status' => 'F',
                'message' => 'Password salah, silakan cek kembali!'
            ], 404);
        }

        return response([
            'status' => 'F',
            'message' => 'Gagal ubah Password, ada kesalahan sistem!'
        ], 500);
    }

    public function riwayatTrxByMe(){ //hanya tampil riwayat untuk yg sedang login
        $user = Auth::user();
        $user->trxReservasis;

        return new PostResource('T', 'Berhasil Ambil Data Riwayat Transaksi..', $user);
    }

    public function riwayatTrxBySM(string $id){ //tampil riwayat untuk customer yg dipilih SM
        
        $customer = MasterCustomer::find($id); // mencari id customer yg diinginkan

        if(is_null($customer) || $customer->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Data Customer tidak ditemukan!'
            ], 404);
        }

        $customer->trxReservasis;
        return new PostResource('T', 'Berhasil Ambil Data Riwayat Transaksi..', $customer);
    }
}
