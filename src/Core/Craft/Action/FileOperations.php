<?php

namespace Canvastack\Canvastack\Core\Craft\Action;

use Illuminate\Http\Request;
use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

/**
 * File Operations Trait
 * 
 * Handles file upload, validation, and processing operations
 * Extracted from Action.php for better organization
 */
trait FileOperations
{
    /**
     * Set Upload Path URL.
     */
    private function setUploadURL()
    {
        $currentRoute = explode('.', current_route());
        unset($currentRoute[array_key_last($currentRoute)]);
        $currentRoute = implode('.', $currentRoute);

        $uploadPath = str_replace('.', '/', str_replace('.'.__FUNCTION__, '', $currentRoute));
        
        // Environment-aware logging for upload path generation
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('FileOperations: Generated upload path', [
                'current_route' => current_route(),
                'upload_path' => $uploadPath
            ]);
        }

        return $uploadPath;
    }

    /**
     * Check If any input type file submitted or not.
     *
     * @param Request $request
     * @return object|\Illuminate\Http\Request
     */
    private function checkFileInputSubmited(Request $request)
    {
        if (! empty($request->files)) {
            // Environment-aware logging for file submission check
            if (app()->environment(['local', 'testing'])) {
                SafeLogger::debug('FileOperations: Checking file submissions', [
                    'files_count' => count($request->files),
                    'file_inputs' => array_keys($request->files->all())
                ]);
            }

            foreach ($request->files as $inputname => $file) {
                if ($request->hasfile($inputname)) {
                    // if any file type submitted
                    $file = $this->fileAttributes;

                    // Environment-aware logging for file processing
                    if (app()->environment(['local', 'testing'])) {
                        SafeLogger::debug('FileOperations: Processing file upload', [
                            'input_name' => $inputname,
                            'has_file_attributes' => !empty($this->fileAttributes)
                        ]);
                    }

                    return $this->uploadFiles($this->setUploadURL(), $request, $file);
                } else {
                    // if no one file type submitted
                    return $request;
                }
            }

            // if no one file type submitted
            return $request;

        } else {
            // if no one file type submitted
            // Environment-aware logging for no files case
            if (app()->environment(['local', 'testing'])) {
                SafeLogger::debug('FileOperations: No files submitted in request');
            }
            
            return $request;
        }
    }
}