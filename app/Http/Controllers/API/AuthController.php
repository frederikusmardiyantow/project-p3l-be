<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\MasterCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'email' => 'required|email:rfc,dns|unique:App\Models\MasterCustomer,email|unique:App\Models\MasterPegawai,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'alamat' => 'required'
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
}
