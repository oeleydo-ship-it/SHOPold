<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cart;
use App\Models\EmailVerificationToken;
use App\Mail\VerifyEmail;
use App\Mail\WelcomeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Merge guest cart with user cart
            $this->mergeGuestCart();

            if (Auth::user()->is_admin) {
                return redirect()->intended(route('admin.dashboard'));
            }

            return redirect()->intended(route('home'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegisterForm()
    {
        // Check if registration is allowed
        if (!setting('system.allow_registration', true)) {
            return redirect()->route('login')
                ->with('error', 'Registration is currently disabled. Please contact the administrator.');
        }
        
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Check if registration is allowed
        if (!setting('system.allow_registration', true)) {
            return redirect()->route('login')
                ->with('error', 'Registration is currently disabled.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);
        
        // Merge guest cart
        $this->mergeGuestCart();

        // Send verification email if enabled
        if (setting('system.require_email_verification', false)) {
            $emailSent = $this->sendVerificationEmail($user);
            if ($emailSent) {
                return redirect()->route('verification.notice')
                    ->with('success', 'Registration successful! Please check your email to verify your account.');
            } else {
                // Email failed but user is registered - mark as verified to avoid blocking
                $user->markEmailAsVerified();
                // Still send welcome email
                $this->sendWelcomeEmail($user);
                return redirect()->route('home')
                    ->with('warning', 'Registration successful! Email verification could not be sent, but your account is active.');
            }
        } else {
            // No verification required - send welcome email
            $this->sendWelcomeEmail($user);
        }

        return redirect()->route('home')->with('success', 'Registration successful! Welcome to our shop.');
    }

    /**
     * Show the email verification notice.
     */
    public function showVerificationNotice()
    {
        if (auth()->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        return view('auth.verify-email');
    }

    /**
     * Verify the user's email address.
     */
    public function verifyEmail(Request $request, string $token)
    {
        $verificationToken = EmailVerificationToken::where('token', $token)->first();

        if (!$verificationToken) {
            return redirect()->route('login')
                ->with('error', 'Invalid verification link.');
        }

        if ($verificationToken->isExpired()) {
            $verificationToken->delete();
            return redirect()->route('verification.notice')
                ->with('error', 'Verification link has expired. Please request a new one.');
        }

        $user = $verificationToken->user;
        $user->markEmailAsVerified();
        $verificationToken->delete();

        // Log in the user if not already logged in
        if (!auth()->check()) {
            Auth::login($user);
        }

        return redirect()->route('home')
            ->with('success', 'Email verified successfully! Welcome to our shop.');
    }

    /**
     * Resend the email verification link.
     */
    public function resendVerificationEmail(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $emailSent = $this->sendVerificationEmail($user);

        if ($emailSent) {
            return back()->with('success', 'Verification link sent! Please check your email.');
        } else {
            return back()->with('error', 'Failed to send verification email. Please check your email settings or try again later.');
        }
    }

    /**
     * Send verification email to user.
     */
    protected function sendVerificationEmail(User $user): bool
    {
        try {
            $token = EmailVerificationToken::generateFor($user);
            $verificationUrl = route('verification.verify', ['token' => $token->token]);

            // Send immediately, not queued, to catch errors
            Mail::to($user->email)->send(new VerifyEmail($user, $verificationUrl));
            
            Log::info('Verification email sent successfully to: ' . $user->email);
            return true;
        } catch (\Exception $e) {
            // Log the error but don't crash registration
            Log::error('Failed to send verification email to ' . $user->email . ': ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Send welcome email to user.
     */
    protected function sendWelcomeEmail(User $user): void
    {
        try {
            if (!setting('email.welcome_email', true)) {
                return;
            }

            Mail::to($user->email)->send(new WelcomeEmail($user));
            Log::info('Welcome email sent successfully to: ' . $user->email);
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email to ' . $user->email . ': ' . $e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    protected function mergeGuestCart()
    {
        $sessionId = session()->getId();
        $guestCart = Cart::where('session_id', $sessionId)->first();
        
        if ($guestCart && $guestCart->items->count() > 0) {
            $userCart = Cart::firstOrCreate(['user_id' => auth()->id()]);
            
            foreach ($guestCart->items as $item) {
                $existingItem = $userCart->items()->where('product_id', $item->product_id)->first();
                
                if ($existingItem) {
                    $existingItem->increment('quantity', $item->quantity);
                } else {
                    $userCart->items()->create([
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                    ]);
                }
            }
            
            $guestCart->delete();
        }
    }
}
