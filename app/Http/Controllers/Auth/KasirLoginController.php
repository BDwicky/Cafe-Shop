<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\KasirAuthorizedDevice;
use App\Support\DeviceDetector;
use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class KasirLoginController extends Controller
{
    public function showForm()
    {
        return view('kasir.login');
    }

    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $loginInput = strtolower(trim($validated['email']));
        if ($loginInput === 'owner') {
            $loginInput = 'owner@kopikita.test';
        } elseif ($loginInput === 'kasir') {
            $loginInput = 'kasir@kopikita.test';
        }

        $credentials = [
            'email' => $loginInput,
            'password' => $validated['password'],
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/kasir');
        }

        return back()
            ->withErrors(['email' => 'Email atau password salah.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/kasir/login');
    }

    public function authorizeDevice(Request $request)
    {
        $inputKey = trim((string) $request->input('key', ''));
        $secret = config('cafe.kasir_device_secret', 'kopikita-pos-secret-device-2026');

        if ($inputKey === '' || ! hash_equals($secret, $inputKey)) {
            return redirect()->route('kasir.login')
                ->with('error', 'Kunci otorisasi perangkat tidak valid. Hubungi owner untuk mendapatkan akses.');
        }

        // Generate unique token per device
        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        $detector = DeviceDetector::fromUserAgent($request->userAgent());
        $sequence = KasirAuthorizedDevice::count() + 1;

        KasirAuthorizedDevice::create([
            'device_name' => $detector->suggestName($sequence),
            'device_token_hash' => $tokenHash,
            'device_type' => $detector->deviceType,
            'platform' => $detector->platform,
            'browser' => $detector->browser,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        // 1 year cookie (525600 minutes)
        $cookie = cookie('kasir_device_token', $plainToken, 525600, null, null, false, true);

        return redirect()->route('kasir.login')
            ->withCookie($cookie)
            ->with('status', 'Perangkat ini berhasil diotorisasi sebagai terminal kasir resmi!');
    }

    public function deviceSetup(Request $request)
    {
        $clientIp = $request->ip();
        $allowedIps = config('cafe.kasir_allowed_ips', ['127.0.0.1', '::1']);
        $secret = config('cafe.kasir_device_secret', 'kopikita-pos-secret-device-2026');

        $devices = KasirAuthorizedDevice::orderBy('last_active_at', 'desc')->get();

        $currentDevice = null;
        $isDeviceAuthorized = false;
        $cookieToken = $request->cookie('kasir_device_token');
        if ($cookieToken) {
            $tokenHash = hash('sha256', (string) $cookieToken);
            $currentDevice = KasirAuthorizedDevice::where('device_token_hash', $tokenHash)->first();
            if ($currentDevice && ! $currentDevice->is_revoked) {
                $isDeviceAuthorized = true;
            } elseif (! $currentDevice) {
                $expectedLegacy = hash_hmac('sha256', 'kopikita-authorized-pos-device', $secret);
                $isDeviceAuthorized = hash_equals($expectedLegacy, (string) $cookieToken);
            }
        }

        // Tautan otorisasi instan untuk perangkat tablet baru
        $authorizeUrl = route('kasir.authorize-device', ['key' => $secret]);

        // QR Code Data URI untuk scan langsung via kamera tablet
        $qrCodeUri = QrCode::dataUri($authorizeUrl, 220);

        return view('kasir.device-setup', compact(
            'clientIp',
            'allowedIps',
            'secret',
            'isDeviceAuthorized',
            'currentDevice',
            'devices',
            'authorizeUrl',
            'qrCodeUri'
        ));
    }

    public function revokeDevice(Request $request)
    {
        $cookieToken = $request->cookie('kasir_device_token');
        if ($cookieToken) {
            $tokenHash = hash('sha256', (string) $cookieToken);
            KasirAuthorizedDevice::where('device_token_hash', $tokenHash)->update([
                'is_revoked' => true,
                'revoked_at' => now(),
            ]);
        }

        $cookie = cookie()->forget('kasir_device_token');

        return redirect()->route('kasir.device-setup')
            ->withCookie($cookie)
            ->with('status', 'Otorisasi perangkat ini telah berhasil dicabut.');
    }

    public function revokeRemoteDevice(Request $request, KasirAuthorizedDevice $device)
    {
        $device->revoke();

        $cookieToken = $request->cookie('kasir_device_token');
        $isSelf = $cookieToken && hash_equals($device->device_token_hash, hash('sha256', (string) $cookieToken));

        $redirect = redirect()->route('kasir.device-setup')
            ->with('status', "Akses untuk '{$device->device_name}' berhasil dicabut.");

        if ($isSelf) {
            return $redirect->withCookie(cookie()->forget('kasir_device_token'));
        }

        return $redirect;
    }

    public function restoreRemoteDevice(KasirAuthorizedDevice $device)
    {
        $device->restore();

        return redirect()->route('kasir.device-setup')
            ->with('status', "Akses untuk '{$device->device_name}' berhasil diaktifkan kembali.");
    }

    public function destroyRemoteDevice(KasirAuthorizedDevice $device)
    {
        $name = $device->device_name;
        $device->delete();

        return redirect()->route('kasir.device-setup')
            ->with('status', "Perangkat '{$name}' telah dihapus dari daftar riwayat.");
    }

    public function renameRemoteDevice(Request $request, KasirAuthorizedDevice $device)
    {
        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $device->update(['device_name' => trim($validated['device_name'])]);

        return redirect()->route('kasir.device-setup')
            ->with('status', "Nama perangkat berhasil diperbarui menjadi '{$device->device_name}'.");
    }
}
