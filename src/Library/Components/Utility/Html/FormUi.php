<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Html;

use Collective\Html\FormFacade;

/**
 * Html/Form UI helpers with legacy-parity output.
 * Methods are designed to mirror Helpers\FormObject.php behavior.
 */
final class FormUi
{
    /**
     * Legacy button builder.
     */
    public static function button(
        $name,
        $label = false,
        $action = [],
        $tag = 'button',
        $link = false,
        $color = 'white',
        $border = false,
        $size = false, // intentionally unused for parity
        $disabled = false,
        $icon_name = false,
        $icon_color = false
    ): string {
        // URL attribute
        $url = false;
        if (false !== $link) {
            if (is_array($link)) {
                $keyLink = key($link);
                $urlLink = $link[$keyLink];
                $url = " {$keyLink}=\"{$urlLink}\"";
            } else {
                $url = ' href="'.$link.'"';
            }
        }

        // Color class
        $buttonColor = false;
        if (false !== $color) {
            $buttonColor = " btn-{$color}";
        }

        // Tag
        $buttonTag = $tag;
        if (false === $tag) {
            $buttonTag = 'button';
        }

        // Border class
        $buttonBorder = false;
        if (false !== $border) {
            $buttonBorder = " btn-{$border}";
        }

        // Disabled
        $buttonDisabled = false;
        if (false !== $disabled) {
            $buttonDisabled = ' disabled';
        }

        // Icon
        $icon = false;
        if (false !== $icon_name) {
            $iconColor = false;
            if (false !== $icon_color) {
                $iconColor = " {$icon_color}";
            }
            $icon = '<i class="fa fa-'.$icon_name.' bigger-120'.$iconColor.'"></i>&nbsp; ';
        }

        // Extra attributes (action)
        $actions = [];
        if (count($action) >= 1) {
            foreach ($action as $key => $val) {
                $actions[$key] = " {$key} = '{$val}' ";
            }
            $actionElm = implode(' ', $actions);
        } else {
            $actionElm = false;
        }

        // Build HTML (keep spacing exactly as legacy)
        $button = '<'.$buttonTag.$url.' class="btn '.$buttonColor.' btn-'.$name.$buttonBorder.$buttonDisabled.'" '.$actionElm.'>';
        if (false !== $icon) {
            $button .= $icon;
        }
        if (false !== $label) {
            $button .= $label;
        }
        $button .= '</'.$buttonTag.'>';

        return $button;
    }

    /**
     * Simple Checkbox List Builder (legacy parity)
     */
    public static function checkList($name, $value = false, $label = false, $checked = false, $class = 'success', $id = false, $inputNode = null): string
    {
        $nameAttr = false;
        $valueAttr = false;
        $idAttr = false;
        $idForAttr = false;
        $labelName = '&nbsp;';
        $checkBox = false;

        if (false !== $name) {
            $nameAttr = ' name="'.$name.'"';
        }
        if (false !== $value) {
            $valueAttr = ' value="'.$value.'"';
        }
        if (false !== $id) {
            $idAttr = ' id="'.$id.'"';
            $idForAttr = ' for="'.$id.'"';
        } else {
            $idAttr = ' id="'.$name.'"';
            $idForAttr = ' for="'.$name.'"';
        }
        if (false !== $label) {
            $labelName = "&nbsp; {$label}";
        }
        if (false !== $checked) {
            $checkBox = ' checked="checked"';
        }
        if (! empty($inputNode)) {
            $inputNode = ' '.$inputNode;
        }

        $o = "<div class=\"ckbox ckbox-{$class}\"><input type=\"checkbox\"{$valueAttr}{$nameAttr}{$idAttr}{$checkBox}{$inputNode}><label{$idForAttr}>{$labelName}</label></div>";
        return $o;
    }

