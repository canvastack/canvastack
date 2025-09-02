<?php

namespace Canvastack\Canvastack\Controllers\Admin\System;

use Canvastack\Canvastack\Controllers\Core\Controller;
use Canvastack\Canvastack\Models\Admin\System\Language;
use Canvastack\Canvastack\Models\Admin\System\Preference;
use Canvastack\Canvastack\Models\Admin\System\Timezone;
use Illuminate\Http\Request;

/**
 * Created on Mar 7, 2018
 * Time Created	: 9:41:31 AM
 * Filename		: PreferenceController.php
 *
 * @filesource	PreferenceController.php
 *
 * @author		wisnuwidi@canvastack.com - 2018
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class PreferenceController extends Controller
{
    public $data;

    public function __construct()
    {
        parent::__construct(Preference::class, 'system.config.preference');

        $this->setValidations([
            /*
            'title'       => 'required|min:5|max:150',
            'sub_title'   => 'required|min:5|max:150',
             */
            'template' => 'required',
            'meta_author' => 'required',
            'logo' => canvastack_image_validations(800),
            'login_background' => canvastack_image_validations(2000),
        ]);
    }

    private function getIndexModel($id)
    {
        $this->getModel(1);
        $this->model_find(1);

        $this->model_data = (object) $this->model_data->getAttributes();
    }

    public function index()
    {
        return self::redirect('1/edit');
    }

    private function input_language()
    {
        return canvastack_selectbox(Language::all(), 'abbr', 'language');
    }

    private function input_timezone()
    {
        return canvastack_selectbox(Timezone::all(), 'id', 'timezone');
    }

    public function edit($id)
    {
        $this->setPage();
        $this->removeActionButtons(['add', 'view', 'delete', 'back']);

        $this->form->modelWithFile();

        $this->form->text('title');
        $this->form->text('sub_title');
        $this->form->file('logo', ['imagepreview']);

        $this->form->textarea('header', $this->model_data->header, ['class' => 'text-area-class limit-info', 'maxlength' => 20, 'placeholder' => 'Header']);
        $this->form->textarea('footer', $this->model_data->footer);
        $this->form->selectbox('template', ['', 'default' => 'Default'], $this->model_data->template);
        $this->form->selectbox('language', $this->input_language(), $this->model_data->language);
        $this->form->selectbox('timezone', $this->input_timezone(), $this->model_data->timezone);

        $this->form->openTab('Meta Tag');
        $this->form->text('meta_author', $this->model_data->meta_author, ['required']);
        $this->form->tags('meta_title', $this->model_data->meta_title);
        $this->form->tags('meta_keywords', $this->model_data->meta_keywords);
        $this->form->textarea('meta_description|limit:500', $this->model_data->meta_description);

        $this->form->openTab('Email Preference');
        $this->form->text('email_person', $this->model_data->email_person);
        $this->form->text('email_address', $this->model_data->email_address);

        $this->form->openTab('SMTP Setting');
        $this->form->text('smtp_host', $this->model_data->smtp_host);
        $this->form->text('smtp_port', $this->model_data->smtp_port);
        $this->form->text('smtp_secure', $this->model_data->smtp_secure);
        $this->form->text('smtp_user', $this->model_data->smtp_user);
        $this->form->password('smtp_password');

        $this->form->openTab('Session');
        $this->form->text('session_name', $this->model_data->session_name);
        $this->form->text('session_lifetime', $this->model_data->session_lifetime);

        $this->form->openTab('Login Preference');
        $this->form->text('login_title', $this->model_data->login_title);
        $this->form->file('login_background', ['imagepreview']);
        $this->form->number('login_attempts', $this->model_data->login_attempts);
        $this->form->text('change_password', $this->model_data->change_password);

        $this->form->openTab('Web Preference');
        $this->form->selectbox('debug', ['No', 'Yes'], $this->model_data->debug);
        $this->form->selectbox('maintenance', ['No', 'Yes'], $this->model_data->maintenance);
        $this->form->closeTab();

        $this->form->close('Submit', ['class' => 'btn btn-primary btn-slideright pull-right']);

        return $this->render();
    }

    public function update(Request $request, $id)
    {
        $this->update_data($request, $id, false);

        return self::redirect('edit', $request);
    }
}
