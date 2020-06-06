<?php 

/**
 * Tero Framework 
 *
 * @link      https://github.com/dromero86/tero
 * @copyright Copyright (c) 2014-2019 Daniel Romero
 * @license   https://github.com/dromero86/tero/blob/master/LICENSE (MIT License)
 */    

/**
 * Image Processor
 *
 * @package     Tero
 * @subpackage  Vendor
 * @category    Library
 * @author      Daniel Romero 
 */ 
class image
{

    private $_path = "";
    private $_saveAs = "";
    private $_keepRatio = TRUE;
    private $_quality = 72;

    private function resizeAspectRatio($src_img, $saveIn)
    {
        $img_width  = imagesx($src_img);
        $img_height = imagesy($src_img);

        $target_width  = 400;
        $target_height = 400;
        $new_img = @imagecreatetruecolor($target_width, $target_height);

        $width_ratio  = $target_width  / $img_width;
        $height_ratio = $target_height / $img_height;

        if($width_ratio > $height_ratio) {
            $resized_width  = $target_width;
            $resized_height = $img_height * $width_ratio;
        } else {
            $resized_height = $target_height;
            $resized_width  = $img_width * $height_ratio;
        }
        // Drop decimal values
        $resized_width  = round($resized_width);
        $resized_height = round($resized_height);

        // Calculations for centering the image
        $offset_width  = round(($target_width  - $resized_width) / 2);
        $offset_height = round(($target_height - $resized_height) / 2);

        $imgCopyRes = @imagecopyresampled(
                          $new_img, $src_img, 
                          $offset_width, $offset_height, 
                          0, 0, 
                          $resized_width, $resized_height, 
                          $img_width, $img_height);      

        $imageSave = imagejpeg($new_img,$saveIn,$this->_quality);

        imagedestroy($src_img);
        imagedestroy($new_img);

        return $imageSave;
    }

    private function rawSave($src_img, $saveIn)
    {
        $imageSave = imagejpeg($src_img, $saveIn, $this->_quality);

        imagedestroy($src_img);

        return $imageSave;
    }

    public function set_path          ($value){ $this->_path      = $value;  return $this; }
    public function save_as           ($value){ $this->_saveAs    = $value;  return $this; }
    public function keep_aspect_ratio ($value){ $this->_keepRatio = $value;  return $this; }
    public function quality ($value){ $this->_quality = $value;  return $this; }

    private function result($status, $message, $errno="", $errkey="")
    {
        $result          =  new stdclass;
        $result->status  = $status;
        $result->message = $message;
        if($errno) $result->errno   = "";
        if($errkey) $result->errkey  = "";

        return $result;
    }


    public function from_base64($value)
    {
        if($this->_path == "") return $this->result(FALSE, "Path destination empty", "L101", "FOLDER_EMPTY");
        if($this->_saveAs == "") return $this->result(FALSE, "File destination empty", "L102", "FILE_EMPTY");

        if(is_writable($this->_path)==FALSE ) return $this->result(FALSE, "No writable", "L103", "ERROR_PERMISION");

        $saveIn    = $this->_path.$this->_saveAs;

        $imageData = base64_decode($value);

        if($imageData == FALSE) return $this->result(FALSE, "FileData decode error", "L104", "DECODE_ERROR");

        $source    = imagecreatefromstring($imageData); 

        if ($source === FALSE ) return $this->result(FALSE, "FileData parse error", "L105", "ERROR_PARSE"); 

        if($this->_keepRatio == FALSE)
        { 
            $storeResult = $this->resizeAspectRatio($source, $saveIn);       

            if($storeResult== FALSE) return $this->result(FALSE, "FileData saving error", "L106", "ERROR_SAVE_RESIZED");

            return $this->result(TRUE, "FileData saving sucessfully");
        }
        else
        {
            $storeResult = $this->rawSave($source, $saveIn);

            if($storeResult== FALSE) return $this->result(FALSE, "FileData saving error", "L107", "ERROR_SAVE_RAW");

            return $this->result(TRUE, "FileData saving sucessfully");
        }
    }

}