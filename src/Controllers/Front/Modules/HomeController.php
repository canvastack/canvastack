<?php

namespace Canvastack\Canvastack\Controllers\Front\Modules;

use Canvastack\Canvastack\Core\Controller;

/**
 * Created on 10 Mar 2021
 * Time Created	: 13:17:56
 *
 * @filesource	HomeController.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class HomeController extends Controller
{
    private $name = 'home';

    public $model = [];

    public function __construct()
    {
        parent::__construct();

    }

    public function index()
    {
        $this->meta->title('Development');

        return $this->render();
    }

    public function create()
    {
        $this->meta->title('Development');
        $this->form->open();

        $this->form->text('message-data', 'Isi Surat', ['class' => 'text-sub2-class']);
        $this->form->text('subject_data', 'Isi Subjek', ['class' => 'text-sub-class'], 'Subjek', 2);
        $this->form->text('contact', '08888999', ['class' => 'text-sub2-class'], false, 2);
        $this->form->openTab('Text Form');
        $this->form->password('password_field', ['class' => 'text-sub2-class']);
        $this->form->email('email_address', 'example@gmail.com', ['class' => 'text-class', 'required']);
        $this->form->textarea('textarea_field', 'Isi Konten', ['class' => 'text-area-class ckeditor', 'maxlength' => 200, 'placeholder' => 'Isi Konten']);

        $this->form->openTab('Multi Select Form');
        $this->form->selectbox('select_field', ['L' => 'Large', 'S' => 'Small'], 'S');
        $this->form->checkbox('check_field', [
            'check 1' => 'Check Satu',
            'check 2' => 'Check Dua',
            'check 3' => 'Check Tiga',
        ], ['check 1', 'Check Tiga']);
        $this->form->checkbox('check_2', ['check 1', 'cb2', 'chk3'], [1, 'chk3'], ['check_type' => 'switch']);
        $this->form->radiobox('radio_field', ['radio 1', 'radio 2', 'radio 3'], 'radio 2');

        $this->form->openTab('Date And Time');
        $this->form->date('date_field');
        $this->form->datetime('datetime_field');
        $this->form->daterange('daterange_field');
        $this->form->time('time_field');
        $this->form->month('month_field');

        $this->form->openTab('Others');
        $this->form->number('number_field');
        $this->form->file('file_field', ['imagepreview']);

        $this->form->closeTab();

        $this->form->close('Submit', ['class' => 'btn btn-primary btn-slideright pull-right']);

        return $this->render();
    }
    /*
    public function store(Request $request) {
        dd($request->all());
    } */
}
