<?php

namespace Canvastack\Canvastack\Library\Components;

use Canvastack\Canvastack\Models\Admin\System\Preference;

/**
 * Meta Tags Class
 *
 * Created on Jun 4, 2016
 * Time Created : 8:10:51 PM
 *
 * @author      : wisnuwidi @IncoDIY - 2016
 *
 * @link        : wisnuwidi@canvastack.com
 *
 * @copyright   : Wisnu Widiantoko
 */
class MetaTags
{
    public $content;

    public $base_url;

    public $lang;

    public $author;

    public $app_name;

    public $preference;

    public function __construct()
    {
        if (! empty(route('system.config.preference.index'))) {
            $preference = new Preference();
            $prefData = $preference->first()->getAttributes();

            $this->preference['app_name'] = $prefData['title'];
            $this->preference['meta_title'] = $prefData['meta_title'];
            $this->preference['meta_keywords'] = $prefData['meta_keywords'];
            $this->preference['meta_description'] = $prefData['meta_description'];
            $this->preference['meta_author'] = $prefData['meta_author'];
            $this->preference['email_person'] = $prefData['email_person'];
            $this->preference['email_address'] = $prefData['email_address'];
        }

        $this->load_meta();
    }

    private function load_meta()
    {
        $this->getMeta();
        $this->getHtml();
    }

    /**
     * Render Default Meta Tags Data
     */
    public function getMeta($inject = null)
    {
        if (is_null($this->content)) {

            $this->baseURL();
            $this->title();
            $this->charset();
            $this->http_equiv();
            $this->app_name();
            $this->author();
            $this->keywords();
            $this->description();
            $this->language();
            $this->viewport();
        }
    }

    private function getHtml()
    {
        return $this->content['html'];
    }

    private function getText()
    {
        return $this->content['text'];
    }

    private function config($name)
    {
        return canvastack_config("{$name}");
    }

    /**
     * Rendering String
     *
     * created @Aug 21, 2018
     * author: wisnuwidi
     */
    private function renderString($string, $setting_name, $meta_preference_name = false)
    {
        $str = null;
        $_str = null;

        if (true === $meta_preference_name) {
            if (empty($string)) {
                if (! empty($this->preference[$setting_name])) {
                    $str = $this->preference[$setting_name];
                } else {
                    $str = $this->config($setting_name);
                }
            } else {
                if (! empty($this->preference[$setting_name])) {
                    $_str = $this->preference[$setting_name];
                } else {
                    $_str = $this->config($setting_name);
                }

                if ('meta_title' === $setting_name) {
                    $str = $string.' | '.$_str;
                } else {
                    $str = $_str;
                }
            }
        } else {
            if (empty($string)) {
                $str = $this->config($setting_name);
            } else {
                $str = $string;
            }
        }

        return $str;
    }

    public function tags($as = 'html')
    {
        if ('html' === $as) {
            return $this->getHtml();
        } else {
            return $this->getText();
        }
    }

    public $csrf;

    public function csrf($inject)
    {
        $str = $this->renderString($inject, 'csrf');
        $this->csrf = $str;

        $this->content['csrf']['text'] = $inject;
        $this->content['csrf']['html'] = '<meta name="'.__FUNCTION__.'-token" content="'.$inject.'" />';
    }

    public function getMetaText($meta_name)
    {
        return $this->content['text'][$meta_name];
    }

    public function getMetaHTML($meta_name)
    {
        return $this->content['html'][$meta_name];
    }

    /**
     * Render Base URL
     *
     * @param  string  $string
     */
    public function baseURL($string = null)
    {
        $this->base_url = $string;

        if (empty($string)) {
            $this->base_url = $this->config('baseURL');
        }

        $this->content['text']['baseURL'] = $this->base_url;
        $this->content['html']['baseURL'] = '<base href="'.$this->base_url.'" />';
    }

    /**
     * Render Application Name
     *
     * @param  string  $string
     */
    public function app_name($string = null)
    {
        $str = $this->renderString($string, 'app_name', true);
        $this->app_name = $str;

        $this->content['text']['app_name'] = $this->app_name;
        $this->content['html']['app_name'] = '<meta name="'.__FUNCTION__.'" content="'.$this->app_name.'" />';
    }

    /**
     * Render Meta Tag for Language
     *
     * @param  string  $string
     */
    public function language($string = null)
    {
        $this->lang = $string;
        if (empty($string)) {
            $this->lang = $this->config('lang');
        }

        $this->content['text']['lang'] = $this->lang;
        $this->content['html']['lang'] = "<meta http-equiv=\"content-language\" content=\"{$this->lang}\">";
    }

    /**
     * Render Meta Tag for Charset
     *
     * @param  string  $string
     * @param  string  $html
     */
    public function charset($string = null)
    {
        $str = $string;
        if (empty($string)) {
            $str = $this->config('charset');
        }

        $this->content['html']['charset'] = '<meta '.__FUNCTION__.'="'.$str.'" />';
    }

    public function title($string = null)
    {
        $str = $this->renderString($string, 'meta_title', true);

        $this->content['text']['title'] = $str;
        $this->content['html']['title'] = "<title>{$str}</title>";
    }

    /**
     * Render Meta Tag for Author
     *
     * @param  string  $string
     * @param  string  $html
     */
    public function author($string = null)
    {
        $this->author = $this->renderString($string, 'meta_author');

        $this->content['text']['author'] = $this->author;
        $this->content['html']['author'] = '<meta name="'.__FUNCTION__.'" content="'.$this->author.'" />';
    }

    /**
     * Render Meta Tag for Keywords
     *
     * @param  string  $string
     * @param  string  $html
     */
    public function keywords($string = null, $html = true)
    {
        $str = $this->renderString($string, 'meta_keywords', true);

        $this->content['text']['meta_keywords'] = $str;
        $this->content['html']['meta_keywords'] = '<meta name="'.__FUNCTION__.'" content="'.$str.'" />';
    }

    /**
     * Render Meta Tag for Description
     *
     * @param  string  $string
     * @param  string  $html
     */
    public function description($string = null, $html = true)
    {
        $str = $this->renderString($string, 'meta_description', true);

        $this->content['text']['meta_description'] = $str;
        $this->content['html']['meta_description'] = '<meta name="'.__FUNCTION__.'" content="'.$str.'" />';
    }

    /**
     * Render Meta Tag for Viewport
     *
     * @param  string  $string
     * @param  string  $html
     */
    public function viewport($string = null, $html = true)
    {
        $str = $string;
        if (empty($string)) {
            $str = $this->config('meta_viewport');
        }

        $this->content['text']['meta_viewport'] = $str;
        $this->content['html']['meta_viewport'] = '<meta name="'.__FUNCTION__.'" content="'.$str.'" />';
    }

    /**
     * Render Meta Tag for HTTP_EQUIV
     *
     * @param  string  $type
     * @param  string  $content
     * @param  string  $html
     */
    public function http_equiv($type = null, $content = null, $html = true)
    {
        $str = [];
        $http_equiv = $this->config('meta_http_equiv');

        if (empty($type)) {
            $str['type'] = $http_equiv['type'];
        } else {
            $str['type'] = $type;
        }

        if (empty($content)) {
            $str['content'] = $http_equiv['content'];
        } else {
            $str['content'] = $content;
        }

        $this->content['text']['meta_http_equiv'] = $str;
        $this->content['html']['meta_http_equiv'] = '<meta '.__FUNCTION__.'="'.$str['type'].'" content="'.$str['content'].'" />';
    }
}
