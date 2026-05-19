<?php

namespace App\Http\Controllers;

use App\Models\AdminSetting;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use LogsActivity;

    private const ADMIN_PASSWORD_KEY = 'admin_password_hash';

    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        $hash = $this->getAdminPasswordHash();
        if ($hash === '') {
            return back()->with('error', 'Admin password is not configured in the database yet. Run migrations, then try again.');
        }

        if (!Hash::check($validated['password'], $hash)) {
            $this->writeLog('login_failed', 'admin', null, 'admin', null, null, 'Incorrect password', $request);
            return back()->with('error', 'Incorrect password');
        }

        $request->session()->regenerate();
        $request->session()->put('is_admin', true);

        $this->writeLog('login', 'admin', null, 'admin', null, null, null, $request);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $this->writeLog('logout', 'admin', null, 'admin', null, null, null, $request);

        $request->session()->forget('is_admin');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function showChangePassword()
    {
        return view('admin-change-password');
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:12|confirmed',
        ]);

        $currentHash = $this->getAdminPasswordHash();
        if ($currentHash === '' || !Hash::check($validated['current_password'], $currentHash)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        AdminSetting::updateOrCreate(
            ['key' => self::ADMIN_PASSWORD_KEY],
            ['value' => Hash::make($validated['new_password'])]
        );

        $this->writeLog('password_changed', 'admin_settings', null, 'admin_password_hash', null, null, null, $request);

        $request->session()->forget('is_admin');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('admin.login')
            ->with('success', 'Password updated. Please log in again with your new password.');
    }

    private function getAdminPasswordHash(): string
    {
        $setting = AdminSetting::where('key', self::ADMIN_PASSWORD_KEY)->first();
        if ($setting && is_string($setting->value) && $setting->value !== '') {
            return $setting->value;
        }

        return '';
    }
}