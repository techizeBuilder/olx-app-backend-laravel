<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CustomField;
use App\Models\Item;
use App\Models\User;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

class HomeController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth')->except([
            'sendPasswordResetOtp',
            'verifyPasswordResetOtp',
            'updatePasswordWithOtp',
        ]);
    }


    public function index() {
        $items = Cache::remember('dashboard_map_items', now()->addMinutes(10), function () {
            return Item::select('id', 'name', 'price', 'latitude', 'longitude', 'city', 'state', 'country')
                ->where('clicks', '>', 0)
                ->where('status', 'approved')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->with('gallery_images')
                ->latest()
                ->limit(500)
                ->get();
        });
        $categories = Category::withCount('items')->with('translations')->whereHas('items')->get();

        $category_name = array();
        $category_item_count = array();

        foreach ($categories as $value) {
            $category_name[] = "'" . $value->translated_name . "'";
            $category_item_count[] = $value->items_count;
        }

        $categories_count = Category::count();
        $user_count = User::role('User')->withTrashed()->count();
        $item_count = Item::withTrashed()->count();
        $custom_field_count = CustomField::count();
        // $items = Item::all();
        return view('home', compact('category_item_count', 'category_name', 'categories_count', 'item_count', 'user_count', 'custom_field_count','items'));
    }

    public function changePasswordIndex() {
        $this->clearPasswordResetState();

        return view('change_password.index', [
            'otpResetState' => $this->emptyPasswordResetState(Auth::user()->email ?? ''),
        ]);
    }


    public function changePasswordUpdate(Request $request) {
        $validator = Validator::make($request->all(), [
            'old_password'     => 'required',
            'new_password'     => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = Auth::user();
            if (!Hash::check($request->old_password, Auth::user()->password)) {
                ResponseService::errorResponse("Incorrect old password");
            }
            $user->password = Hash::make($request->confirm_password);
            $user->update();
            ResponseService::successResponse('Password Change Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "HomeController --> changePasswordUpdate");
            ResponseService::errorResponse();
        }


    }

    public function sendPasswordResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $email = trim((string) $request->email);
            $user = $this->getPasswordResetUserByEmail($email);

            if (! $user) {
                ResponseService::validationError('Reset password works only on your valid admin email.');
            }

            $otp = (string) random_int(100000, 999999);
            $expiresAt = now()->addMinutes(10);

            Mail::html(
                '<p>Your admin password reset OTP is <strong>' . e($otp) . '</strong>.</p><p>This OTP will expire in 10 minutes.</p>',
                function ($message) use ($email, $user) {
                    $message->to($email, $user->name ?? 'Admin')
                        ->subject('Admin Password Reset OTP');
                }
            );

            $this->storePasswordResetState([
                'user_id' => $user->id,
                'email' => $email,
                'otp' => Hash::make($otp),
                'expires_at' => $expiresAt->timestamp,
                'verified' => false,
                'verified_at' => null,
            ]);

            ResponseService::successResponse('OTP sent successfully.');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'HomeController --> sendPasswordResetOtp');
            ResponseService::errorResponse('Unable to send OTP. Please verify SMTP settings are configured correctly.');
        }
    }

    public function verifyPasswordResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric|digits:6',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $state = session('admin_password_reset');
            if (! $this->isValidResetState($state)) {
                $this->clearPasswordResetState();
                ResponseService::validationError('OTP session expired.');
            }

            if (! Hash::check((string) $request->otp, $state['otp'])) {
                ResponseService::validationError('Invalid OTP.');
            }

            $state['verified'] = true;
            $state['verified_at'] = now()->timestamp;
            $this->storePasswordResetState($state);

            ResponseService::successResponse('OTP Verified Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'HomeController --> verifyPasswordResetOtp');
            ResponseService::errorResponse();
        }
    }

    public function updatePasswordWithOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
            'confirm_password' => 'required|same:new_password',
        ], [
            'new_password.min' => __('The new password must be at least :min characters.'),
            'new_password.regex' => __('The new password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'),
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $state = session('admin_password_reset');
            if (! $this->isValidResetState($state) || empty($state['verified'])) {
                $this->clearPasswordResetState();
                ResponseService::validationError('Please verify OTP before resetting password.');
            }

            $user = User::find($state['user_id'] ?? 0);
            if (! $user || strcasecmp($user->email ?? '', $state['email'] ?? '') !== 0 || $user->hasRole('User')) {
                $this->clearPasswordResetState();
                ResponseService::validationError('Reset password works only on your valid admin email.');
            }

            $user->password = Hash::make($request->confirm_password);
            $user->update();

            $this->clearPasswordResetState();

            ResponseService::successResponse('Password Change Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'HomeController --> updatePasswordWithOtp');
            ResponseService::errorResponse();
        }
    }

    private function getPasswordResetState(): array
    {
        $state = session('admin_password_reset');
        if (! $this->isValidResetState($state)) {
            session()->forget('admin_password_reset');

            return [
                'sent' => false,
                'verified' => false,
                'email' => Auth::user()->email ?? '',
            ];
        }

        return [
            'sent' => true,
            'verified' => ! empty($state['verified']),
            'email' => $state['email'] ?? (Auth::user()->email ?? ''),
        ];
    }

    private function emptyPasswordResetState(string $email = ''): array
    {
        return [
            'sent' => false,
            'verified' => false,
            'email' => $email,
        ];
    }

    private function isValidResetState($state): bool
    {
        return ! empty($state['user_id']) &&
            ! empty($state['email']) &&
            ! empty($state['otp']) &&
            ! empty($state['expires_at']) &&
            now()->timestamp <= (int) $state['expires_at'];
    }

    private function storePasswordResetState(array $state): void
    {
        session(['admin_password_reset' => $state]);
        session()->save();
    }

    private function clearPasswordResetState(): void
    {
        session()->forget('admin_password_reset');
        session()->save();
    }

    private function getPasswordResetUserByEmail(string $email): ?User
    {
        $user = User::where('email', $email)->first();

        if (! $user || $user->hasRole('User')) {
            return null;
        }

        return $user;
    }


    public function changeProfileIndex() {
        return view('change_profile.index');
    }

    public function changeProfileUpdate(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'    => 'required',
            'email'   => 'required|email|unique:users,email,' . Auth::user()->id,
            'profile' => 'nullable|mimes:jpeg,jpg,png'
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = Auth::user();
            $data = [
                'name'  => $request->name,
                'email' => $request->email
            ];
            if ($request->hasFile('profile')) {
                $data['profile'] = $request->file('profile')->store('admin_profile', 'public');
            }
            $user->update($data);
            ResponseService::successResponse('Profile Updated Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "HomeController --> updateProfile");
            ResponseService::errorResponse();
        }

    }
    public function getMapsData()
    {
        $apiKey = env('PLACE_API_KEY');

        $url = "https://maps.googleapis.com/maps/api/js?" . http_build_query([
            'libraries' => 'places',
            'key' => $apiKey, // Use the API key from the .env file
            // Add any other parameters you need here
        ]);

        return file_get_contents($url);
    }
}
