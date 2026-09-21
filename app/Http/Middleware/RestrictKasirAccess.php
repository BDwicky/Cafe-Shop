<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class RestrictKasirAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jika pembatasan dinonaktifkan di konfigurasi/env, izinkan akses langsung
        if (! config('cafe.kasir_ip_restriction_enabled', false)) {
            return $next($request);
        }

        // 1. Cek Kunci Perangkat Terdaftar (Registered Device Token)
        if ($this->hasValidDeviceToken($request)) {
            return $next($request);
        }

        // 2. Cek Whitelist IP / Jaringan Wi-Fi Kafe
        $clientIp = $request->ip();
        $allowedIps = config('cafe.kasir_allowed_ips', ['127.0.0.1', '::1']);

        if ($clientIp && $this->isIpAllowed($clientIp, $allowedIps)) {
            return $next($request);
        }

        // 3. Akses Ditolak (Unauthorized Network / Device)
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Akses ditolak. Web kasir hanya dapat dibuka melalui jaringan Wi-Fi/IP kafe resmi atau perangkat terdaftar.',
                'client_ip' => $clientIp,
            ], 403);
        }

        return response()->view('errors.kasir-restricted', [
            'clientIp' => $clientIp,
            'allowedIps' => $allowedIps,
        ], 403);
    }

    /**
     * Memeriksa apakah perangkat memiliki cookie otorisasi perangkat yang valid.
     */
    protected function hasValidDeviceToken(Request $request): bool
    {
        $token = $request->cookie('kasir_device_token');
        if (! $token) {
            return false;
        }

        $secret = config('cafe.kasir_device_secret', 'kopikita-pos-secret-device-2026');
        $expected = hash_hmac('sha256', 'kopikita-authorized-pos-device', $secret);

        return hash_equals($expected, (string) $token);
    }

    /**
     * Memeriksa apakah IP client cocok dengan daftar IP, wildcard, atau CIDR subnet yang diizinkan.
     *
     * @param  array<string>  $allowedRules
     */
    protected function isIpAllowed(string $ip, array $allowedRules): bool
    {
        foreach ($allowedRules as $rule) {
            $rule = trim($rule);
            if ($rule === '') {
                continue;
            }

            // A. Wildcard match (e.g. 192.168.* or 10.0.*)
            if (str_contains($rule, '*')) {
                if (fnmatch($rule, $ip)) {
                    return true;
                }

                continue;
            }

            // B. Exact IP atau CIDR match via Symfony IpUtils (e.g. 127.0.0.1, ::1, 192.168.1.0/24)
            try {
                if (IpUtils::checkIp($ip, $rule)) {
                    return true;
                }
            } catch (\Throwable) {
                // Abaikan jika format IP salah, lanjut cek aturan berikutnya
                continue;
            }
        }

        return false;
    }
}
