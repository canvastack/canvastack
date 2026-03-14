<?php

namespace Canvastack\Canvastack\Http\Controllers;

use Canvastack\Canvastack\Library\Components\MetaTags;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class PublicController extends Controller
{
    /**
     * Display homepage.
     */
    public function home(MetaTags $meta): View
    {
        $meta->title('Home')->description('Modern Laravel CMS Package');
        
        return view('canvastack::public.home', compact('meta'));
    }
    
    /**
     * Display about page.
     */
    public function about(MetaTags $meta): View
    {
        $meta->title('About')->description('About CanvaStack CMS Package');
        
        return view('canvastack::public.about', compact('meta'));
    }
}
