<?php

namespace Canvastack\Canvastack\Core\Craft\Action;

use Illuminate\Support\Facades\Log;
use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

/**
 * Validation Handler Trait
 * 
 * Handles validation rules, error handling, and form validation
 * Extracted from Action.php for better organization
 */
trait ValidationHandler
{
    private static $validation_messages = [];
    private static $validation_rules = [];

    /**
     * Set Validation Data
     *
     * @param  array  $roles
     * @param  array  $on_update
     */
    public function setValidations($roles = [], $on_update = [])
    {
        $this->validations = $roles;
        if (! empty($on_update) && canvastack_array_contained_string(['edit', 'update'], explode('.', current_route()))) {
            unset($this->validations);
            $this->validations = $on_update;
        }
        $this->form->setValidations($this->validations);
    }

    /**
     * Redirect with validation messages.
     *
     * @param string $to
     * @param mixed $message_data
     * @param bool $status_info
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function redirect($to, $message_data = [], $status_info = true)
    {
        $message = null;
        if (! empty($message_data)) {
            if (is_object($message_data) && 'Request' === class_basename($message_data)) {
                if ($message_data->allFiles()) {
                    $message = $message_data->all();
                    $files = [];
                    foreach ($message_data->allFiles() as $filename => $filedata) {
                        $files[$filename] = $filedata;
                        unset($message[$filename]);
                    }
                    // Files Need Re-Check Again!!!
                    
                    // Environment-aware logging for file processing
                    if (app()->environment(['local', 'testing'])) {
                        SafeLogger::debug('ValidationHandler: Processing file uploads', [
                            'files_count' => count($files),
                            'file_names' => array_keys($files)
                        ]);
                    }
                } else {
                    $message = $message_data->all();
                    
                    // Environment-aware logging for request data processing
                    if (app()->environment(['local', 'testing'])) {
                        SafeLogger::debug('ValidationHandler: Processing request data', [
                            'data_keys' => array_keys($message),
                            'data_count' => count($message)
                        ]);
                    }
                }
            } else {
                $message = $message_data;
                
                // Environment-aware logging for message data
                if (app()->environment(['local', 'testing'])) {
                    SafeLogger::debug('ValidationHandler: Processing message data', [
                        'message_type' => gettype($message_data),
                        'is_array' => is_array($message_data),
                        'data_count' => is_array($message_data) ? count($message_data) : 1
                    ]);
                }
            }
        }

        $route = null;
        $currentRoute = current_route();
        $routeArray = explode('.', $currentRoute);
        $lastRoute = last($routeArray);

        if ('store' === $to) {
            $route = str_replace('.'.$lastRoute, '.create', $currentRoute);
        } elseif ('update' === $to) {
            $route = str_replace('.'.$lastRoute, '.edit', $currentRoute);
        } elseif ('edit' === $to) {
            $route = str_replace('.'.$lastRoute, '.edit', $currentRoute);
        } else {
            $route = str_replace('.'.$lastRoute, '.'.$to, $currentRoute);
        }

        $route = str_replace('.', '/', $route);

        // Environment-aware logging for redirect operations
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ValidationHandler: Redirecting after validation', [
                'target_route' => $route,
                'status_info' => $status_info,
                'has_message' => !empty($message),
                'redirect_type' => $status_info ? 'success' : 'error'
            ]);
        }

        if (true === $status_info) {
            if (! empty($message)) {
                return redirect($route)->with('success', $message);
            } else {
                return redirect($route)->with('success', 'Data has been saved successfully!');
            }
        } else {
            if (! empty($message)) {
                return redirect($route)->withErrors($message)->withInput();
            } else {
                return redirect($route)->with('error', 'Failed to save data. Please try again.');
            }
        }
    }

    /**
     * Redirect page after login
     *
     * created @Aug 18, 2018
     * author: wisnuwidi
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function firstRedirect()
    {
        $group_id = null;
        if (! empty($this->session_auth['group_id'])) {
            $group_id = intval($this->session_auth['group_id']);
        }
        
        // Environment-aware logging for login redirect
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ValidationHandler: First redirect after login', [
                'group_id' => $group_id,
                'is_root_group' => (1 === intval($group_id)),
                'has_session_auth' => !empty($this->session_auth)
            ]);
        }
        
        if (1 === intval($group_id)) {
            // root group as internal
            return redirect()->intended($this->rootPage);
        } else {
            // admin and/or another group except root group as external
            return redirect()->intended($this->adminPage);
        }
    }
}