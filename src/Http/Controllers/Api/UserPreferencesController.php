<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * User Preferences API Controller.
 *
 * Handles user preference updates including theme, locale, and other settings.
 */
class UserPreferencesController extends Controller
{
    /**
     * Update user theme preference.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateTheme(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'theme' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $themeName = $request->input('theme');

        // Validate theme exists
        $themeManager = app('canvastack.theme');
        if (!$themeManager->has($themeName)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid theme',
                'error' => "Theme '{$themeName}' does not exist",
            ], 400);
        }

        try {
            // Update user preference
            $user = Auth::user();

            if ($user) {
                // Store in user preferences (assuming preferences column exists)
                $preferences = $user->preferences ?? [];
                $preferences['theme'] = $themeName;
                $user->preferences = $preferences;
                $user->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Theme preference updated successfully',
                    'data' => [
                        'theme' => $themeName,
                        'updated_at' => now()->toIso8601String(),
                    ],
                ]);
            }

            // Guest user - only localStorage will be used
            return response()->json([
                'success' => true,
                'message' => 'Theme preference saved (guest mode)',
                'data' => [
                    'theme' => $themeName,
                    'guest' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update theme preference',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user theme preference.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTheme(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user) {
                $preferences = $user->preferences ?? [];
                $theme = $preferences['theme'] ?? config('canvastack-ui.theme.active', 'gradient');

                return response()->json([
                    'success' => true,
                    'data' => [
                        'theme' => $theme,
                    ],
                ]);
            }

            // Guest user
            return response()->json([
                'success' => true,
                'data' => [
                    'theme' => config('canvastack-ui.theme.active', 'gradient'),
                    'guest' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get theme preference',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user locale preference.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateLocale(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'locale' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $locale = $request->input('locale');

        // Validate locale exists
        $availableLocales = config('app.available_locales', ['en', 'id']);
        if (!in_array($locale, $availableLocales)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid locale',
                'error' => "Locale '{$locale}' is not supported",
            ], 400);
        }

        try {
            // Update user preference
            $user = Auth::user();

            if ($user) {
                $preferences = $user->preferences ?? [];
                $preferences['locale'] = $locale;
                $user->preferences = $preferences;
                $user->save();

                // Update session locale
                session(['locale' => $locale]);
                app()->setLocale($locale);

                return response()->json([
                    'success' => true,
                    'message' => 'Locale preference updated successfully',
                    'data' => [
                        'locale' => $locale,
                        'updated_at' => now()->toIso8601String(),
                    ],
                ]);
            }

            // Guest user
            session(['locale' => $locale]);
            app()->setLocale($locale);

            return response()->json([
                'success' => true,
                'message' => 'Locale preference saved (guest mode)',
                'data' => [
                    'locale' => $locale,
                    'guest' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update locale preference',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all user preferences.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPreferences(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user) {
                $preferences = $user->preferences ?? [];

                return response()->json([
                    'success' => true,
                    'data' => [
                        'theme' => $preferences['theme'] ?? config('canvastack-ui.theme.active', 'gradient'),
                        'locale' => $preferences['locale'] ?? config('app.locale', 'en'),
                        'dark_mode' => $preferences['dark_mode'] ?? false,
                        'preferences' => $preferences,
                    ],
                ]);
            }

            // Guest user
            return response()->json([
                'success' => true,
                'data' => [
                    'theme' => config('canvastack-ui.theme.active', 'gradient'),
                    'locale' => config('app.locale', 'en'),
                    'dark_mode' => false,
                    'guest' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get preferences',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update multiple user preferences at once.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'theme' => 'sometimes|string|max:50',
            'locale' => 'sometimes|string|max:10',
            'dark_mode' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $preferences = $user ? ($user->preferences ?? []) : [];

            // Update theme
            if ($request->has('theme')) {
                $themeName = $request->input('theme');
                $themeManager = app('canvastack.theme');

                if (!$themeManager->has($themeName)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid theme',
                    ], 400);
                }

                $preferences['theme'] = $themeName;
            }

            // Update locale
            if ($request->has('locale')) {
                $locale = $request->input('locale');
                $availableLocales = config('app.available_locales', ['en', 'id']);

                if (!in_array($locale, $availableLocales)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid locale',
                    ], 400);
                }

                $preferences['locale'] = $locale;
                session(['locale' => $locale]);
                app()->setLocale($locale);
            }

            // Update dark mode
            if ($request->has('dark_mode')) {
                $preferences['dark_mode'] = $request->boolean('dark_mode');
            }

            // Save preferences
            if ($user) {
                $user->preferences = $preferences;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Preferences updated successfully',
                'data' => [
                    'preferences' => $preferences,
                    'updated_at' => now()->toIso8601String(),
                    'guest' => !$user,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
