<?php
/**
 * Created on 10 Mar 2021
 * Time Created	: 10:24:14
 *
 * @filesource	footer.blade.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */

// Robust string normalizer for any value
$__toString = function ($value): string {
    if (is_string($value) || is_numeric($value)) return (string) $value;
    if (is_array($value)) {
        // Convert nested arrays/objects to JSON then join
        $parts = array_map(function ($v) {
            if (is_string($v) || is_numeric($v)) return (string) $v;
            return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $value);
        return implode(', ', $parts);
    }
    if (is_object($value)) {
        if (method_exists($value, '__toString')) return (string) $value;
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return '';
};

$components    = isset($components) && is_object($components) ? $components : null;
$prefs         = (isset($components) && isset($components->meta) && isset($components->meta->preference)) ? $components->meta->preference : [];

$author        = function_exists('canvastack_config') ? canvastack_config('meta_author') : (config('app.author') ?? '');
if (!empty($prefs['meta_author']))   $author = $prefs['meta_author'];
$author        = $__toString($author);

$copyright     = function_exists('canvastack_config') ? canvastack_config('copyrights') : (config('app.name') ?? '');
if (!empty($prefs['meta_author']))   $copyright = $prefs['meta_author'];
$copyright     = $__toString($copyright);

$email_address = function_exists('canvastack_config') ? canvastack_config('email') : (config('mail.from.address') ?? '');
if (!empty($prefs['email_address'])) $email_address = $prefs['email_address'];
$email_address = $__toString($email_address);

$location      = function_exists('canvastack_config') ? canvastack_config('location') : '';
$location      = $__toString($location);
$location_abbr = function_exists('canvastack_config') ? canvastack_config('location_abbr') : '';
$location_abbr = $__toString($location_abbr);
?>
				<!-- FOOTER OPEN  -->
				<footer>
					<div class="footer-area blury">
						<span class="pull-right">
							<span id="copyright"></span>&nbsp;
							<font title="{{ $author }} &lt;{{ $email_address }}&gt;">&copy;</font>&nbsp;
							<a href="mailto:{{ $email_address }}" target="_blank">{{ $copyright }}</a>, {{ $location }} {{ $location_abbr }}
						</span>
					</div>
				</footer>
				<!-- FOOTER CLOSE -->