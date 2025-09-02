<?php

namespace Canvastack\Canvastack\Controllers\Admin\Modules;

use Canvastack\Canvastack\Controllers\Core\Controller;
use Canvastack\Canvastack\Models\Admin\Modules\Form;

/**
 * Created on 23 Mar 2021
 * Time Created : 17:35:59
 *
 * @filesource FormController.php
 *
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
class FormController extends Controller
{
    //	private $route_group		= 'modules';

    //	protected $inputFiles		= ['file_field', 'file_field_alt'];
    //	protected $hideFields		= ['text_field', 'selectbox_field'];
    protected $excludeFields = ['password_field'];

    private $setTableFields = ['email_field:Email', 'text_field', 'number_field:Number', 'month_field:Month', 'time_field', 'file_field', 'file_field_alt', 'updated_at'];

    private $fieldlists = ['region', 'cluster', 'category', 'distributor', 'actual', 'target']; //, 'achv%', 'weight%', 'max_cap%:Max %**', 'total_point:Total Point*'];

    private $chartClass;

    public function __construct()
    {
        parent::__construct(Form::class, 'modules.development.form');

        //	$this->preventInsertDbThumbnail('file_field_alt');
        //	$this->setImageElements('file_field', 1, true);
        //	$this->setFileElements('file_field_alt', 'file', 'txt,xlx,xlxs,pdf', 2);
    }

    public function index()
    {
        $this->setPage();

        $this->chart->subtitle('Test');
        $this->chart->column('report_data_monthly_program_keren_pro_data', ['period', 'region', 'total_all_revenue'], 'name:period|data:total_all_revenue::sum', 'region', 'region::DESC, total_all_revenue::DESC', 'region, period');

        return $this->render();
    }

    private $fieldsUser = [
        'username',
        'fullname',
        'alias',
        'email',
        'email_verified_at',
        'password',
        'gender',
        'birth_date',
        'birth_place',
        'photo',
        'photo_thumb',
        'file_info',
        'address',
        'phone',
        'language',
        'timezone',
        'ip_address',
        'first_route',
        'reg_date',
        'last_visit_date',
        'past_visit_date',
        'remember_token',
        'change_password',
        'last_change_password_date',
        'expire_date',
        'cryptcode',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
        'active',
    ];

    public function index1t()
    {
        $this->setPage();

        $this->table->method('POST');
        $this->table->searchable();
        $this->table->sortable();
        $this->table->clickable();

        $this->table->lists('users', $this->fieldsUser);

        return $this->render();
    }

    private $nodeObject;

    private function setNode($node)
    {
        $this->nodeObject = $node;
    }

    private function setChartObject()
    {
        //	return $this->chartClass = new \ConsoleTVs\Charts\Classes\Highcharts\Chart();
    }

    private $chartData = [];

    private function renderChart($name, $labels = [], $data = [])
    {
        $chart = $name;
        $chart = $this->chartClass;

        $chart->labels($labels);
        $chart->dataset($name, 'column', $data);

        return $this->data['charts'][$name] = $chart;
    }

    public function indexChart1()
    {
        return $this->indexColumn();
    }

    public function index5()
    {
        $this->setPage();

        $opt = [
            'chart' => [
                'params' => [
                    ['labelsRotation', -20],
                    ['minimalist', false],
                ],
            ],
            'data' => ['color' => 'green'],
        ];
        //	$this->charts->column('Legend 1', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14], $opt);
        $this->charts->column('Area 1', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        $this->charts->column('Area 2', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        $this->charts->column('Area 3', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        $this->charts->column('Area 4', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        $this->charts->column('Area 5', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        $this->charts->column('Area 6', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        $this->charts->column('Area 7', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        $this->charts->column('Area 8', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        $this->charts->column('Area 9', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        $this->charts->column('Area 10', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        $this->charts->column('Area 11', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        $this->charts->column('Area 12', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        /*
        $this->charts->canvas();
        $this->charts->column('column 1', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        $this->charts->column('pie 1', ['Pie Chart 1', 'Pie Chart 2', 'Pie Chart 3', 'Pie Chart 4'], [10, 8, 7, 9]);
        $this->charts->line('line 1', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [1, 2, 3, 4]);

        $this->charts->canvas();
        $this->charts->area('Pie Canvas', ['Pie Chart 1', 'Pie Chart 2', 'Pie Chart 3', 'Pie Chart 4'], [10, 18, 17, 19]);
        $this->charts->spline('Column Canvas', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [20, 29, 16, 17]);
        $this->charts->areaspline('Re-Pie Canvas', ['Pie Chart 1', 'Pie Chart 2', 'Pie Chart 3', 'Pie Chart 4'], [8, 7, 9, 5]);
        $this->charts->scatter('Line Canvas', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [1, 2, 3, 4]);
         */ /*
        $this->setChartObject();
        $this->renderChart('Test', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);

        return view('welcome', $this->data); */
        return $this->render();
    }

    public function indexX()
    {
        $this->setPage();

        //	$this->js("https://cdn.jsdelivr.net/npm/fusioncharts@3.12.2/fusioncharts.js");
        /*
        $chart1 = new Chart();
        $chart1->labels(['Chart 1', 'Chart 2', 'Chart 3', 'Chart 4']);
        $chart1->dataset('My Chart 1', 'line', [1, 2, 3, 4]);
        $chart1->dataset('My Chart 1', 'line', collect([1, 2, 3, 4]));
         */
        //	$this->setNode('node');

        /*
        $this->setChartObject();
        $this->renderChart('chart', ['label 1', 'label 2', 'label 3', 'label 4'], [1, 2, 3, 4]);
        $this->setChartObject();
        $this->renderChart('chart2', ['Chart 1', 'Chart 2', 'Chart 3', 'Chart 4', 'Chart 5', 'Chart 6', 'Chart 7'], [1, 2, 3, 4, 5, 6, 7]);
         */

        $this->charts->canvas();
        $this->charts->column('column 1', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [10, 9, 15, 14]);
        $this->charts->column('pie 1', ['Pie Chart 1', 'Pie Chart 2', 'Pie Chart 3', 'Pie Chart 4'], [10, 8, 7, 9]);
        $this->charts->line('line 1', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [1, 2, 3, 4]);

        $this->charts->canvas();
        $this->charts->area('Pie Canvas', ['Pie Chart 1', 'Pie Chart 2', 'Pie Chart 3', 'Pie Chart 4'], [10, 18, 17, 19]);
        $this->charts->spline('Column Canvas', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [20, 29, 16, 17]);
        $this->charts->areaspline('Re-Pie Canvas', ['Pie Chart 1', 'Pie Chart 2', 'Pie Chart 3', 'Pie Chart 4'], [8, 7, 9, 5]);
        $this->charts->scatter('Line Canvas', ['Render Chart 1', 'Render Chart 2', 'Render Chart 3', 'Render Chart 4'], [1, 2, 3, 4]);

        //	$this->charts->break();
        /*
        $this->charts->column('chartDeveleopment2', ['Render Chart 1', 'Render Chart 2'], [1, 2]);
        $this->charts->line('chartDeveleopment2', ['Render Chart 1', 'Render Chart 2'], [1, 2]);
         */
        //	$this->data['content_page'] = $chart;
        //	dd($this);
        //	dd($this->chartData);
        //	return view('welcome', $this->data);
        //	$this->chart->render($chart);

        return $this->render();
    }

    public function index4()
    {
        $this->setPage();

        //	$this->chart->column('t_view_mantra_kpi_distributors', ['periode', 'region', 'actual'], 'name:region|data:actual::sum', 'periode', 'periode::DESC, actual::DESC', 'region, periode');

        $this->chart->dualAxesLineAndColumn('t_view_mantra_kpi_distributors', ['periode', 'region', 'actual', 'target'], 'name:region|data:actual::sum|combine:target::sum::legend:true', 'periode', 'periode::DESC, actual::DESC', 'region, periode');

        return $this->render();
    }

    public function indexColumn()
    {
        $this->setPage();

        $this->chart->title('Report: Mantra KPI Distributors', ['x' => -20]);
        $this->chart->subtitle('Chart Subtitle');
        $this->chart->tooltips([
            'headerFormat' => '<span style="font-size:10px">{point.key}</span><table>',
            'pointFormat' => '<tr><td style="color:{series.color};padding:0">{series.name}: </td><td style="padding:0"><b>{point.y:.1f}</b></td></tr>',
            'footerFormat' => '</table>',
            'shared' => true,
            'useHTML' => true,
        ]);

        $this->chart->column('report_data_monthly_program_keren', ['periode', 'region', 'actual'], 'name:region|data:actual::sum', 'periode', 'periode::DESC, actual::DESC', 'region, periode');

        $this->chart->tooltips([
            'headerFormat' => '<span style="font-size:10px">{point.key}</span><table>',
            'pointFormat' => '<tr><td style="color:{series.color};padding:0">{series.name}: </td><td style="padding:0"><b>{point.y:.1f}</b></td></tr>',
            'footerFormat' => '</table>',
            'shared' => true,
            'useHTML' => true,
        ]);
        $this->chart->column('report_data_monthly_program_keren', ['periode', 'region', 'actual'], 'name:periode|data:actual::sum', 'region', 'region::DESC, actual::DESC', 'region, periode');

        return $this->render();
    }

    public function indexLine()
    {
        $this->setPage();

        $this->chart->title('Report: Mantra KPI Distributors', ['x' => -20]);
        $this->chart->subtitle('Chart Subtitle');
        $this->chart->tooltips(['valueSuffix' => 'C']);
        $this->chart->legends(['layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'middle', 'borderWidth' => '0']);
        //	$this->chart->canvas(['styles' => 'width: 100%; height: 900px; margin: 0 auto', 'id' => 'test_id']);
        //	$this->chart->xAxis([], true);
        $this->chart->yAxis([
            'title' => ['text' => 'Actual (SUM)'],
            'plotLines' => [
                [
                    'value' => 0,
                    'width' => 1,
                    'color' => '#808080',
                ],
            ],
        ]);
        $this->chart->line('report_data_monthly_program_keren', ['periode', 'region', 'actual'], 'name:region|data:actual::sum', 'periode', 'periode::DESC, actual::DESC', 'region, periode');

        $this->chart->title('Mantra KPI Distributors');
        $this->chart->line('report_data_monthly_program_keren', ['periode', 'region', 'actual'], 'name:periode|data:actual::sum', 'region', 'region::DESC, actual::DESC', 'region, periode');

        return $this->render();
    }

    public function index3()
    {
        $this->setPage();

        $this->table->searchable();
        $this->table->clickable();
        $this->table->sortable();

        $this->table->filterGroups('region', 'selectbox', true);
        $this->table->filterGroups('cluster', 'selectbox', true);
        $this->table->filterGroups('category', 'selectbox', true);

        $this->table->lists('t_view_mantra_kpi_distributors', $this->fieldlists, false);

        $series = [
            [
                'name' => 'Tokyo',
                'data' => [7.0, 6.9, 9.5, 14.5, 18.2, 21.5, 25.2, 26.5, 23.3, 18.3, 13.9, 9.6],
            ], [
                'name' => 'New York',
                'data' => [-0.2, 0.8, 5.7, 11.3, 17.0, 22.0, 24.8, 24.1, 20.1, 14.1, 8.6, 2.5],
            ], [
                'name' => 'Berlin',
                'data' => [-0.9, 0.6, 3.5, 8.4, 13.5, 17.0, 18.6, 17.9, 14.3, 9.0, 3.9, 1.0],
            ], [
                'name' => 'London',
                'data' => [3.9, 4.2, 5.7, 8.5, 11.9, 15.2, 17.0, 16.6, 14.2, 10.3, 6.6, 4.8],
            ],
        ];
        //	$series = json_encode($series);//dd("series:{$series}");
        $attributes = ['styles' => 'width: 100%; height: 400px; margin: 0 auto', 'id' => 'test_id'];

        $this->chart->title(['text' => 'Monthly Averange Temperature', 'x' => -20]);
        $this->chart->subtitle(['text' => 'Chart Subtitle']);
        $this->chart->legends(['layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'middle', 'borderWidth' => '0']);
        $this->chart->category(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
        $this->chart->tooltips(['valueSuffix' => 'C']);

        $this->chart->line($series, null, $attributes);

        //		dd($this);
        return $this->render();
    }

    public function index1()
    {
        $this->setPage();

        //	$this->table->mergeColumns('Text Merged Column', ['text_field', 'email_field']);
        //	$this->table->setCenterColumns(['month_field'], true, true);
        //	$this->table->setRightColumns(['number_field', 'formula_f', 'formula_f1'], true, true);
        //	$this->table->setBackgroundColor('#5D94F0', 'yellow', ['file_field_alt', 'email_field']);

        $this->table->searchable();
        $this->table->clickable();
        $this->table->sortable();
        /*
        $this->table->columnCondition('text_field', 'row', '!==', 'Testing', 'background-color', '#F1F7CB');
        $this->table->columnCondition('email_field', 'row', '==', 'test@mail.com', 'background-color', '#FFC107');
        $this->table->columnCondition('email_field', 'cell', '==', 'testing@mail.com', 'background-color', '#CDE3A2');
        $this->table->columnCondition('email_field', 'cell', '!=', 'testing@mail.com', 'background-color', '#E2F6BB');
        $this->table->columnCondition('email_field', 'cell', '==', 'test@mail.com', 'replace', 'mail@replace.ment');
        $this->table->columnCondition('email_field', 'cell', '!=', 'test@mail.com', 'replace', 'replace@mail.com');
        $this->table->columnCondition('text_field', 'cell', '==', 'Testing', 'color', '#28A745');
        $this->table->columnCondition('text_field', 'cell', '!=', 'Testing', 'color', '#007BFF');
        $this->table->columnCondition('text_field', 'cell', '==', 'Testing', 'prefix', '# ');
        $this->table->columnCondition('text_field', 'cell', '!==', 'Testing', 'prefix', '! ');
        $this->table->columnCondition('time_field', 'cell', '!==', '19:08:00', 'suffix', ' #');
        $this->table->columnCondition('time_field', 'cell', '==', '19:08:00', 'suffix', ' !');
        $this->table->columnCondition('formula_f1', 'cell', '>', 140, 'background-color', '#F0CDCD');
        $this->table->columnCondition('formula_f1', 'cell', '>', 140, 'replace', 0);

        $this->table->formula('formula_f', 'Formula Label', ['number_field', 'month_field'], "cos(number_field+month_field)*100+tan(month_field)");
        $this->table->formula('formula_f1', null, ['number_field', 'month_field'], "(number_field+month_field)*number_field");

        $this->table->filterGroups('month_field', 'selectbox', true);
        $this->table->filterGroups('email_field', 'checkbox', false);
        $this->table->filterGroups('text_field', 'radiobox', ['email_field', 'number_field']);

        $this->table->lists('test_inputform', $this->setTableFields, true);

         */
        $this->table->filterGroups('region', 'selectbox', true);
        $this->table->filterGroups('cluster', 'selectbox', true);
        $this->table->filterGroups('category', 'selectbox', true);

        $this->table->lists('t_view_mantra_kpi_distributors', $this->fieldlists, false);

        return $this->render();
    }

    public function index2()
    {
        $this->setPage('Form Object');
        /*
        $this->table->mergeColumns('Text Merged Column', ['text_field', 'email_field']);
        $this->table->mergeColumns('File Merged Column', ['file_field', 'file_field_alt', 'updated_at']);
        $this->table->setCenterColumns(['text_field'], true, false);
        $this->table->setRightColumns(['time_field'], true, true);

        $this->table->setBackgroundColor('#5D94F0', 'yellow', ['file_field_alt', 'email_field']);
        $this->table->setBackgroundColor('#7CA7EE', '#fff', ['time_field']);
         */
        $this->table->searchable(['text_field', 'email_field', 'updated_at']);
        $this->table->clickable(['text_field', 'email_field']);
        $this->table->sortable(['text_field', 'email_field']);

        $this->table->lists('test_inputform', $this->setTableFields);
        /* 	$this->table->clear();

            $this->table->mergeColumns('Text Merged Column', ['parent_name', 'module_name']);
            $this->table->searchable();
            $this->table->clickable();
            $this->table->sortable();
            $this->table->lists('base_module', ['parent_name', 'module_name', 'module_info', 'flag_status'], false);
            $this->table->clear(); */
        /*
    //	$this->table->model($this->model);
        $this->table->mergeColumns('Text Merged Column', ['text_field', 'email_field']);
        $this->table->mergeColumns('Formula Merged Column', ['formula_f', 'formula_f1']);
    //	$this->table->orderby('id', 'desc');
        $this->table->searchable();
        $this->table->clickable();
        $this->table->sortable();

        $this->table->columnCondition('text_field', 'row', '!==', 'Testing', 'background-color', '#F1F7CB');
        $this->table->columnCondition('email_field', 'row', '==', 'test@mail.com', 'background-color', '#FFC107');
        $this->table->columnCondition('email_field', 'cell', '==', 'testing@mail.com', 'background-color', '#CDE3A2');
        $this->table->columnCondition('email_field', 'cell', '!=', 'testing@mail.com', 'background-color', '#E2F6BB');
        $this->table->columnCondition('email_field', 'cell', '==', 'test@mail.com', 'replace', 'mail@replace.ment');
        $this->table->columnCondition('email_field', 'cell', '!=', 'test@mail.com', 'replace', 'replace@mail.com');
        $this->table->columnCondition('text_field', 'cell', '==', 'Testing', 'color', '#28A745');
        $this->table->columnCondition('text_field', 'cell', '!=', 'Testing', 'color', '#007BFF');
        $this->table->columnCondition('text_field', 'cell', '==', 'Testing', 'prefix', '# ');
        $this->table->columnCondition('text_field', 'cell', '!==', 'Testing', 'prefix', '! ');
        $this->table->columnCondition('time_field', 'cell', '!==', '19:08:00', 'suffix', ' #');
        $this->table->columnCondition('time_field', 'cell', '==', '19:08:00', 'suffix', ' !');

        $this->table->columnCondition('formula_f1', 'cell', '>', 140, 'background-color', '#F0CDCD');
        $this->table->columnCondition('formula_f1', 'cell', '>', 140, 'replace', 0);
        $this->table->formula('formula_f', 'Formula Label', ['number_field', 'month_field'], "cos(number_field+month_field)*100+tan(month_field)");
        $this->table->formula('formula_f1', null, ['number_field', 'month_field'], "(number_field+month_field)*number_field");
         */
        //	$this->table->where('time_field', '=', '16:11:00');
        //	$this->table->where('text_field', 'like', '%Testing%');
        //	$this->table->where('id', '>', 3);
        //	$this->table->lists('test_inputform', $this->setTableFields, true);

        /*
        $this->table->searchable();
        $this->table->clickable();
        $this->table->sortable();
        $this->table->lists('base_group', [], false);

        $this->table->searchable();
        $this->table->clickable();
        $this->table->sortable();
        $this->table->lists('base_postal_code', [], false);
         */
        /*
        $this->table->query('select group_name, group_info from base_group where id != 1');
        $this->table->lists(null, ['group_name', 'group_info'], false);
         */
        return $this->render();
    }

    public function create()
    {
        $this->setPage('Form Object');

        $this->form->modelWithFile();

        $this->form->text('text_field');
        $this->form->textarea('textarea_field', null, ['class' => 'text-area-class ckeditor', 'maxlength' => 200, 'placeholder' => 'Isi Konten']);
        $this->form->password('password_field', ['class' => 'text-sub2-class']);
        $this->form->email('email_field', null, ['class' => 'text-class']);

        $this->form->openTab('Multi Select Form');
        $this->form->selectbox('selectbox_field', ['L' => 'Large', 'S' => 'Small']);
        $this->form->checkbox('checkbox_field', [
            1 => 'Check Satu',
            2 => 'Check Dua',
            3 => 'Check Tiga',
        ]);
        $this->form->radiobox('radiobox_field', ['radio 1', 'radio 2', 'radio 3']);

        $this->form->openTab('Date And Time');
        $this->form->date('date_field');
        $this->form->datetime('datetime_field');
        $this->form->daterange('daterange_field');
        $this->form->time('time_field');
        $this->form->month('month_field');

        $this->form->openTab('Others');
        $this->form->number('number_field');

        $this->form->file('file_field', ['imagepreview']);
        $this->form->file('file_field_alt');

        $this->form->closeTab();

        $this->form->close('Submit', ['class' => 'btn btn-primary btn-slideright pull-right']);

        return $this->render();
    }
}
