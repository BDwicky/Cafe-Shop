<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\KasirAuthorizedDevice;
use App\Support\DeviceDetector;
use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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

    public static function getDeviceSecret(): string
    {
        $cached = cache()->get('kasir_dynamic_device_secret');
        if ($cached && is_string($cached)) {
            return $cached;
        }

        return (string) config('cafe.kasir_device_secret', 'kopikita-pos-secret-device-2026');
    }

    public function authorizeDevice(Request $request)
    {
        $inputKey = trim((string) $request->input('key', ''));
        $secret = self::getDeviceSecret();

        if ($inputKey === '' || ! hash_equals($secret, $inputKey)) {
            return redirect()->route('kasir.login')
                ->with('error', 'Kunci otorisasi perangkat tidak valid. Hubungi owner untuk mendapatkan akses.');
        }

        // Cek apakah perangkat ini membawa token yang berstatus dicabut (Banned) oleh Owner
        $cookieToken = $request->cookie('kasir_device_token');
        if ($cookieToken) {
            $existingHash = hash('sha256', (string) $cookieToken);
            $existingDevice = KasirAuthorizedDevice::where('device_token_hash', $existingHash)->first();

            if ($existingDevice && $existingDevice->is_revoked) {
                return redirect()->route('kasir.login')
                    ->with('error', "Perangkat ini ('{$existingDevice->device_name}') telah dicabut izinnya / diblokir oleh Owner. Pendaftaran ulang via QR ditolak. Hubungi Owner untuk mengaktifkan kembali via dashboard.");
            }

            // Jika perangkat sudah terdaftar dan masih aktif, segarkan status tanpa membuat entri duplikat
            if ($existingDevice && ! $existingDevice->is_revoked) {
                $existingDevice->touchActivity($request->ip());

                return redirect()->route('kasir.login')
                    ->with('status', "Perangkat '{$existingDevice->device_name}' sudah terdaftar dan aktif sebagai terminal kasir resmi!");
            }
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
        $secret = self::getDeviceSecret();

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

    public function regenerateDeviceSecret(Request $request)
    {
        $newSecret = 'pos-sec-'.Str::random(32);
        $revokeAll = $request->boolean('revoke_all_devices');

        // 1. Simpan di cache runtime & konfigurasi aplikasi
        cache()->forever('kasir_dynamic_device_secret', $newSecret);
        config(['cafe.kasir_device_secret' => $newSecret]);

        // 2. Perbarui file .env secara otomatis agar permanen saat restart server
        $envPath = app()->environmentFilePath();
        if (file_exists($envPath) && is_writable($envPath)) {
            $content = file_get_contents($envPath);
            if (preg_match('/^KASIR_DEVICE_SECRET=.*$/m', $content)) {
                $content = preg_replace('/^KASIR_DEVICE_SECRET=.*$/m', 'KASIR_DEVICE_SECRET='.$newSecret, $content);
            } else {
                $content .= PHP_EOL.'KASIR_DEVICE_SECRET='.$newSecret.PHP_EOL;
            }
            file_put_contents($envPath, $content);

            try {
                Artisan::call('config:clear');
            } catch (\Throwable) {
            }
        }

        // 3. Jika opsi revoke semua perangkat aktif dicentang (dengan PENGECUALIAN untuk perangkat yang sedang mengeksekusi)
        if ($revokeAll) {
            $cookieToken = $request->cookie('kasir_device_token');
            $currentTokenHash = $cookieToken ? hash('sha256', (string) $cookieToken) : null;
            $currentDevice = $currentTokenHash
                ? KasirAuthorizedDevice::where('device_token_hash', $currentTokenHash)->first()
                : null;

            $newCookie = null;

            if ($currentDevice) {
                // Pastikan perangkat saat ini tetap aktif dan tidak dicabut
                $currentDevice->update([
                    'is_revoked' => false,
                    'revoked_at' => null,
                    'last_active_at' => now(),
                ]);

                // Cabut semua perangkat LAIN selain perangkat yang sedang mengeksekusi
                KasirAuthorizedDevice::where('id', '!=', $currentDevice->id)->update([
                    'is_revoked' => true,
                    'revoked_at' => now(),
                ]);
            } else {
                // Jika perangkat saat ini belum tercatat di database, daftarkan agar tidak ter-logout
                $plainToken = Str::random(64);
                $detector = DeviceDetector::fromUserAgent($request->userAgent());
                $createdDevice = KasirAuthorizedDevice::create([
                    'device_name' => 'Perangkat Admin (Owner)',
                    'device_token_hash' => hash('sha256', $plainToken),
                    'device_type' => $detector->deviceType,
                    'platform' => $detector->platform,
                    'browser' => $detector->browser,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'is_revoked' => false,
                    'last_active_at' => now(),
                ]);

                // Cabut semua perangkat selain perangkat yang baru didaftarkan untuk eksekutor
                KasirAuthorizedDevice::where('id', '!=', $createdDevice->id)->update([
                    'is_revoked' => true,
                    'revoked_at' => now(),
                ]);

                // 1 year cookie untuk perangkat eksekutor
                $newCookie = cookie('kasir_device_token', $plainToken, 525600, null, null, false, true);
            }

            $redirect = redirect()->route('kasir.device-setup')
                ->with('status', 'Kunci rahasia baru berhasil digenerate. Seluruh perangkat kasir lain telah dicabut izinnya (perangkat Anda tetap aktif).');

            return $newCookie ? $redirect->withCookie($newCookie) : $redirect;
        }

        return redirect()->route('kasir.device-setup')
            ->with('status', 'Kunci rahasia keamanan baru berhasil digenerate. QR Code & tautan otorisasi baru telah diperbarui.');
    }
}
