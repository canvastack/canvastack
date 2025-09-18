<?php

namespace Canvastack\Canvastack\Core\Craft;

/**
 * Created on 25 Mar 2021
 * Time Created	: 12:56:54
 *
 * @filesource	Scripts.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
trait Scripts
{
    private $scriptNode = 'diyScriptNode::';

    public function js($scripts, $position = 'bottom', $as_script_code = false)
    {
        return $this->template->js($scripts, $position, $as_script_code);
    }

    public function css($scripts, $position = 'top')
    {
        return $this->template->css($scripts, $position);
    }

    private function addScriptsFromElements()
    {
        $scripts = [];

        $current_template = canvastack_template_config('admin.'.canvastack_current_template());
        unset($current_template['position']);

        if (! empty($this->form)) {
            $this->getScriptFromElements($this->form);

            if (count($this->form->element_plugins) >= 1) {
                foreach (array_unique($this->form->element_plugins) as $_plugins) {
                    if ('ckeditor' === $_plugins) {
                        /*
                        $scripts['js'][] = "{$this->scriptNode}$('.ckeditor').each(function(x, y) { $(this).attr('id', 'ckeditor_' + y.name); });";
                        $scripts['js'][] = "vendor/ckeditor-4.10.1/ckeditor.js";
                        $scripts['js'][] = "vendor/ckeditor-4.10.1/config.js";
                        */
                        $scripts['js'][] = 'vendor/ckeditor/ckeditor.js';
                        $scripts['js'][] = 'vendor/ckeditor/config.js';
                    }
                }
            }
        }

        if (! empty($this->table->elements)) {
            if (! empty($this->table->filter_scripts)) {
                if (! empty($this->table->filter_scripts['js'])) {
                    $scripts['js'] = $this->table->filter_scripts['js'];
                }
                if (! empty($this->table->filter_scripts['css'])) {
                    $scripts['css'] = $this->table->filter_scripts['css'];
                }
            }
            $this->getScriptFromElements($this->table);
        }

        if (! empty($this->chart->elements)) {
            $this->getScriptFromElements($this->chart);
        }

        $this->setScriptUnique('js', $scripts);
        $this->setScriptUnique('css', $scripts);

        return false;
    }

    private function getScriptFromElements($object)
    {
        $scripts = [];

        if (! empty($object)) {
            $current_template = canvastack_template_config('admin.'.canvastack_current_template());
            unset($current_template['position']);

            foreach (array_unique($object->element_name) as $_elements) {
                foreach ($current_template as $element => $data) {
                    if ($element === $_elements) {
                        foreach ($data as $script_type => $script_paths) {
                            if ('js' === $script_type) {
                                foreach ($script_paths as $script_path) {
                                    $scripts['js'][] = $script_path;
                                }
                            } else {
                                foreach ($script_paths as $script_path) {
                                    $scripts['css'][] = $script_path;
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->setScriptUnique('js', $scripts);
        $this->setScriptUnique('css', $scripts);
    }

    private function setScriptUnique($type, $scripts)
    {
        $scriptLists[$type] = [];
        $scriptEnd[$type] = [];

        if (! empty($scripts[$type])) {
            foreach (array_unique($scripts[$type]) as $script) {
                if (! empty($script)) {
                    if (str_contains($script, 'top:')) {
                        //	$scriptEnd[$type][] = str_replace('last:', '', $script);
                        $this->js(str_replace('top:', '', $script), 'top');
                    } elseif (str_contains($script, 'last:')) {
                        $scriptEnd[$type][] = str_replace('last:', '', $script);
                    } else {
                        $scriptLists[$type][] = $script;
                    }
                }
            }
            $scripts[$type] = array_merge($scriptLists[$type], $scriptEnd[$type]);

            foreach (array_unique($scripts[$type]) as $script) {
                if (str_contains($script, $this->scriptNode)) {
                    $this->{$type}(str_replace($this->scriptNode, '', $script), 'bottom', true);
                } else {
                    $this->{$type}($script);
                }
            }
        }
    }
}
