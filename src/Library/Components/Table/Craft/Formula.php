<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft;

use jlawrence\eos\Parser;

/**
 * Created on 11 Jun 2021
 * Time Created	: 15:58:07
 *
 * @filesource	Formula.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class Formula extends Parser
{
    private $data = [];

    private $headers = [];

    private $formula = null;

    public function __construct($data_formula, $data_query)
    {
        $data = [];
        $data['formula'] = $data_formula;
        $data['query_data'] = $data_query->getAttributes();
        $this->data = canvastack_object($data);
    }

    public function calculate()
    {
        $formula = $this->data->formula['logic'];
        $column_key = $this->data->formula['name'];
        $row = $this->data->query_data;
        $this->header_sanitizer($this->data->formula['field_lists']);

        try {
            $row[$column_key] = $this->parsing($formula, $this->headers, $row);
        } catch (\Exception $e) {
            $row[$column_key] = 0;
        }

        $this->formula = $row[$column_key];

        return $this->formula;
    }

    private function parsing($formula, $headers, $row)
    {
        $vars = [];
        $formula = str_replace(['$', '_', '&'], '', strtr($formula, $headers));

        foreach ($headers as $origHeader => $sanitizedHeader) {
            $vars[$sanitizedHeader] = (float) $row[$origHeader];
        }

        return $this->solve($formula, $vars);
    }

    private function header_sanitizer($headers_formula)
    {
        foreach ($headers_formula as $key => $header) {
            $this->headers[$header] = str_replace(range('0', '9'), range('a', 'j'), "diytacolumn{$key}");
        }
    }
}
