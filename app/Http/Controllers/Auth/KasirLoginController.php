<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $token = hash_hmac('sha256', 'kopikita-authorized-pos-device', $secret);

        // 1 year cookie (525600 minutes)
        $cookie = cookie('kasir_device_token', $token, 525600, null, null, false, true);

        return redirect()->route('kasir.login')
            ->withCookie($cookie)
            ->with('status', 'Perangkat ini berhasil diotorisasi sebagai terminal kasir resmi!');
    }

    public function deviceSetup(Request $request)
    {
        $clientIp = $request->ip();
        $allowedIps = config('cafe.kasir_allowed_ips', ['127.0.0.1', '::1']);
        $secret = config('cafe.kasir_device_secret', 'kopikita-pos-secret-device-2026');

        $isDeviceAuthorized = false;
        $cookieToken = $request->cookie('kasir_device_token');
        if ($cookieToken) {
            $expectedToken = hash_hmac('sha256', 'kopikita-authorized-pos-device', $secret);
            $isDeviceAuthorized = hash_equals($expectedToken, (string) $cookieToken);
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
            'authorizeUrl',
            'qrCodeUri'
        ));
    }

    public function revokeDevice(Request $request)
    {
        $cookie = cookie()->forget('kasir_device_token');

        return redirect()->route('kasir.device-setup')
            ->withCookie($cookie)
            ->with('status', 'Otorisasi perangkat ini telah berhasil dicabut.');
    }
}
