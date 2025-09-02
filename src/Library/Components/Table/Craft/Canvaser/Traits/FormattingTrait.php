<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits;

/**
 * FormattingTrait
 *
 * Encapsulates formula registration and column data formatting definitions.
 */
trait FormattingTrait
{
    public $formula = [];

    /**
     * Membuat Formula Untuk Menghitung Nilai Kolom
     *
     * Fungsi ini digunakan untuk membuat formula yang dapat digunakan untuk menghitung nilai kolom tertentu.
     * Formula ini dapat digunakan untuk menghitung nilai kolom yang dihitung berdasarkan beberapa kolom lainnya.
     *
     * @param  string  $name
     *      : Nama dari formula yang akan dibuat.
     *      : Nama ini akan digunakan sebagai nama kolom yang dihitung.
     * @param  string  $label
     *      : Label dari formula yang akan dibuat.
     *      : Label ini akan digunakan sebagai nama tampilan dari kolom yang dihitung.
     * @param  array  $field_lists
     *      : Daftar kolom yang akan digunakan untuk menghitung nilai formula.
     *      : Kolom-kolom ini harus berupa array yang berisi nama-nama kolom yang diinginkan.
     * @param  string  $logic
     *      : Operator logika yang digunakan untuk menghitung nilai formula.
     *      : Operator logika ini dapat berupa '+', '-', '*', '/', '%', '||', '&&'.
     * @param  string  $node_location
     *      : Lokasi node yang akan di isi dengan hasil perhitungan formula.
     *      : Jika di set, maka hasil perhitungan formula akan di isi ke node yang di set.
     *      : Jika tidak di set, maka hasil perhitungan formula akan di isi ke node yang sama dengan nama formula.
     * @param  bool  $node_after_node_location
     *      : Jika true, maka hasil perhitungan formula akan di isi setelah node yang di set.
     *      : Jika false, maka hasil perhitungan formula akan di isi sebelum node yang di set.
     */
    public function formula(string $name, string $label = null, array $field_lists, string $logic, string $node_location = null, bool $node_after_node_location = true)
    {
        $this->labels[$name] = $label;
        $this->conditions['formula'][] = [
            'name' => $name,
            'label' => $label,
            'field_lists' => $field_lists,
            'logic' => $logic,
            'node_location' => $node_location,
            'node_after' => $node_after_node_location,
        ];
    }

    /**
     * Format Data
     *
     * Mengatur format penampilan data pada kolom (angka, boolean, string, dsb.)
     */
    public function format($fields, int $decimal_endpoint = 0, $separator = '.', $format = 'number')
    {
        if (is_array($fields)) {
            foreach ($fields as $field) {
                $this->variables['format_data'][$field] = [
                    'field_name' => $field,
                    'decimal_endpoint' => $decimal_endpoint,
                    'format_type' => $format,
                    'separator' => $separator,
                ];
            }
        } else {
            $this->variables['format_data'][$fields] = [
                'field_name' => $fields,
                'decimal_endpoint' => $decimal_endpoint,
                'format_type' => $format,
                'separator' => $separator,
            ];
        }
    }
}
