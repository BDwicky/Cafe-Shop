<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Auth\KasirLoginController;
use App\Models\KasirAuthorizedDevice;
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
        $tokenStatus = $this->checkDeviceTokenStatus($request);
        if ($tokenStatus['valid']) {
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
                'message' => $tokenStatus['revoked']
                    ? 'Akses perangkat ini telah dicabut oleh Owner.'
                    : 'Akses ditolak. Web kasir hanya dapat dibuka melalui jaringan Wi-Fi/IP kafe resmi atau perangkat terdaftar.',
                'client_ip' => $clientIp,
                'is_revoked' => $tokenStatus['revoked'],
            ], 403);
        }

        return response()->view('errors.kasir-restricted', [
            'clientIp' => $clientIp,
            'allowedIps' => $allowedIps,
            'isRevoked' => $tokenStatus['revoked'],
        ], 403);
    }

    /**
     * Memeriksa status token otorisasi perangkat dari cookie:
     * - Valid: aktif di tabel kasir_authorized_devices atau token HMAC legacy cocok.
     * - Revoked: tercatat di database namun berstatus is_revoked = true.
     *
     * @return array{valid: bool, revoked: bool}
     */
    protected function checkDeviceTokenStatus(Request $request): array
    {
        $token = $request->cookie('kasir_device_token');
        if (! $token) {
            return ['valid' => false, 'revoked' => false];
        }

        $tokenStr = (string) $token;
        $hash = hash('sha256', $tokenStr);

        // A. Cek di tabel database kasir_authorized_devices
        $device = KasirAuthorizedDevice::where('device_token_hash', $hash)->first();
        if ($device) {
            if ($device->is_revoked) {
                return ['valid' => false, 'revoked' => true];
            }

            // Perbarui waktu aktif (dibatasi cache tiap 5 menit agar performa tetap kencang)
            $cacheKey = "kasir_device_active_{$device->id}";
            if (! cache()->has($cacheKey)) {
                $device->touchActivity($request->ip());
                cache()->put($cacheKey, true, now()->addMinutes(5));
            }

            return ['valid' => true, 'revoked' => false];
        }

        // B. Fallback kompatibilitas: token statis HMAC bawaan
        $secret = KasirLoginController::getDeviceSecret();
        $legacyExpected = hash_hmac('sha256', 'kopikita-authorized-pos-device', $secret);

        if (hash_equals($legacyExpected, $tokenStr)) {
            return ['valid' => true, 'revoked' => false];
        }

        return ['valid' => false, 'revoked' => false];
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
