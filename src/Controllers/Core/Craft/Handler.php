<?php

namespace Canvastack\Canvastack\Controllers\Core\Craft;

/**
 * Created on 2 Apr 2023
 *
 * Time Created : 19:50:57
 *
 * @filesource  Handler.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
trait Handler
{
    private $roleAlias = ['admin'];

    private $roleInfo = [];

    private function roleHandlerInfo($role = [])
    {
        $this->roleInfo = $role;
    }

    private function roleHandlerAlias($role = [])
    {
        $this->roleAlias = $role;
    }

    private function initHandler()
    {
        $this->roleHandlerAlias(['admin', 'internal']);
        $this->roleHandlerInfo(['National']);
    }

    private function customHandler()
    {
    }

    protected function sessionFiltersOld()
    {
        $this->initHandler();
        if ('root' !== $this->session['user_group']) {
            if (! in_array($this->session['user_group'], $this->roleAlias)) {
                if (! empty($this->roleInfo) && ! in_array($this->session['group_alias'], $this->roleInfo)) {
                    $this->customHandler();
                }

                $this->sessionConfig();
            }
        }
    }

    protected function sessionFilters()
    {
        $this->initHandler();
        // Safeguard session keys for CLI/testing contexts
        $userGroup = $this->session['user_group'] ?? null;
        $groupAlias = $this->session['group_alias'] ?? null;

        if ($userGroup !== null && $userGroup !== 'root') {
            if (! in_array($userGroup, $this->roleAlias ?? [], true)) {
                if (! empty($this->roleInfo) && ($groupAlias === null || ! in_array($groupAlias, $this->roleInfo, true))) {
                    $this->customHandler();
                }
                $this->sessionConfig();
            }
        }
    }

    private function sessionConfig()
    {
        $user_group_session_key = canvastack_config('user.group_alias_key');
        $user_group_session_field = canvastack_config('user.group_alias_field');
        $user_session_alias = canvastack_config('user.alias_session_name');

        if (! empty($this->session[$user_group_session_field] ?? '')) {
            $this->filterPage([$user_group_session_key => $this->session[$user_group_session_field]], '=');
        }

        if (! empty($this->session[$user_session_alias] ?? [])) {
            foreach ($this->session[$user_session_alias] as $fieldset => $fieldvalues) {
                $this->filterPage([$fieldset => $fieldvalues], '=');
            }
        }
    }
}
