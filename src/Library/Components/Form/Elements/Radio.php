<?php

namespace Canvastack\Canvastack\Library\Components\Form\Elements;

use Collective\Html\FormFacade as Form;

/**
 * Created on 22 Mar 2021
 * Time Created	: 11:01:38
 *
 * @filesource	Radio.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
trait Radio
{
    public function radiobox($name, $values = [], $selected = false, $attributes = [], $label = true)
    {
        $this->setParams('radio', $name, $values, $attributes, $label, $selected);
        $this->inputDraw('radio', $name);
    }

    private function drawRadioBox($name, $value, $selected, $attributes = [])
    {
        $hideAttribute = '';
        $radiobox = '';
        $radio_type = ' col-sm-3 rdio-primary';

        foreach ($value as $radio_key => $radio_label) {
            $attr_id = ['id' => "diy{$radio_key}:rdo".canvastack_random_strings(8, false)];
            $radio_attr = array_merge_recursive($attr_id, $attributes);
            $_selected_radio = false;
            if (false !== $selected) {
                if ($radio_key === $selected) {
                    $_selected_radio = true;
                } elseif ($radio_label === $selected) {
                    $_selected_radio = true;
                }
            }

            foreach ($radio_attr as $attr_key_radio => $attr_val_radio) {
                if ('radio_type' === $attr_key_radio) {
                    $radio_type = " rdio-{$attr_val_radio}";
                }
            }
            unset($radio_attr['radio_type']);

            $radiobox .= '<div class="rdio'.$radio_type.$hideAttribute.' circle">';
            $radiobox .= Form::radio($name, $radio_key, $_selected_radio, $radio_attr).Form::label($radio_attr['id'], $radio_label);
            $radiobox .= '</div>';
        }

        return $radiobox;
    }
}
