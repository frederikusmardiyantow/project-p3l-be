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
    public function handle(Request $request, Closure $next, $role)
    {
        $role_lower = strtolower($role); //ubah ke huruf kecil smua dlu
        $user = Auth::user();
        if($role_lower !== 'all' && (!$user->role || strtolower($user->role->nama_role) !== $role_lower)){
            return response([
                'status' => 'F',
                'message' => 'Anda tidak punya akses!'
            ], 403);
        }
        return $next($request);
    }
}