    /**
     * Selectbox builder delegating to Laravel Collective (legacy behavior).
     */
    public static function selectbox($name, $values = [], $selected = false, $attributes = [], $label = true, $set_first_value = [null => 'Select'])
    {
        $default_attr = ['class' => 'chosen-select-deselect chosen-selectbox form-control'];
        if (! empty($attributes)) {
            $attributes = self::changeInputAttribute($attributes, 'class', 'chosen-select-deselect chosen-selectbox form-control');
        } else {
            $attributes = $default_attr;
        }

        if (! empty($set_first_value)) {
            $values = array_merge_recursive($set_first_value, $values);
        }

        // Try Laravel Collective; fallback to manual builder when facade is unavailable
        try {
            return FormFacade::select($name, $values, $selected, $attributes);
        } catch (\Throwable $e) {
            $attr = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString($attributes);
            $html = '<select name="'.$name.'" '.$attr.'>';
            foreach ($values as $val => $text) {
                $isSelected = ((string) $selected === (string) $val) ? ' selected' : '';
                $html .= '<option value="'.(string) $val.'"'.$isSelected.'>'.(string) $text.'</option>';
            }
            $html .= '</select>';
            return $html;
        }
    }

    /**
     * Alert message builder (legacy parity)
     */
    public static function alertMessage($message = 'Success', $type = 'success', $title = 'Success', $prefix = 'fa-check', $extra = false): string
    {
        $content_message = null;
        if (is_array($message) && 'success' !== $type) {
            $content_message = '<ul class="alert-info-content">';
            foreach ($message as $mfield => $mData) {
                $content_message .= '<li class="title"><div>';
                $content_message .= '<label for="'.$mfield.'" class="control-label">'.ucwords(str_replace('_', ' ', $mfield)).'</label>';
                if (is_array($mData)) {
                    $content_message .= '<ul class="content">';
                    foreach ($mData as $imData) {
                        $content_message .= '<li>';
                        $content_message .= '<label for="'.$mfield.'" class="control-label">'.$imData.'</label>';
                        $content_message .= '</li>';
                    }
                    $content_message .= '</ul>';
                } else {
                    $content_message .= '<ul class="content"><li><label for="'.$mfield.'" class="control-label">'.$mData.'</label></li></ul>';
                }
                $content_message .= '</div></li>';
            }
            $content_message .= '</ul>';
        }

        $prefix_tag = false;
        if (false !== $prefix) {
            $prefix_tag = "<strong><i class=\"fa {$prefix}\"></i> &nbsp;{$title}</strong>";
        }

        $o = "<div class=\"alert alert-block alert-{$type} animated fadeInDown alert-dismissable\" role=\"alert\">";
        $o .= '<button type="button" class="close" data-dismiss="alert">';
        $o .= '<i class="fa fa-times"></i>';
        $o .= '</button>';
        if (! is_array($message)) {
            // When message is scalar, render it after the prefix
            $o .= "<p>{$prefix_tag} {$message}</p>";
        } else {
            $o .= "<p>{$prefix_tag}</p>{$content_message}";
        }
        $o .= $extra;
        $o .= '</div>';

        return $o;
    }

    /**
     * Header tab.
     */
    public static function createHeaderTab($data, $pointer, $active = false, $class = false): string
    {
        $activeClass = false;
        $classTag = false;
        $tabName = ucwords(str_replace('_', ' ', $data));

        if ($active) {
            $activeClass = ''.$active.'';
        }
        if ($class) {
            $classTag = '<i class="'.$class.'"></i>';
        }

        $string = "<li class=\"nav-item\"><a class=\"nav-link {$activeClass}\" data-toggle=\"tab\" role=\"tab\" href=\"#{$pointer}\">{$classTag}{$tabName}</a></li>";
        return $string;
    }

    /**
     * Content tab.
     */
    public static function createContentTab($data, $pointer, $active = false): string
    {
        $activeClass = false;
        if (false !== $active) {
            $activeClass = ' active show';
        }
        $string = "<div id=\"{$pointer}\" class=\"tab-pane fade{$activeClass}\" role=\"tabpanel\">{$data}</div>";
        return $string;
    }

    /**
     * Merge/append attribute values (legacy parity for class merge behavior)
     */
    public static function changeInputAttribute($attribute, $key = false, $value = false)
    {
        $new_attribute = [$key => $value];
        $attributes = array_merge_recursive($attribute, $new_attribute);

        foreach ($attributes as $keys => $values) {
            if ($key === $keys) {
                $_values = $values;
            }
        }

        if (true === is_array($_values ?? null)) {
            $values = implode(' ', $_values);
        } else {
            $values = $_values ?? null;
        }

        $_attribute = [$key => $values];
        $attribute = array_merge($attribute, $_attribute);

        return $attribute;
    }
}