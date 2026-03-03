<?php

namespace Canvastack\Canvastack\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * SoftDeleteController - Handles restore and permanent delete operations.
 *
 * Provides endpoints for restoring and permanently deleting soft-deleted records.
 *
 * Requirements: 8.7, 8.8, 8.9
 */
class SoftDeleteController
{
    /**
     * Restore a soft-deleted record.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * Requirements: 8.7, 8.8
     */
    public function restore(Request $request): JsonResponse
    {
        try {
            $modelClass = base64_decode($request->input('model_class'));
            $modelId = $request->input('model_id');

            // Validate model class exists
            if (!class_exists($modelClass)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid model class',
                ], 400);
            }

            // Find the soft-deleted record
            $model = $modelClass::withTrashed()->find($modelId);

            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record not found',
                ], 404);
            }

            // Check if record is actually soft deleted
            if (!$model->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record is not deleted',
                ], 400);
            }

            // Restore the record
            $model->restore();

            Log::info('Soft-deleted record restored', [
                'model' => $modelClass,
                'id' => $modelId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Record restored successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error restoring soft-deleted record', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while restoring the record',
            ], 500);
        }
    }

    /**
     * Permanently delete a soft-deleted record.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * Requirements: 8.9
     */
    public function forceDelete(Request $request): JsonResponse
    {
        try {
            $modelClass = base64_decode($request->input('model_class'));
            $modelId = $request->input('model_id');

            // Validate model class exists
            if (!class_exists($modelClass)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid model class',
                ], 400);
            }

            // Find the soft-deleted record
            $model = $modelClass::withTrashed()->find($modelId);

            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record not found',
                ], 404);
            }

            // Check if record is actually soft deleted
            if (!$model->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record must be soft deleted before permanent deletion',
                ], 400);
            }

            // Permanently delete the record
            $model->forceDelete();

            Log::warning('Record permanently deleted', [
                'model' => $modelClass,
                'id' => $modelId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Record permanently deleted',
                'redirect' => $this->getRedirectUrl($modelClass),
            ]);
        } catch (\Exception $e) {
            Log::error('Error permanently deleting record', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the record',
            ], 500);
        }
    }

    /**
     * Get redirect URL after permanent deletion.
     *
     * @param string $modelClass
     * @return string
     */
    protected function getRedirectUrl(string $modelClass): string
    {
        // Try to determine a sensible redirect URL based on model class
        $modelName = class_basename($modelClass);
        $route = strtolower(str_replace('_', '-', snake_case($modelName)));

        // Default to admin index route
        return url("/admin/{$route}");
    }
}
