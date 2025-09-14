<?php

namespace Canvastack\Canvastack\Controllers\Core\Craft\Includes;

use Illuminate\Support\Facades\Storage;

/**
 * Created on 27 Mar 2021
 * Time Created	: 01:43:45
 *
 * @filesource	FileUpload.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
trait FileUpload
{
    /**
     * Statically define input file type
     *
     * @var array
     */
    protected $inputFiles = [];

    /**
     * File Attribute Collections
     */
    protected $fileAttributes = [];

    /**
     * Set Image Validation
     *
     * created @Sep 8, 2018
     * author: wisnuwidi
     *
     * @param  bool  $filename
     * @param  bool  $size in MegaByte
     * @return array
     */
    private function setImageValidation($filename, $size = 1)
    {
        $this->fileAttributes[$filename]['file_validation'] = canvastack_image_validations(canvastack_set_filesize($size));
    }

    /**
     * Set File Validations
     *
     * @param  string  $filename
     * @param  string  $type
     * @param  bool  $validation
     * @param  number  $size
     */
    private function setFileValidation($filename, $type, $validation = false, $size = 1)
    {
        if (! empty($size)) {
            $max = '|max:'.canvastack_set_filesize($size);
        }
        if (! empty($validation)) {
            $this->fileAttributes[$filename]['file_validation'] = "{$type}|mimes:{$validation}{$max}";
        }
    }

    /**
     * Set File Type
     *
     * @param  string  $filename
     * @param  string  $filetype
     */
    private function setFileType($filename, $filetype)
    {
        $this->fileAttributes[$filename]['file_type'] = $filetype;
    }

    /**
     * Set Image Thumbnail
     *
     * @param  string  $filename
     * @param  bool  $thumb
     * @param  array  $thumb_size
     */
    private function setImageThumb($filename, $thumb = false, $thumb_size = [100, null])
    {
        $thumbName = false;

        if (! empty($thumb)) {
            if (true === $thumb) {
                $thumbName = "{$filename}_thumb";
            } else {
                $thumbName = $thumb;
            }
        } else {
            $thumbName = "{$filename}_thumb";
        }

        if (! empty($thumbName)) {
            $this->fileAttributes[$filename]['thumb_name'] = $thumbName;
            $this->fileAttributes[$filename]['thumb_size'] = $thumb_size;
        }
    }

    /**
     * Data Image Setted for Prevent Adding Thumbnail To Database
     *
     * @var array
     */
    private $dropDbThumbnail = [];

    /**
     * Prevent Inserting Image Thumbnail To The Database
     *
     * @param  string  $file_target
     */
    public function preventInsertDbThumbnail($file_target)
    {
        $this->dropDbThumbnail[$file_target] = $file_target;
    }

    /**
     * Set Image Elements
     *
     * To set some file elements like file type, image validations, image thumbnail
     * Set this function in constructor function [__construct()] class
     *
     * @param  string  $fieldname
     * @param  number  $file_max_size
     * @param  bool  $file_thumb
     * @param  array  $thumb_size
     */
    public function setImageElements($fieldname, $file_max_size = 1, $file_thumb = false, $thumb_size = [100, null])
    {
        $this->setFileType($fieldname, 'image');
        $this->setImageValidation($fieldname, $file_max_size);

        if (! empty($file_thumb)) {
            $this->setImageThumb($fieldname, $file_thumb, $thumb_size);
        }
    }

    /**
     * Set File Elements
     *
     * To set some file elements like file type and file validations
     * Set this function in constructor function [__construct()] class
     *
     * @param  string  $fieldname
     * @param  string  $type
     * @param  bool  $validation
     * @param  int  $size
     */
    public function setFileElements($fieldname, $type, $validation = false, $size = 1)
    {
        $this->setFileType($fieldname, $type);
        $this->setFileValidation($fieldname, $type, $validation, $size);
    }

    /**
     * Simply Manipulate All Request Data with File before Insert/Update Process
     *
     * created @Jul 18, 2018
     * author: wisnuwidi
     *
     * @param  string  $upload_path
     * @param  object  $request
     * @param  string  $filename
     * @param  string  $validation
     * @param  array  $thumbnail_size
     * @param  bool  $use_time
     * @return array
     */
    public function uploadFiles($upload_path, $request, $file_data = [])
    {
        // Sanitasi upload path untuk cegah directory traversal
        $baseUploadDir = 'uploads'; // Base directory upload di storage
        $uploadDisk = 'public'; // Disk config di config/filesystems.php

        // Cek traversal: Tidak boleh mengandung '../' atau '..'
        if (strpos($upload_path, '../') !== false || strpos($upload_path, '..\\') !== false) {
            throw new \Illuminate\Validation\ValidationException(
                \Illuminate\Validation\Factory::make([], [])->errors()->add('upload_path', 'Path traversal tidak diizinkan.')
            );
        }

        $upload_path = trim($upload_path, '/\\'); // Clean path

        $this->getFileUploads = []; // Reset

        if (is_array($file_data)) {
            foreach ($file_data as $file_name => $config) {
                if ($request->hasFile($file_name)) {
                    $file = $request->file($file_name);

                    // Validasi file berdasarkan config
                    if (isset($config['file_validation'])) {
                        $request->validate([
                            $file_name => $config['file_validation']
                        ]);
                    }

                    // Generate unique filename
                    $filename = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $uniqueName = time() . '_' . str_replace('.' . $extension, '', $filename) . '.' . $extension;

                    // Store file menggunakan Laravel Storage
                    $path = $file->storeAs($baseUploadDir . '/' . $upload_path, $uniqueName, $uploadDisk);

                    $storedPath = Storage::disk($uploadDisk)->url($path);

                    $this->getFileUploads[$file_name] = ['file' => $storedPath];

                    // Handle thumbnail jika diperlukan
                    if (isset($config['thumb_name']) && $config['file_type'] === 'image') {
                        // Asumsikan menggunakan Intervention Image; install jika belum
                        $image = \Image::make($file);
                        $thumbName = $config['thumb_name'] . '.' . $extension;
                        $thumbPath = $image->fit($config['thumb_size'][0], $config['thumb_size'][1])->save();
                        $thumbFullPath = $thumbPath->storeAs($baseUploadDir . '/' . $upload_path, $thumbName, $uploadDisk);
                        $this->getFileUploads[$file_name]['thumbnail'] = Storage::disk($uploadDisk)->url($thumbFullPath);
                    }
                }
            }
        }

        if (empty($this->getFileUploads)) {
            $routeBack = str_replace('.', '/', str_replace('store', 'create', current_route()));

            return redirect($routeBack);
        }

        // Data Insert Collection
        $dataExceptions = array_keys($this->getFileUploads);
        $dataFiles = [];
        foreach ($this->getFileUploads as $file_name => $file_data) {
            if (! empty($file_data['thumbnail'])) {
                $checkDropField = isset($this->dropDbThumbnail[$file_name]) ? $this->dropDbThumbnail[$file_name] : false;
                if ($file_name === $checkDropField) {
                    $dataFiles[$file_name] = $file_data['file'];
                } else {
                    $dataFiles[$file_name] = $file_data['file'];
                    $dataFiles[$file_name . '_thumb'] = $file_data['thumbnail'];
                }
            } else {
                $dataFiles[$file_name] = $file_data['file'];
            }
        }

        return array_merge_recursive($request->except($dataExceptions), $dataFiles);
    }
}
