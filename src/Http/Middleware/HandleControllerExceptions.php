<?php
namespace Canvastack\Canvastack\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Canvastack\Canvastack\Exceptions\Controller\ControllerException;
use Canvastack\Canvastack\Exceptions\Controller\FileUploadException;

/**
 * Handle Controller Exceptions Middleware
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Middleware untuk menangani exception dari Controller components
 * dan menampilkan error dalam format yang user-friendly.
 * 
 * Fitur:
 * - Menangkap ControllerException dan sub-classes
 * - Menampilkan modal alert untuk error (jika dikonfigurasi)
 * - Fallback ke Laravel default error handling
 * - Logging error untuk debugging
 * 
 * Konfigurasi:
 * - canvastack.controller.error_handling.show_modal_alert: Enable/disable modal alert
 * - canvastack.controller.error_handling.modal_alert_title: Judul modal
 * - canvastack.controller.error_handling.modal_alert_button_text: Text tombol
 * 
 * @package Canvastack\Canvastack\Http\Middleware
 * @category Middleware
 * @version 1.0.0
 */
class HandleControllerExceptions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (ControllerException $e) {
            // Check if modal alert is enabled
            $showModalAlert = config('canvastack.controller.error_handling.show_modal_alert', true);
            
            if ($showModalAlert) {
                return $this->renderModalAlert($e, $request);
            } else {
                // Let Laravel handle it normally
                throw $e;
            }
        }
    }
    
    /**
     * Render modal alert for exception
     * 
     * @param ControllerException $exception
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    protected function renderModalAlert(ControllerException $exception, Request $request)
    {
        // Get configuration
        $title = config('canvastack.controller.error_handling.modal_alert_title', 'File Upload Error');
        $buttonText = config('canvastack.controller.error_handling.modal_alert_button_text', 'Close');
        
        // Get user-friendly message
        $message = $exception->getUserMessage();
        
        // Get error type for styling
        $errorType = $this->getErrorType($exception);
        
        // Get additional details for display
        $details = $this->getErrorDetails($exception);
        
        // Check if this is an AJAX request
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => $message,
                'error_type' => $errorType,
                'details' => $details,
                'code' => $exception->getCode(),
            ], $exception->getCode() ?: 500);
        }
        
        // Render HTML with modal alert
        $html = $this->generateModalAlertHtml($title, $message, $buttonText, $errorType, $details);
        
        return response($html, $exception->getCode() ?: 500);
    }
    
    /**
     * Get error details for display
     * 
     * @param ControllerException $exception
     * @return array
     */
    protected function getErrorDetails(ControllerException $exception): array
    {
        $details = [];
        
        if ($exception instanceof FileUploadException) {
            $fileDetails = $exception->getFileDetails();
            
            // File details section
            $details['file_details'] = [];
            if (!empty($fileDetails['filename']) && $fileDetails['filename'] !== 'unknown') {
                $details['file_details']['File Name'] = $fileDetails['filename'];
            }
            if (!empty($fileDetails['size']) && $fileDetails['size'] > 0) {
                $details['file_details']['File Size'] = $this->formatFileSize($fileDetails['size']);
            }
            if (!empty($fileDetails['extension']) && $fileDetails['extension'] !== 'unknown') {
                $details['file_details']['File Type'] = $fileDetails['extension'];
            }
            
            // Requirements section
            $context = $exception->getContext();
            $details['requirements'] = [];
            
            if (!empty($context['allowed_types'])) {
                $details['requirements']['Allowed Types'] = $context['allowed_types'];
            }
            if (!empty($context['max_size'])) {
                $details['requirements']['Maximum Size'] = $context['max_size'];
            }
        }
        
        return $details;
    }
    
    /**
     * Format file size for display
     * 
     * @param int $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
    
    /**
     * Get error type for styling
     * 
     * @param ControllerException $exception
     * @return string
     */
    protected function getErrorType(ControllerException $exception): string
    {
        if ($exception instanceof FileUploadException) {
            $uploadErrorType = $exception->getUploadErrorType();
            
            switch ($uploadErrorType) {
                case 'malware':
                case 'security':
                    return 'danger';
                case 'size':
                case 'type':
                case 'validation':
                    return 'warning';
                case 'timeout':
                case 'concurrent_limit':
                    return 'info';
                default:
                    return 'error';
            }
        }
        
        // Default error type
        return 'error';
    }
    
    /**
     * Generate modal alert HTML using existing Bootstrap modal design
     * 
     * This uses the same modal structure as the client-side file upload validator
     * to maintain consistency across the application.
     * 
     * @param string $title
     * @param string $message
     * @param string $buttonText
     * @param string $errorType
     * @param array $details Additional details to display
     * @return string
     */
    protected function generateModalAlertHtml(string $title, string $message, string $buttonText, string $errorType, array $details = []): string
    {
        // Get icon based on error type
        $iconClass = $this->getErrorIconClass($errorType);
        
        // Build details HTML if provided
        $detailsHtml = '';
        if (!empty($details)) {
            $detailsHtml = '<div class="file-error-details mt-3">';
            
            if (!empty($details['file_details'])) {
                $detailsHtml .= '<h6>File Details:</h6><ul class="list-unstyled">';
                foreach ($details['file_details'] as $label => $value) {
                    $detailsHtml .= '<li><strong>' . htmlspecialchars($label) . ':</strong> ' . htmlspecialchars($value) . '</li>';
                }
                $detailsHtml .= '</ul>';
            }
            
            if (!empty($details['requirements'])) {
                $detailsHtml .= '<h6>Requirements:</h6><ul class="list-unstyled">';
                foreach ($details['requirements'] as $label => $value) {
                    $detailsHtml .= '<li><strong>' . htmlspecialchars($label) . ':</strong> ' . htmlspecialchars($value) . '</li>';
                }
                $detailsHtml .= '</ul>';
            }
            
            $detailsHtml .= '</div>';
        }
        
        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .modal {
            display: block;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .modal-dialog {
            margin: 0 auto;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .file-error-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        
        .file-error-details h6 {
            color: #495057;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 8px;
        }
        
        .file-error-details h6:first-child {
            margin-top: 0;
        }
        
        .file-error-details ul {
            margin-bottom: 0;
        }
        
        .file-error-details li {
            padding: 4px 0;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="modal fade show" id="fileUploadErrorModal" tabindex="-1" role="dialog" aria-labelledby="fileUploadErrorModalLabel" aria-hidden="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="fileUploadErrorModalLabel">
                        <i class="{$iconClass}"></i> {$title}
                    </h5>
                    <button type="button" class="close text-white" onclick="goBack()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger mb-3">
                        <strong>{$message}</strong>
                    </div>
                    {$detailsHtml}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="goBack()">{$buttonText}</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/';
            }
        }
        
        // Allow closing with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                goBack();
            }
        });
    </script>
</body>
</html>
HTML;
    }
    
    /**
     * Get error icon class for Font Awesome
     * 
     * @param string $errorType
     * @return string
     */
    protected function getErrorIconClass(string $errorType): string
    {
        switch ($errorType) {
            case 'danger':
            case 'warning':
                return 'fa fa-exclamation-triangle';
            case 'info':
                return 'fa fa-info-circle';
            default:
                return 'fa fa-exclamation-circle';
        }
    }
}
