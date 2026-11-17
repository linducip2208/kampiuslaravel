<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class SsoController extends Controller
{
    /**
     * @var array<string, array{authorize: string, token: string, userinfo: string}>
     */
    private const ENDPOINTS = [
        'google' => [
            'authorize' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token' => 'https://oauth2.googleapis.com/token',
            'userinfo' => 'https://www.googleapis.com/oauth2/v3/userinfo',
        ],
        'microsoft' => [
            'authorize' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'token' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'userinfo' => 'https://graph.microsoft.com/v1.0/me',
        ],
    ];

    private const SCOPE = 'openid email profile';

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureKnownProvider($provider);

        $clientId = (string) config("services.{$provider}.client_id", '');
        $clientSecret = (string) config("services.{$provider}.client_secret", '');
        $redirectUri = (string) config("services.{$provider}.redirect", '');

        if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
            return redirect()->route('login')->withErrors(['email' => 'SSO belum dikonfigurasi']);
        }

        $state = Str::random(40);
        $nonce = Str::random(40);

        session()->put('sso.state', $state);
        session()->put('sso.nonce', $nonce);

        $url = self::ENDPOINTS[$provider]['authorize'].'?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'state' => $state,
            'nonce' => $nonce,
        ]);

        return redirect()->away($url);
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureKnownProvider($provider);

        $expected = $request->session()->get('sso.state');
        $given = $request->query('state');

        if (! is_string($expected) || ! is_string($given) || ! hash_equals($expected, $given)) {
            abort(HttpResponse::HTTP_FORBIDDEN, 'State tidak valid');
        }

        $code = $request->query('code');

        if (! is_string($code) || $code === '') {
            return redirect()->route('login')->withErrors(['email' => 'SSO gagal. Coba lagi.']);
        }

        try {
            $tokenResponse = Http::asForm()->post(self::ENDPOINTS[$provider]['token'], [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => (string) config("services.{$provider}.redirect", ''),
                'client_id' => (string) config("services.{$provider}.client_id", ''),
                'client_secret' => (string) config("services.{$provider}.client_secret", ''),
            ]);

            if ($tokenResponse->failed()) {
                return redirect()->route('login')->withErrors(['email' => 'SSO gagal. Coba lagi.']);
            }

            $accessToken = $tokenResponse->json('access_token');

            if (! is_string($accessToken) || $accessToken === '') {
                return redirect()->route('login')->withErrors(['email' => 'SSO gagal. Coba lagi.']);
            }

            $userinfoResponse = Http::withToken($accessToken)->get(self::ENDPOINTS[$provider]['userinfo']);

            if ($userinfoResponse->failed()) {
                return redirect()->route('login')->withErrors(['email' => 'SSO gagal. Coba lagi.']);
            }

            $info = $userinfoResponse->json();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors(['email' => 'SSO gagal. Coba lagi.']);
        }

        $email = $this->extractVerifiedEmail($provider, is_array($info) ? $info : []);

        if ($email === null) {
            abort(HttpResponse::HTTP_FORBIDDEN, 'Email belum terverifikasi.');
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            abort(HttpResponse::HTTP_FORBIDDEN, 'Akun belum terdaftar. Hubungi admin.');
        }

        $request->session()->forget(['sso.state', 'sso.nonce']);

        Auth::login($user, false);
        $request->session()->regenerate();

        try {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'sso.login',
                'model' => User::class,
                'model_id' => $user->id,
                'diff_json' => ['provider' => $provider, 'email' => $email],
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
        }

        return redirect()->intended(route('dashboard'));
    }

    private function ensureKnownProvider(string $provider): void
    {
        if (! array_key_exists($provider, self::ENDPOINTS)) {
            abort(HttpResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * @param  array<string, mixed>  $info
     */
    private function extractVerifiedEmail(string $provider, array $info): ?string
    {
        if ($provider === 'google') {
            $email = $info['email'] ?? null;
            $verified = $info['email_verified'] ?? false;
            $isVerified = $verified === true || $verified === 1 || $verified === 'true' || $verified === '1';

            if (! is_string($email) || $email === '' || ! $isVerified) {
                return null;
            }

            return mb_strtolower(trim($email));
        }

        $email = $info['mail'] ?? $info['userPrincipalName'] ?? null;

        if (! is_string($email) || ! str_contains($email, '@')) {
            return null;
        }

        return mb_strtolower(trim($email));
    }
}
