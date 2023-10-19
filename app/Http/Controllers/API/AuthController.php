<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Mail\ForgetPasswordMail;
use App\Models\MasterCustomer;
use App\Models\MasterPegawai;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Validator;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $login = $request->only('email', 'password');

        $validate = Validator::make($login, [
            'email' => 'required|email:rfc,dns',
            'password' => 'required',
        ], [
            'required' => ':Attribute wajib diisi!',
            'email' => 'Email tidak valid!',
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        if(Auth::guard('customer')->attempt($login)){
            $user = Auth::guard('customer')->user();
        }else if(Auth::guard('pegawai')->attempt($login)){
            $user = Auth::guard('pegawai')->user();
            $user->role;
        }else{
            return response()->json([
                'status' => 'F',
                'message' => 'Email / Password yang dimasukkan salah!',
            ], 401);
        }

        $data = [
            'user' => $user,
            'authorization' => [
                'token' => $user->createToken('ApiToken')->plainTextToken,
                'type' => 'bearer',
            ]
        ];
        return new PostResource('T', 'Berhasil Login..', $data);
    }

    public function register(Request $request)
    {
        $regisData = $request->all();

        $validate = Validator::make($regisData, [
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
            'password' => ['required', 'confirmed', Password::min(8)],
            'alamat' => 'required'
        ], [
            'required' => ':Attribute wajib diisi!',
            'email' => 'Email tidak valid!',
            'nama_customer.max' => 'Nama customer terlalu panjang! (max: 150 karakter)',
            'jenis_identitas' => 'Jenis identitas terlalu panjang (max: 50 karakter)',
            'max_digit' => ':Attribute terlalu pajang (max digit: 50)',
            'unique' => 'Email ini sudah digunakan oleh pengguna lain!',
            'confirmed' => 'Pasword tidak cocok!',
            'min' => 'Password minimal 8 karakter!'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        if(is_null($regisData['jenis_customer']) || $regisData['jenis_customer'] === ""){
            $regisData['jenis_customer'] = 'P';
        }
        $regisData['password'] = Hash::make($request->password);
        if(is_null($regisData['flag_stat']) || $regisData['flag_stat'] === ""){
            $regisData['flag_stat'] = 1;
        }

        $customer = MasterCustomer::create($regisData);

        return response([
            'status' => 'T',
            'message' => 'Sukses Registrasi',
            'user' => $customer,
        ], 201);
    }

    public function logout()
    {
        $user = Auth::user();
        Auth::user()->tokens()->delete();

        return new PostResource('T', 'Berhasil Logout.. Terima Kasih ' . $user['nama_customer'], null);
    }

    public function forgetPassword(Request $request) 
    {
        $req = $request->all();

        $validate = Validator::make($req, [
            'email'=> 'required|email:rfc,dns',
            'role' => 'required'
        ], [
            'required' => ':Attribute wajib diisi!'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        if(strtolower($req['role']) !== 'customer' && strtolower($req['role']) !== 'pegawai'){
            return response([
                'status' => 'F',
                'message' => "Pastikan request role diisi 'customer' atau 'pegawai'"
            ], 400);
        }else{
            if(strtolower($req['role']) === 'customer'){
                $user = MasterCustomer::where('email', $req['email'])->first();
            }else{
                $user = MasterPegawai::where('email', $req['email'])->first();
            }

            if(is_null($user)){
                return response([
                    'status' => 'F',
                    'message' => "Email tidak terdaftar!"
                ], 404);
            }
        }

        //generate token
        $length = 6; //token akan berjumlah 6 angka
        $characters = '0123456789';
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[rand(0, strlen($characters) - 1)];
        }

        try{
            //Mengisi variabel yang akan ditampilkan pada view mail
            $content = [
                'token' => $token,
            ];
            
            //Mengirim email
            Mail::to($user['email'])->send(new ForgetPasswordMail($content));

            // Simpan token di cache dengan waktu tertentu (contoh: 60 menit)
            $cacheKey = 'reset_token_' . $token;
            $expirationMinutes = 5; // Waktu kadaluarsa dalam menit

            // Menyimpan token di cache dengan waktu kadaluarsa
            Cache::put($cacheKey, $token, $expirationMinutes);

            return response([
                'status' => 'T',
                'message' => "Token telah terkirim, cek email ya.."
            ], 200);

        }catch(Exception $e){
            return response([
                'status' => 'F',
                'message' => "Token gagal terkirim!"
            ], 500);
        }
    }

    public function tokenVerification(Request $request){
        $token = $request->input("token");

        $cacheKey = 'reset_token_' . $token;
        $retrievedToken = Cache::get($cacheKey);

        if(!empty($retrievedToken)){
            return response([
                'status' => 'T',
                'message' => "Token berhasil diverifikasi.."
            ], 400);
        }

        return response([
            'status' => 'F',
            'message' => "Token tidak diketahui!"
        ], 404);
    }
}
