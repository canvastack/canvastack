<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Fields;

use InvalidArgumentException;

/**
 * FieldFactory - Factory for creating form field instances.
 *
 * Uses Factory Pattern to create appropriate field types.
 */
class FieldFactory
{
    /**
     * Field type to class mapping.
     */
    protected array $fieldTypes = [
        'text' => TextField::class,
        'textarea' => TextareaField::class,
        'email' => EmailField::class,
        'password' => PasswordField::class,
        'number' => NumberField::class,
        'select' => SelectField::class,
        'checkbox' => CheckboxField::class,
        'radio' => RadioField::class,
        'file' => FileField::class,
        'date' => DateField::class,
        'datetime' => DateTimeField::class,
        'time' => TimeField::class,
        'hidden' => HiddenField::class,
        'tags' => TagsField::class,
        'daterange' => DateRangeField::class,
        'month' => MonthField::class,
    ];

    /**
     * Create a field instance.
     *
     * @param string $type Field type
     * @param string $name Field name
     * @param string|null $label Field label
     * @param mixed $value Field value or options (for select/checkbox/radio)
     * @param array $attributes HTML attributes
     * @return BaseField
     * @throws InvalidArgumentException
     */
    public function make(string $type, string $name, ?string $label = null, mixed $value = null, array $attributes = []): BaseField
    {
        if (!isset($this->fieldTypes[$type])) {
            throw new InvalidArgumentException("Unknown field type: {$type}");
        }

        $className = $this->fieldTypes[$type];

        /** @var BaseField $field */
        $field = new $className($name, $label, $value, $attributes);

        return $field;
    }

    /**
     * Register a custom field type.
     *
     * @param string $type Field type identifier
     * @param string $className Fully qualified class name
     * @return void
     */
    public function register(string $type, string $className): void
    {
        if (!is_subclass_of($className, BaseField::class)) {
            throw new InvalidArgumentException('Field class must extend BaseField');
        }

        $this->fieldTypes[$type] = $className;
    }

    /**
     * Get all registered field types.
     *
     * @return array
     */
    public function getFieldTypes(): array
    {
        return $this->fieldTypes;
    }
}
