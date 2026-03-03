<?php
namespace Canvastack\Origin\Library\Components\Form\Elements;

use Collective\Html\FormFacade as Form;
use Intervention\Image\Facades\Image;

/**
 * Created on 19 Mar 2021
 * Time Created	: 03:19:05
 *
 * @filesource	File.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
 
trait File {
	
	public $inputFiles     = [];
	public $getFileUploads = [];
	public $isFileType     = false;
	
	private $filePath      = null;
	private $fileNameInfo  = null;
	private $thumbFolder   = 'thumb';
	
	/**
	 * Create Input File
	 *
	 * @param string $name
	 * @param array $attributes
	 * @param boolean $label
	 */
	public function file($name, $attributes = [], $label = true) {
		$this->setParams(__FUNCTION__, $name, null, $attributes, $label);
		$this->inputDraw(__FUNCTION__, $name);
	}
	
	/**
	 * Draw Input File
	 * 
	 * @param string $name
	 * @param array $attributes
	 * 
	 * @return string
	 */
	private function inputFile($name, $attributes) {
		$hideAttribute = false;
		$input_file    = Form::file($name, false);
		$fileValue     = null;
		
		if (true === in_array('imagepreview', $attributes)) {
			if (!empty($attributes['value'])) {
				$fileValue = "<img src=\"{$attributes['value']}\" />";
			}
			
			$o  = '<div class="fileinput fileinput-new' . $hideAttribute . '" data-provides="fileinput">';
			$o .= '<div id="' . $name . '-fileinput-preview" class="fileinput-preview thumbnail" data-trigger="fileinput" style="width: 198px; height: 150px;">' . $fileValue . '</div>';
			$o .= '<div>';
			
			$o .= "<span class=\"btn btn-primary btn-file\"><span class=\"fileinput-new\">Select Image</span><span class=\"fileinput-exists\">Change</span>{$input_file}</span>";
			$o .= '<a href="#" class="btn btn-danger fileinput-exists" data-dismiss="fileinput">Remove</a>';
			
			$o .= '</div></div>';
		} else {
			if (!empty($attributes['value'])) {
				$fileValue = explode('/', $attributes['value']);
				$fileValue = explode('_', $fileValue[array_key_last($fileValue)]);
				$fileValue = $fileValue[1];
			}
			
			$o  = '<div class="fileinput fileinput-new input-group' . $hideAttribute . '" data-provides="fileinput">';
			$o .= '<div class="form-control" data-trigger="fileinput"><i class="glyphicon glyphicon-file fileinput-exists"></i> <span class="fileinput-filename">' . $fileValue . '</span></div>';
			
			$o .= "<span class=\"input-group-addon btn btn-primary btn-file\"><span class=\"fileinput-new\">Select File</span><span class=\"fileinput-exists\">Change</span>{$input_file}</span>";
			$o .= '<a href="#" class="input-group-addon btn btn-danger fileinput-exists" data-dismiss="fileinput">Remove</a>';
			
			$o .= '</div>';
		}
		
		return "<div class=\"input-group col-sm-9\">{$o}</div>";
	}
	
	/**
	 * Get File Type
	 * 
	 * @param object $request
	 * @param string $input_name
	 * 
	 * @return string
	 */
	private function getFileType($request, $input_name) {
		$mimeType = $request->file($input_name)->getMimeType();
		$getType  = explode('/', $mimeType);
		
		return strtolower($getType[0]);
	}
	
	/**
	 * Validation File
	 * 
	 * @param object $request
	 * @param string $input_name
	 * @param string $validation
	 */
	private function validationFile($request, $input_name, $validation) {
		if (!empty($validation)) {
			$this->validations[$input_name] = $validation;
			$request->validate($this->validations);
		}
	}
	
	/**
	 * Set Upload Path
	 * 
	 * @param string $folder_name
	 * 
	 * @return string
	 */
	private function setUploadPath($folder_name) {
		$baseFileUpload = canvas_config('base_resources');
		
		return public_path("{$baseFileUpload}/{$folder_name}");
	}
	
	/**
	 * Set Asset Path
	 * 
	 * @param string $path
	 * @param string $folder
	 * 
	 * @return string
	 */
	private function setAssetPath($path, $folder) {
		$baseIndexFolder = canvas_config('index_folder');
		$basePathURL     = explode($baseIndexFolder, str_replace('\\', '/', $path));
		/* 
		if (false === canvas_string_contained(canvas_config('baseURL'), 'public')) {
			$baseIndexFolder = null;
			$basePathURLi    = explode('/', $basePathURL[1]);
			unset($basePathURLi[0]);
			$basePathURL[1]  = implode('/', $basePathURLi);
		}
		 */
		if (false === canvas_string_contained(canvas_config('baseURL'), 'public')) {
			$baseIndexFolder = null;
			if (!empty($basePathURL[2])) {
				$basePathURL[1]  = str_replace('/assets/', 'assets/', $basePathURL[2]);
			} else {
				$basePathURLi    = explode('/', $basePathURL[1]);
				unset($basePathURLi[0]);
				$basePathURL[1]  = implode('/', $basePathURLi);
			}
		}
		
		$endBasePathURL = end($basePathURL);
		
		return "{$baseIndexFolder}{$endBasePathURL}/{$folder}";
	}
	
	/**
	 * Trigger File Upload Process
	 * 
	 * @param string $upload_path
	 * @param object $request
	 * @param array $fileInfo
	 */
	public function fileUpload($upload_path, $request, $fileInfo) {
		$this->fileUploadProcessor($request, $upload_path, $fileInfo);
	}
	
	/**
	 * File Upload Process
	 * 
	 * @param object $request
	 * @param string $upload_path
	 * @param array $fileInfo
	 * @param boolean $use_time
	 */
	private function fileUploadProcessor($request, $upload_path, $fileInfo, $use_time = true) {
		$file     = null;
		$filePath = $this->setUploadPath($upload_path);
		
		if (true === $use_time) {
			$str_time = time() . '_';
			$datePath = date('Y') . '/' . date('m') . '/' . date('d');
			$filePath = $this->setUploadPath($upload_path . '/' . $datePath);
		}
		
		$this->filePath = $filePath;
		
		if (!empty($request->files)) {
			foreach ($request->files as $inputname => $fileData) {
				$fileData = [];
				
				// check if any fileInfo data setted by user
				// from setImageElements() and/or setFileElements() function(s)
				if (!empty($fileInfo)) {
					// if yes, check again when inputname from fileInfo match with the real inputname
					if (isset($fileInfo[$inputname])) {
						$fileData = $fileInfo[$inputname];
					}
				}
				$elements = $fileData;
				
				if ($request->hasfile($inputname)) {
					if (!empty($elements['file_validation'])) $this->validationFile($request, $inputname, $elements['file_validation']);
					
					canvas_make_dir($filePath, 0777, true, true);
					if (is_array($request->file($inputname))) {
						//foreach ($request->file($inputname) as $file) {dump($file);}
					} else {
						$file     = $request->file($inputname);
						$filename = $str_time . $file->getClientOriginalName();
					}
					
					$this->fileNameInfo = $filename;
					$fileType           = $this->getFileType($request, $inputname);
					
					if (empty($elements)) {
						$file_name = explode('.', $file->getClientOriginalName());
						
						$elements['file_type']       = $fileType;
						$elements['file_validation'] = null;
						$elements['thumb_name']      = $file_name[0] . '_thumb';
						$elements['thumb_size']      = [100, null];
					}
					
					if ('image' === $fileType) $this->createThumbImage($request, $inputname, $elements, $upload_path);
					
					$file->move($filePath, $filename);
					
					$this->getFileUploads[$inputname]['file'] = $this->setAssetPath($filePath, $filename);
				}
			}
		}
	}
	
	/**
	 * Create Image Thumbnail
	 * 
	 * @param object $request
	 * @param string $inputname
	 * @param array $dataInfo
	 * @param string $upload_path
	 * 
	 * @return boolean
	 */
	private function createThumbImage($request, $inputname, $dataInfo, $upload_path) {
		$filePath       = $this->filePath;
		$fileNameInfo   = $this->fileNameInfo;
		$thumbnail_size = $dataInfo['thumb_size'];
		
		// CREATE THUMBNAIL
		if (!empty($dataInfo['thumb_name'])) {
			$thumb_time = 'tnail_';
			$datePath   = date('Y') . '/' . date('m') . '/' . date('d');
			$thumbPath  = $this->setUploadPath($upload_path . '/' . $datePath . '/' . $this->thumbFolder);
			canvas_make_dir($thumbPath, 0777, true, true);
			
			$thumbname  = $thumb_time . $fileNameInfo;
			$thumbfile  = Image::make($request->file($inputname)->getRealPath());
			
			// SET SIZE THUMB-FILE WITH CHECKING ASPECT RATIO ( $thumbnail_size[0] = width | $thumbnail_size[1] = height )
			if (null !== $thumbnail_size[0]) {
				if (null !== $thumbnail_size[1]) {
					$thumbfile->resize($thumbnail_size[0], $thumbnail_size[1]);
				} else {
					$thumbfile->resize($thumbnail_size[0], null, function($constraint) {
						$constraint->aspectRatio();
					});
				}
			} elseif (null !== $thumbnail_size[1]) {
				if (null !== $thumbnail_size[0]) {
					$thumbfile->resize($thumbnail_size[0], $thumbnail_size[1]);
				} else {
					$thumbfile->resize(null, $thumbnail_size[1], function($constraint) {
						$constraint->aspectRatio();
					});
				}
			}
			
			$thumbfile->save("{$thumbPath}/{$thumbname}");
			$this->getFileUploads[$inputname]['thumbnail'] = $this->setAssetPath($filePath, $this->thumbFolder . '/' . $thumbname);
		}
		
		return false;
	}
}