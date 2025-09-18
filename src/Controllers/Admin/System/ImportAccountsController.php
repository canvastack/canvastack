<?php

namespace Canvastack\Canvastack\Controllers\Admin\System;

use Canvastack\Canvastack\Core\Controller;
use Canvastack\Canvastack\Models\Admin\System\Group;
use Canvastack\Canvastack\Models\Admin\System\ImportAccounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Created on Dec 13, 2022
 *
 * Time Created : 10:44:25 AM
 *
 * @filesource	ImportAccountsController.php
 *
 * @author      wisnuwidi@canvastack.com - 2022
 * @copyright   wisnuwidi
 *
 * @email       wisnuwidi@canvastack.com
 *
 * @uses        'username|email|fullname|role|info|alias'
 *              'pass format prefix_[lower(username)]_suffix'
 */
class ImportAccountsController extends Controller
{
    public $data;

    private $importField = 'import_csv';

    private $delimiter = '|';

    private $contents = [];

    private $groupName = [];

    private $userEmails = [];

    private $insertRoles = [];

    private $insertUsers = [];

    private $passPrefix = '@';

    private $passSuffix = '#ImU323';

    public function __construct()
    {
        parent::__construct(ImportAccounts::class, 'system.accounts.import_csv');

        $this->checkGroups();
        $this->checkUsers();
    }

    public function index()
    {
        $this->setPage('Import Accounts');
        $this->removeActionButtons(['add', 'view', 'delete', 'back']);

        $this->form->modelWithFile();

        $this->form->file($this->importField, [], 'Import .CSV File');
        $this->form->close('Submit', ['class' => 'btn btn-primary btn-slideright pull-right']);

        return $this->render();
    }

    private function getRequestFileContents(Request $request)
    {
        $file = $request->file($this->importField)->openFile();

        return explode(PHP_EOL, $file->fread($file->getSize()));
    }

    private function checkGroups()
    {
        $groups = new Group();
        foreach ($groups->all() as $group) {
            $groupInfo = $group->getAttributes();
            $this->groupName[$groupInfo['id']] = $groupInfo['group_name'];
        }
    }

