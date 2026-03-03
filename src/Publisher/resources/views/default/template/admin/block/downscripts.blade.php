<?php
/**
 * Created on 10 Mar 2021
 * Time Created	: 10:28:28
 *
 * @filesource	downscripts.blade.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */

$scripts = [];
$scripts['bottom_first'] = [];
if (!empty($components->template->scripts['js']['bottom_first'])) $scripts['bottom_first'] = $components->template->scripts['js']['bottom_first'];
$scripts['bottom']       = [];
if (!empty($components->template->scripts['js']['bottom'])) $scripts['bottom'] = $components->template->scripts['js']['bottom'];
$scripts['bottom_last']  = [];
if (!empty($components->template->scripts['js']['bottom_last'])) $scripts['bottom_last'] = $components->template->scripts['js']['bottom_last'];
?>
	<!-- JS -->
	@foreach ($scripts['bottom_first'] as $script)
	{!! $script->html !!}
	@endforeach
    
	@foreach ($scripts['bottom'] as $script)
	{!! $script->html !!}
	@endforeach
    
	@foreach ($scripts['bottom_last'] as $script)
	{!! $script->html !!}
	@endforeach