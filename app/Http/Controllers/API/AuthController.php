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
use Illuminate\Support\Str;
use Mail;
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

    public function loginAdmin(Request $request)
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

        if(Auth::guard('pegawai')->attempt($login)){
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
        if($regisData['jenis_customer'] === 'G'){
            // cek jika jenis customer = G maka nama institusi wajib diisi!
            $validasi['nama_institusi'] = 'required';
        }else{
            $validasi['password'] = ['required', 'confirmed', Password::min(8)];
        }

        $validate = Validator::make($regisData, $validasi, [
            'required' => ':Attribute wajib diisi!',
            'email' => 'Email tidak valid!',
            'nama_customer.max' => 'Nama customer terlalu panjang! (max: 150 karakter)',
            'jenis_identitas' => 'Jenis identitas terlalu panjang (max: 50 karakter)',
            'min_digits' => ':Attribute harus berisi minimal 10 digit',
            'max_digits' => ':Attribute terlalu pajang (max digit: 15)',
            'unique' => 'Email ini sudah digunakan oleh pengguna lain!',
            'confirmed' => 'Pasword tidak cocok!',
            'min' => 'Password minimal 8 karakter!',
            'in' => 'Inputan hanya G atau P!'
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

        $token = Str::random(60);

        $cacheKey = 'reset_token_' . $token;
        // $expirationMinutes = 300; // Set the expiration time in detik! (5 menit (60x5))
        Cache::put($cacheKey, $user->email);

        // Generate the reset link. link ini yg digunakan di fe. jd di fe pastikan ada route ini.
        $resetLink = url('/password/reset', $token); //hasilnya bakal http://127.0.0.1:8000/password/reset/{tokennya}

        try{
            //Mengisi variabel yang akan ditampilkan pada view mail
            $data = [
                'resetLink' => $resetLink,
            ];
    
            // Send the email
            Mail::to($user->email)->send(new ForgetPasswordMail($data));

            return response([
                'status' => 'T',
                'message' => "Email telah terkirim, cek email Anda untuk mereset kata sandi."
            ], 200);

        }catch(Exception $e){
            return response([
                'status' => 'F',
                'message' => "Token gagal terkirim!"
            ], 500);
        }
    }

    public function tokenVerification($token){
        // cari token di cache
        $cacheKey = 'reset_token_' . $token;
        $userEmail = Cache::get($cacheKey);

        if(!$userEmail){
            // Token tidak valid atau sudah kedaluwarsa
            return response([
                'status' => 'F',
                'message' => "Token tidak valid atau sudah kedaluwarsa!"
            ], 404);
        }

        // Token valid, kirimkan pengguna ke halaman reset password dengan token
        return response([
            'status' => 'T',
            'message' => "Token valid. Silakan atur ulang kata sandi."
        ], 200);
    }

    public function updatePassword(Request $request, $token){
        // cari token di cache
        $cacheKey = 'reset_token_' . $token;
        $userEmail = Cache::get($cacheKey);

        if(!$userEmail){
            // Token tidak valid atau sudah kedaluwarsa
            return response([
                'status' => 'F',
                'message' => "Token tidak valid atau sudah kedaluwarsa!"
            ], 404);
        }

        $req = $request->all();

        $validate = Validator::make($req, [
            'password' => ['required', 'confirmed', Password::min(8)]
        ], [
            'required' => ':Attribute wajib diisi!',
            'confirmed' => 'Pasword tidak cocok!',
            'min' => 'Password minimal 8 karakter!'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'email' => $userEmail,
                'message' => $validate->errors()
            ], 400);
        }

        // Temukan pengguna berdasarkan alamat email
        $user = MasterCustomer::where('email', $userEmail)->first();

        if (!$user) {
            $user = MasterPegawai::where('email', $userEmail)->first();

            if(!$user){
                return response([
                    'status' => 'F',
                    'email' => $userEmail,
                    'message' => "User tidak ditemukan!"
                ], 404);
            }
        }
        
        $req['password'] = Hash::make($request->password);

        $user['password'] = $req['password'];

        if(!$user->save()){
            return response([
                'status' => 'F',
                'email' => $userEmail,
                'message' => 'Gagal mengubah password!'
            ], 400);
        }

        Cache::forget($cacheKey);
        return new PostResource('T', 'Berhasil Mengubah Password', $user);

    }
}
