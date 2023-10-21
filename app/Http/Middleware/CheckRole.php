<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole 
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $roles = array_slice(func_get_args(), 2); //mengambil semua argumen yang diberikan ke dalam fungsi (termasuk argumen setelah argumen pertama dan kedua)
        // Dalam konteks ini, itu berarti $roles akan berisi semua argumen yang diberikan setelah $request dan $next. Jadi, jika memanggil middleware dengan: $this->middleware('checkRole:admin,customer'); Maka $roles akan berisi array ['admin', 'customer'].
        $role_lower = array_map('strtolower', $roles); // Ubah semua elemen dalam array menjadi huruf kecil
        
        $user = Auth::user();
        if(($user['jenis_customer'] && in_array('customer', $role_lower)) || in_array('all', $role_lower) || ($user->role && in_array(strtolower($user->role->nama_role), $role_lower))) {
            return $next($request);
        }else {
            return response([
                'status' => 'F',
                'message' => 'Anda tidak punya akses!'
            ], 403);
        }
    }
}
