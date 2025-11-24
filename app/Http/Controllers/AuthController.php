<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BodyMeasurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class AuthController extends Controller
{
    // User Registration
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'role' => 'required|in:customer,tailor,fabric_seller',
            'shop_name' => 'required_if:role,tailor,fabric_seller|string|max:255',
            'shop_description' => 'nullable|string',
            'shop_address' => 'required_if:role,tailor,fabric_seller|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => $request->role,
                'shop_name' => $request->shop_name,
                'shop_description' => $request->shop_description,
                'shop_address' => $request->shop_address,
                'is_verified' => $request->role === 'customer', // Auto-verify customers
            ]);

            // Generate shop logo for tailors and fabric sellers
            if (in_array($request->role, ['tailor', 'fabric_seller'])) {
                $this->generateDefaultShopLogo($user);
            }

            // Create default body measurement for customers
            if ($request->role === 'customer') {
                BodyMeasurement::create([
                    'user_id' => $user->id,
                    'measurement_name' => 'Default Measurement',
                    'is_default' => true,
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // User Login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();
            
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is deactivated. Please contact support.'
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // User Logout
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get Current User Profile
    public function profile(Request $request)
    {
        try {
            $user = $request->user();
            $user->load(['bodyMeasurements', 'defaultBodyMeasurement']);

            return response()->json([
                'success' => true,
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update User Profile
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'shop_name' => 'required_if:role,tailor,fabric_seller|string|max:255',
            'shop_description' => 'nullable|string',
            'shop_address' => 'required_if:role,tailor,fabric_seller|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'shop_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = [
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
            ];

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    Storage::delete('public/' . $user->avatar);
                }
                
                $avatarPath = $this->handleImageUpload($request->file('avatar'), 'avatars');
                $updateData['avatar'] = $avatarPath;
            }

            // Handle shop logo upload for tailors/fabric sellers
            if (in_array($user->role, ['tailor', 'fabric_seller'])) {
                $updateData['shop_name'] = $request->shop_name;
                $updateData['shop_description'] = $request->shop_description;
                $updateData['shop_address'] = $request->shop_address;

                if ($request->hasFile('shop_logo')) {
                    if ($user->shop_logo) {
                        Storage::delete('public/' . $user->shop_logo);
                    }
                    
                    $shopLogoPath = $this->handleImageUpload($request->file('shop_logo'), 'shop-logos');
                    $updateData['shop_logo'] = $shopLogoPath;
                }
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Change Password
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 422);
            }

            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password change failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete Account
    public function deleteAccount(Request $request)
    {
        try {
            $user = $request->user();

            // Soft delete user
            $user->delete();

            // Revoke all tokens
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Account deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get User Statistics
    public function getUserStats(Request $request)
    {
        try {
            $user = $request->user();
            $stats = [];

            switch ($user->role) {
                case 'customer':
                    $stats = [
                        'total_orders' => $user->customerOrders()->count(),
                        'pending_orders' => $user->customerOrders()->whereIn('status', ['pending', 'payment_pending', 'paid'])->count(),
                        'completed_orders' => $user->customerOrders()->where('status', 'completed')->count(),
                        'total_designs' => $user->customDesigns()->count(),
                        'active_designs' => $user->customDesigns()->where('status', 'completed')->count(),
                    ];
                    break;

                case 'tailor':
                    $stats = [
                        'total_orders' => $user->tailorOrders()->count(),
                        'active_orders' => $user->tailorOrders()->whereIn('status', ['in_production', 'quality_check'])->count(),
                        'completed_orders' => $user->tailorOrders()->where('status', 'completed')->count(),
                        'pending_assignments' => $user->tailorAssignments()->where('status', 'assigned')->count(),
                        'total_earnings' => $user->tailorOrders()->where('payment_status', 'paid')->sum('tailoring_cost'),
                    ];
                    break;

                case 'fabric_seller':
                    $stats = [
                        'total_fabrics' => $user->fabrics()->count(),
                        'active_fabrics' => $user->fabrics()->where('is_active', true)->count(),
                        'total_orders' => $user->fabricOrders()->count(),
                        'pending_orders' => $user->fabricOrders()->where('status', 'pending')->count(),
                        'total_revenue' => $user->fabricOrders()->where('status', 'delivered_to_tailor')->sum('total_price'),
                    ];
                    break;
            }

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Private helper methods
    private function handleImageUpload($file, $directory)
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Resize and optimize image
        $image = Image::make($file);
        
        if ($directory === 'avatars') {
            $image->fit(200, 200); // Square crop for avatars
        } else {
            $image->resize(800, 800, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        
        $path = $directory . '/' . $filename;
        Storage::put('public/' . $path, $image->encode());
        
        return $path;
    }

    private function generateDefaultShopLogo($user)
    {
        $canvas = Image::canvas(400, 400, '#4F46E5'); // Indigo background
        
        // Add shop name initials
        $initials = strtoupper(substr($user->shop_name, 0, 2));
        $canvas->text($initials, 200, 200, function($font) {
            $font->file(public_path('fonts/Roboto-Bold.ttf'));
            $font->size(120);
            $font->color('#FFFFFF');
            $font->align('center');
            $font->valign('middle');
        });
        
        $filename = 'shop-logos/' . Str::uuid() . '.png';
        Storage::put('public/' . $filename, $canvas->encode('png'));
        
        $user->update(['shop_logo' => $filename]);
    }
}
