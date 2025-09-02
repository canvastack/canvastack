<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits;

/**
 * ActionsTrait
 *
 * Buttons configuration, URL value, and action defaults.
 */
trait ActionsTrait
{
    public $button_removed = [];

    private $defaultButtons = ['view', 'edit', 'delete'];

    public $useFieldTargetURL = 'id';

    public function removeButtons($remove)
    {
        if (! empty($remove)) {
            if (is_array($remove)) {
                $this->button_removed = $remove;
            } else {
                $this->button_removed = [$remove];
            }
        }
    }

    public function setActions($actions = [], $default_actions = true)
    {
        if (true !== $default_actions) {
            if (is_array($default_actions)) {
                $this->removeButtons($default_actions);
            } else {
                $this->removeButtons($this->defaultButtons);
            }
        }
    }

    public function setUrlValue($field = 'id')
    {
        $this->variables['url_value'] = $field;
        $this->useFieldTargetURL = $field;
    }
}