    private function setGroupName($groupname)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace('-', ' ', $groupname))));
    }

    private function addGroups($content_roles, $active = true)
    {
        $activeStatus = 0;
        if (true === $active) {
            $activeStatus = 1;
        } elseif (false === $active) {
            $activeStatus = 0;
        } else {
            $activeStatus = $active;
        }

        $newRoles = array_diff(array_keys($content_roles), $this->groupName);
        if (! empty($newRoles)) {
            $groupController = new GroupController();
            foreach ($newRoles as $newrole) {
                $groupLists = array_flip($this->groupName);

                if (empty($groupLists[$this->setGroupName($newrole)])) {
                    $roleInfo = $content_roles[$newrole]['info'];
                    if (strlen($content_roles[$newrole]['info']) <= 3) {
                        $roleInfo = strtoupper($content_roles[$newrole]['info']);
                    }

                    $this->insertRoles = [
                        'group_name' => $this->setGroupName($newrole),
                        'group_info' => $roleInfo,
                        'group_alias' => $content_roles[$newrole]['alias'],
                        'active' => $activeStatus,
                    ];

                    $insertGroupRequests = new Request($this->insertRoles);
                    $groupController->store($insertGroupRequests);
                }
            }

            $this->checkGroups();

            return $this->insertRoles;
        }
    }

    private function checkUsers()
    {
        $users = new ImportAccounts();
        foreach ($users->all() as $user) {
            $userInfo = $user->getAttributes();
            $this->userEmails[$userInfo['username']] = $userInfo['email'];
        }
    }

    private function setPassword($password)
    {
        return bcrypt($password);
    }

    private $userGroupRelated = [];

    private function addUsers($data = [])
    {
        $userEmails = [];
        $userData = [];
        $userGroup = [];
        foreach ($data as $n => $userRows) {
            foreach ($userRows as $fieldname => $value) {
                if ('email' === $fieldname) {
                    $userEmails[$n] = $value;
                    $userGroup[$value] = $userRows['role'];
                    unset($userRows['role']);

                    $userData[$value] = $userRows;
                }
            }
        }

        $newUsers = array_diff($userEmails, $this->userEmails);
        if (! empty($newUsers)) {
            foreach ($newUsers as $n => $newUser) {
                foreach ($userData[$newUser] as $userField => $userValue) {
                    $this->insertUsers[$n][$userField] = $userValue;

                    if ('username' === $userField) {
                        if (! isset($this->userEmails[$userValue])) {
                            $this->insertUsers[$n]['password'] = "{$this->passPrefix}{$userValue}{$this->passSuffix}";
                            $this->insertUsers[$n]['active'] = 1;
                            $this->insertUsers[$n]['created_by'] = Auth::id();
                            $this->insertUsers[$n]['created_at'] = date('Y-m-d H:i:s');
                        }
                    }

                    if ('maps' === $userField) {
                        unset($this->insertUsers[$n][$userField]);
                        if (! empty($userValue)) {
                            $aliases = [];
                            foreach (json_decode($userValue) as $alias) {
                                foreach ($alias as $aliasKey => $aliasValue) {
                                    if ('all' !== strtolower($aliasValue)) {
                                        $aliases[$aliasKey][] = $aliasValue;
                                    }
                                }
                            }
                            $userAliases = [];
                            foreach ($aliases as $alsKey => $alsVal) {
                                $userAliases[] = ":{$alsKey}|".implode(",{$alsKey}|", array_unique($alsVal));
                            }

                            $alias = [];
                            foreach ($userAliases as $akey => $aVal) {
                                $alias[$akey] = $aVal;
                            }

                            $this->insertUsers[$n]['alias'] = implode('', $alias);
                        }
                    }
                }
            }

            if (! empty($this->insertUsers)) {

                $checkEmail = array_flip($this->userEmails);
                $userController = new UserController();
                $groupID = array_flip($this->groupName);

                foreach ($this->insertUsers as $dataUsers) {
                    if (empty($checkEmail[$dataUsers['email']])) {
                        $requestGroup['group_id'] = $groupID[$this->setGroupName($userGroup[$dataUsers['email']])];
                        $requestGroup['email'] = $dataUsers['email'];
                        $insertUserRequests = $dataUsers;
                        $requests = new Request([
                            'diyImportProcess' => true,
                            'user' => $insertUserRequests,
                            'group' => $requestGroup,
                        ]);

                        $userController->store($requests);
                    }
                }
            }
        }
    }

    public function store(Request $request, $req = true)
    {
        $data = $this->getRequestFileContents($request);
        $content = [];
        foreach ($data as $n => $rowData) {
            if (! empty($rowData)) {
                if (0 === $n) {
                    $content['head'] = explode($this->delimiter, str_replace("\r", '', $rowData));
                } else {
                    $content['data'][$n] = explode($this->delimiter, str_replace("\r", '', $rowData));
                }
            }
        }

        $fileHeader = $content['head'];
        $fileData = $content['data'];
        $userLists = [];
        $contentFile = [];

        foreach ($fileData as $n => $rows) {
            $roleDataValues = [];
            foreach ($rows as $i => $row) {
                $fieldname = $fileHeader[$i];
                $fieldvalue = $row;

                if (canvastack_string_contained($fieldname, 'username')) {
                    $fieldvalue = strtolower($row);
                }
                if (canvastack_string_contained($fieldname, 'fullname') || canvastack_string_contained($fieldname, 'alias') || canvastack_string_contained($fieldname, 'info') || canvastack_string_contained($fieldname, 'maps')) {
                    $fieldvalue = ucwords($row);
                }

                if (canvastack_string_contained($fieldname, 'role')) {
                    $contentFile['roles'][$n]['role'] = $fieldvalue;
                }
                if (canvastack_string_contained($fieldname, 'role')) {
                    $roleFieldValue = explode('-', $fieldvalue);
                    foreach ($roleFieldValue as $nr => $roleDataFieldValue) {
                        if (0 === $nr && strlen($roleDataFieldValue) <= 4) {
                            $roleDataValues[$nr] = strtoupper($roleDataFieldValue);
                        } else {
                            $roleDataValues[$nr] = ucwords($roleDataFieldValue);
                        }
                    }
                    $roleDataValue = implode('-', $roleDataValues);

                    $contentFile['roles'][$n]['role'] = $roleDataValue;
                }
                if (canvastack_string_contained($fieldname, 'info')) {
                    $contentFile['roles'][$n]['info'] = $fieldvalue;
                }
                if (canvastack_string_contained($fieldname, 'alias')) {
                    $contentFile['roles'][$n]['alias'] = $fieldvalue;
                }

                if (! canvastack_string_contained($fieldname, 'info') && ! canvastack_string_contained($fieldname, 'alias')) {
                    if (canvastack_string_contained($fieldname, 'email')) {
                        $userLists[$n][$fieldname] = strtolower(str_replace('_', ' ', str_replace('-', ' ', $fieldvalue)));
                    } elseif (canvastack_string_contained($fieldname, 'username')) {
                        $userLists[$n][$fieldname] = strtolower(str_replace('_', ' ', str_replace('-', ' ', $fieldvalue)));
                    } elseif (canvastack_string_contained($fieldname, 'role')) {
                        $roleFieldValue = explode('-', $fieldvalue);
                        foreach ($roleFieldValue as $nr => $roleDataFieldValue) {
                            if (0 === $nr && strlen($roleDataFieldValue) <= 4) {
                                $roleDataValues[$nr] = strtoupper($roleDataFieldValue);
                            } else {
                                $roleDataValues[$nr] = ucwords($roleDataFieldValue);
                            }
                        }
                        $roleDataValue = implode(' ', $roleDataValues);

                        $userLists[$n][$fieldname] = $roleDataValue;
                    } else {
                        $userLists[$n][$fieldname] = ucwords(str_replace('_', ' ', str_replace('-', ' ', $fieldvalue)));
                    }
                }
            }
        }

        $manageRoles = [];
        $roleName = null;
        foreach ($contentFile['roles'] as $roleList) {
            foreach ($roleList as $info => $value) {
                if ('role' === $info) {
                    $roleName = $value;
                    $manageRoles[$roleName]['role'] = $value;
                }
            }
            foreach ($roleList as $info => $value) {
                if ('role' !== $info) {
                    $manageRoles[$roleName][$info] = $value;
                }
            }
        }
        $contentFile['roles'] = $manageRoles;

        foreach ($userLists as $userData) {
            $contentFile['users'][$userData['email']] = $userData;
        }

        // INSERT NEW ROLES
        $this->addGroups($contentFile['roles']);
        // INSERT NEW USERS
        $this->addUsers($contentFile['users']);

        return self::redirect('', $request);
    }
}
