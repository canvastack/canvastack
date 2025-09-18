<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Assets\AssetPathHelper;
use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

/**
 * Image column renderer extracted from legacy Datatables orchestrator (behavior preserved).
 */
final class ImageColumnRenderer
{
    /**
     * Apply image rendering rules by inspecting a sample row and attaching editColumn closures.
     * Note: Mirrors legacy behavior including its quirks.
     *
     * @param  \Yajra\DataTables\DataTableAbstract  $datatables
     * @param  object  $model Sample row object
     */
    public static function apply($datatables, $model): void
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ImageColumnRenderer: Starting image column detection', [
                'model_type' => is_object($model) ? get_class($model) : gettype($model)
            ]);
        }

        $imageField = [];

        // Detect potential image fields by extension (do not require file to exist for registration)
        $rowData = method_exists($model, 'getAttributes') ? $model->getAttributes() : (array) $model;
        foreach ($rowData as $field => $strImg) {
            $hasThumbMate = array_key_exists($field.'_thumb', $rowData) && ! empty($rowData[$field.'_thumb']);
            if ($hasThumbMate || self::looksLikeImage($strImg)) {
                $imageField[$field] = true;
            }
        }

        // Ensure image columns are treated as raw so <img> HTML isn't escaped
        if (! empty($imageField)) {
            $datatables->rawColumns(array_keys($imageField));
        }

        foreach ($imageField as $field => $_) {
            $imgSrc = 'imgsrc::';
            if (isset($model->{$field})) {
                $datatables->editColumn($field, function ($row) use ($field, $imgSrc) {
                    $label = ucwords(str_replace('-', ' ', canvastack_clean_strings($field)));

                    // Support both Eloquent model and array rows
                    $dataValue = method_exists($row, 'getAttributes') ? $row->getAttributes() : (array) $row;
                    $value = (string) ($dataValue[$field] ?? '');
                    if ($value === '') {
                        return '';
                    }

                    // Prefer provided {field}_thumb if present on row
                    $thumbField = $field.'_thumb';
                    $filePath = $value;
                    if (! empty($dataValue[$thumbField]) && is_string($dataValue[$thumbField])) {
                        $maybeThumb = (string) $dataValue[$thumbField];
                        // Use thumb if file exists; fallback to original
                        $thumbFs = AssetPathHelper::toPath($maybeThumb);
                        $filePath = file_exists($thumbFs) ? $maybeThumb : $value;
                    } else {
                        // Build conventional thumb path and use it if exists
                        $parts = explode('/', $value);
                        $lastSrc = array_key_last($parts);
                        $lastFile = $parts[$lastSrc] ?? '';
                        if ($lastSrc !== null) {
                            unset($parts[$lastSrc]);
                        }
                        $maybeThumb = implode('/', $parts).'/thumb/tnail_'.$lastFile;
                        $thumbFs = AssetPathHelper::toPath($maybeThumb);
                        if (file_exists($thumbFs)) {
                            $filePath = $maybeThumb;
                        }
                    }

                    // If looks like image, render <img>. If file missing, show message produced by checkValidImage()
                    if (self::looksLikeImage($value)) {
                        $check = self::checkValidImage($value);
                        if ($check === true) {
                            $alt = $imgSrc.$label;

                            return canvastack_unescape_html("<center><img class=\"cdy-img-thumb\" src=\"{$filePath}\" alt=\"{$alt}\" /></center>");
                        }
                        if (is_string($check)) {
                            return canvastack_unescape_html($check);
                        }
                    }

                    // Non-image fallback: last segment
                    $seg = explode('/', $value);
                    $lastIdx = array_key_last($seg);

                    return $seg[$lastIdx] ?? $value;
                });
            }
        }
    }

    /**
     * Legacy port of checkValidImage() using AssetPathHelper. Behavior preserved intentionally.
     * Returns true if an allowed extension is found and file exists, false or HTML string otherwise.
     *
     * @param  mixed  $string
     * @return bool|string
     */
    private static function checkValidImage($string)
    {
        $filePath = AssetPathHelper::toPath((string) $string);

        // First, check by extension
        $ext = strtolower(pathinfo((string) $string, PATHINFO_EXTENSION));
        $isImageExt = in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true);

        if ($isImageExt) {
            // If the file exists, treat as valid image
            if (true === file_exists($filePath)) {
                return true;
            }
            // If not exists, return legacy-like warning block
            $parts = explode('/', (string) $string);
            $lastSrc = array_key_last($parts);
            $lastFile = $parts[$lastSrc] ?? (string) $string;
            $info = "This File [ {$lastFile} ] Do Not or Never Exist!";

            return "<div class=\"show-hidden-on-hover missing-file\" title=\"{$info}\"><i class=\"fa fa-warning\"></i>&nbsp;{$lastFile}</div><!--div class=\"hide\">{$info}</div-->";
        }

        return false;
    }

    private static function looksLikeImage($string): bool
    {
        $ext = strtolower(pathinfo((string) $string, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true);
    }
}
