<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login', [
            'canRegister' => env('ALLOW_REGISTRATION', false),
            'allowPasswordLogin' => env('ALLOW_PASSWORD_LOGIN', false),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function registerCreate()
    {
        if (!env('ALLOW_REGISTRATION', false)) {
            abort(404);
        }
        return Inertia::render('Auth/Register');
    }

    public function registerStore(Request $request)
    {
        if (!env('ALLOW_REGISTRATION', false)) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . \App\Models\User::class,
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'phone' => 'nullable|string|max:20',
        ]);

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'phone' => $request->phone,
            'type' => 'student', // Default type
            'status' => 'planning',
        ]);

        Auth::login($user);

        return redirect(route('dashboard'));
    }

    public function redirectToGoogle()
    {
        return \Laravel\Socialite\Facades\Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/drive.file']) // Request Drive scope immediately
            ->with(['access_type' => 'offline', 'prompt' => 'consent select_account']) // Refresh token
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            /** @var \Laravel\Socialite\Two\User $googleUser */
            $googleUser = \Laravel\Socialite\Facades\Socialite::driver('google')->user();

            $user = \App\Models\User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            if ($user) {
                // ... existing update logic ...
                $user->update([
                    'google_id' => $googleUser->id,
                    'google_token' => $googleUser->token,
                    'google_refresh_token' => $googleUser->refreshToken,
                    'name' => $googleUser->name,
                    'avatar' => $googleUser->getAvatar(),
                ]);
            } else {
                // If Registration is disabled, check if email is in whitelist (FamilyMember table)
                // Actually, the requirement says: "if he is already registered... he can login". 
                // But if it is the FIRST time (no user record), we must check ALLOW_REGISTRATION.
                // However, there is a nuance: "if user/email is in a family" -> "he can visualize data".
                // So if he was invited (FamilyMember exists), he should probably be allowed to register/claim account?
                // The prompt says: "if the user logs in for the first time... check if he is in a family... if not he can start his own".
                // AND "if ALLOW_REGISTRATION=false... prevent this registration".

                // Let's implement EXACTLY what was asked:
                // "if the user logs in via google for the first time (registering) and ALLOW_REGISTRATION=false, prevent this registration."
                // "unless he is NOT a new user" (already handled by 'if $user').

                // BUT wait, does "already registered" mean "in users table" or "in family members"?
                // Usually "registration" creates the User record.
                // If I am invited to a family, I am NOT a User yet.
                // So strict reading: if ALLOW_REGISTRATION=false, I cannot create a User account, even if invited?
                // OR does invite bypass it?
                // "if ALLOW_REGISTRATION=false, the system must prevent this registration, IF HE IS ALREADY REGISTERED, then even with false he can login".
                // This implies: new User creation is blocked.

                if (!env('ALLOW_REGISTRATION', false)) {
                    return redirect(route('login'))->with('error', 'Novos registros estão desativados no momento.');
                }

                // Create user
                $user = \App\Models\User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'avatar' => $googleUser->getAvatar(),
                    'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24)),
                    'google_id' => $googleUser->id,
                    'google_token' => $googleUser->token,
                    'google_refresh_token' => $googleUser->refreshToken,
                    'type' => 'student',
                    'status' => 'planning',
                    'email_verified_at' => now(),
                ]);
            }

            // Sync Family Membership and Name
            $memberRecord = \App\Models\FamilyMember::where('email', $user->email)->first();

            if ($memberRecord) {
                $memberRecord->update([
                    'name' => $googleUser->name,
                    'user_id' => $user->id
                ]);

                if (!$user->family_id) {
                    $user->update(['family_id' => $memberRecord->family_id]);
                }
            } else {
                $memberById = \App\Models\FamilyMember::where('user_id', $user->id)->first();
                if ($memberById) {
                    $memberById->update(['name' => $googleUser->name]);
                }
            }

            Auth::login($user);

            return redirect()->intended(route('dashboard'));

        } catch (\Exception $e) {
            return redirect(route('login'))->with('error', 'Unable to login with Google: ' . $e->getMessage());
        }
    }
}
