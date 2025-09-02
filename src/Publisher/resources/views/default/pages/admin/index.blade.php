<?php
/**
 * Created on 10 Mar 2021
 * Time Created	: 10:01:45
 *
 * @filesource	index.blade.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
?>

@extends('default.template.admin.index')

@section('content')
	
    @foreach($content_page as $key => $content)
    	@if (!is_array($content))
    	{!! $content !!}
    	@else
    		@if ('charts' === $key)
    			@foreach ($content as $chart)
    				{!! $chart->container() !!}
    				{!! $chart->script() !!}
    			@endforeach
    		@endif
    	@endif
    @endforeach
    
@endsection


