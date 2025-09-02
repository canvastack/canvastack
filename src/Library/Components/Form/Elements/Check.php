<?php

namespace Canvastack\Canvastack\Library\Components\Form\Elements;

use Collective\Html\FormFacade as Form;

/**
 * Created on 19 Mar 2021
 * Time Created	: 03:31:26
 *
 * @filesource	Check.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
trait Check
{
    public function checkbox($name, $values = [], $selected = [], $attributes = [], $label = true)
    {
        $this->setParams(__FUNCTION__, $name, $values, $attributes, $label, $selected);
        $this->inputDraw(__FUNCTION__, $name);
    }

    /**
     * Draw Check Box
     *
     * @param  string  $name
     * @param  mixed|array|string  $value
     * @param  string  $selected
     * @param  array  $attributes
     * @return string
     */
    private function drawCheckBox($name, $value, $selected, $attributes = [])
    {
        $checkbox_type = ' ckbox-primary';
        $switch_type = false;
        $hideAttribute = '';
        $checkbox = '';

        if (! is_array($value)) {
            $values = [$value];
        } else {
            $values = $value;
        }

        if (is_array($values)) {
            foreach ($values as $check_key => $check_label) {
                $attr_id = ['id' => "diy{$check_key}:chx".canvastack_random_strings(8, false)];
                $check_attr = array_merge_recursive($attr_id, $attributes);
                $_selected_check = false;

                if (! empty($selected)) {
                    if (is_array($selected)) {
                        foreach ($selected as $selectValue) {
                            if ($check_key === $selectValue) {
                                $_selected_check = true;
                            } elseif ($check_label === $selectValue) {
                                $_selected_check = true;
                            }
                        }
                    } else {
                        if ($check_key === $selected) {
                            $_selected_check = true;
                        } elseif ($check_label === $selected) {
                            $_selected_check = true;
                        }
                    }
                }

                foreach ($check_attr as $attr_key_check => $attr_val_check) {
                    if ('check_type' === $attr_key_check) {
                        if ('switch' === $attr_val_check) {
                            $switch_type = $attr_val_check;
                        } else {
                            $checkbox_type = " ckbox-{$attr_val_check}";
                        }
                    }
                }

                unset($attributes['check_type']);
                if (false !== $switch_type) {
                    foreach ($check_attr as $attr_key_switch => $attr_val_switch) {
                        if ('class' === $attr_key_switch) {
                            $_curr_attr = " {$attr_val_switch}";
                            $switch_class = 'switch';
                            $_attr_switch = ['class' => "{$switch_class}{$_curr_attr}"];
                        }
                    }
                    unset($check_attr['class']);

                    $attr_switch = array_merge_recursive($check_attr, $_attr_switch);
                    $check_attr = $attr_switch;

                    $open_tag = '<div class="switch-box"><div class="s-swtich col-sm-5">';
                    $label_tag = '<label for="'.$check_attr['id'].'">Toggle</label>';
                    $end_tag = '</div>'.Form::label($check_key, $check_label).'</div>';

                } else {
                    $open_tag = '<div class="col-sm-3 ckbox'.$checkbox_type.$hideAttribute.'">';
                    $label_tag = Form::label($check_attr['id'], $check_label);
                    $end_tag = '</div>';
                }

                $checkbox .= $open_tag;
                $checkbox .= Form::checkbox("{$name}[{$check_key}]", $check_key, $_selected_check, $check_attr).$label_tag;
                $checkbox .= $end_tag;
            }
        }

        return $checkbox;
    }
}
