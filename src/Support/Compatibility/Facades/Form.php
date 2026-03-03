<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Compatibility\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Form Facade.
 *
 * Backward compatibility facade for old CanvaStack Origin Form API.
 *
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder text(string $name, string $label, mixed $value = null, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder email(string $name, string $label, mixed $value = null, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder password(string $name, string $label, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder textarea(string $name, string $label, mixed $value = null, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder select(string $name, string $label, array $options = [], mixed $selected = null, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder checkbox(string $name, string $label, mixed $value = 1, bool $checked = false, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder radio(string $name, string $label, mixed $value, bool $checked = false, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder file(string $name, string $label, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder hidden(string $name, mixed $value = null)
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder date(string $name, string $label, mixed $value = null, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder time(string $name, string $label, mixed $value = null, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder datetime(string $name, string $label, mixed $value = null, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder number(string $name, string $label, mixed $value = null, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder url(string $name, string $label, mixed $value = null, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder tel(string $name, string $label, mixed $value = null, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder color(string $name, string $label, mixed $value = null, array $attributes = [])
 * @method static \Canvastack\Canvastack\Components\Form\FormBuilder sync(string $sourceField, string $targetField, string $valueColumn, string $labelColumn, mixed $query)
 * @method static string render()
 * @method static void setContext(string $context)
 * @method static void setModel(mixed $model)
 *
 * @see \Canvastack\Canvastack\Components\Form\FormBuilder
 */
class Form extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'canvastack.form';
    }
}
