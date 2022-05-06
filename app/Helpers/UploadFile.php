<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Image;

/**
 * Description of UploadFile
 *
 * @author kishan
 */
class UploadFile
{
    protected $_projectName = 'dpt';

    public function __construct() {
    }

    public function uploadImage($file = null, $path = null, $height = null, $width = null)
    {
        if ((!isset($file) || empty($file)) && (!isset($path) || empty($path))) return null;
        
        $file = $file->store($path);

        // Resize image here
        if (!empty($height) && !empty($width)) {
            $thumbnailpath = public_path('storage/'.str_replace('public','',$file));

            $img = Image::make($thumbnailpath)->resize($height, $width, function($constraint) {
                $constraint->aspectRatio();
            });

            $img->save($thumbnailpath);
        }

        return $file;
    }

    public function getFilePath($path = null, $type = null)
    {
        if (!isset($path) || empty($path)) return null;

        return str_replace('public/', '', $path);
    }

    public function deleteFileFolder($path = null)
    {
        if (!isset($path) || empty($path)) return false;

        return Storage::delete($path);
    }

    // S3 Bucket uplaod
    public function uploadFileInS3($request = null, $path = null, $fileName = null, $height = null, $width = null, $isUpdateFileName = true)
    {
        if (!$request->hasFile($fileName)) return null;
        
        $projectPath = sprintf('%s/%s', $this->_projectName, $path);

        $file = $request->file($fileName);

        $extension = ($file->getClientOriginalExtension()) ? $file->getClientOriginalExtension() : "doc";

        $newFileName = sprintf('%s.%s', $fileName, $extension);

        if ($isUpdateFileName) {
            $newFileName = sprintf('%d%d.%s', time(), rand(10000, 99999), $extension);
        }

        if (!empty($height) && !empty($width)) {
            $resize = Image::make($file)->resize($height, $width)->encode($extension);

            $response = Storage::disk('s3')->put(sprintf('%s/%s', $projectPath, $newFileName), (string)$resize, 'public');
        } else {
            $response = Storage::disk('s3')->put(sprintf('%s/%s', $projectPath, $newFileName), file_get_contents($file), 'public');
        }

        return sprintf('%s/%s', $path, $newFileName);
    }

    public function uploadMultipleFilesInS3($file = null, $path = null, $height = null, $width = null)
    {
        if (!isset($file) || empty($file)) return null;

        $projectPath = sprintf('%s/%s', $this->_projectName, $path);

        $extension = $file->getClientOriginalExtension();

        $newFileName = sprintf('%d%d.%s', time(), rand(10000, 99999), $extension);

        if (!empty($height) && !empty($width)) {
            $resize = Image::make($file)->resize($height, $width)->encode($extension);

            $response = Storage::disk('s3')->put(sprintf('%s/%s', $projectPath, $newFileName), (string)$resize, 'public');
        } else {
            $response = Storage::disk('s3')->put(sprintf('%s/%s', $projectPath, $newFileName), file_get_contents($file->getPathName()), 'public');
        }

        return sprintf('%s/%s', $path, $newFileName);
    }

    public function getS3FilePath($fileType = null, $path = null)
    {
        if (!isset($path) || empty($path)) return null;

        $s3_link = config('filesystems.disks.s3.url');

        $projectPath = sprintf('%s/%s', $this->_projectName, $path);
        return sprintf('%s/%s', $s3_link, $projectPath);
    }

    public function deleteFileFromS3($path)
    {
        if (!isset($path) || empty($path)) return null;

        $projectPath = sprintf('%s/%s', $this->_projectName, $path);

        $res = Storage::disk('s3')->delete($projectPath);

        return $res;
    }
}
