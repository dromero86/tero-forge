<?php if ( !defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Tero Framework 
 *
 * @link      https://github.com/dromero86/tero
 * @copyright Copyright (c) 2014-2019 Daniel Romero
 * @license   https://github.com/dromero86/tero/blob/master/LICENSE (MIT License)
 */    

/**
 * Telegram
 *
 * Send message to telegram
 * 
 * @package     Tero 
 * @category    Library
 * @author      Daniel Romero 
 */ 

class telegram
{
    /**
     * object with config.json items parsed
     *
     * @var object 
     */ 
    private $config = null;

    /**
     * string path config.json
     *
     * @var string 
     */ 
    private $config_file = "app/config/telegram.json";

    private $host = "";

    /**
     * Load config objects 
     */
    function __construct() 
    {
        $this->config = file_get_json(BASEPATH.$this->config_file);  

        $this->host = "https://api.telegram.org/bot{$this->config->token}";
    }

    public function sendMessage( $message )
    {
        $message = urlencode($message);

        $url = "{$this->host}/sendMessage?chat_id={$this->config->chatid}&text={$message}"; 

        return $this->exec( $url );
    }

    public function sendPhoto( $photo )
    { 
        $url = "{$this->host}/sendPhoto?chat_id={$this->config->chatid}";  
  
        return $this->exec( $url, array( "param"=> "photo", "file"=> $photo ));
    }

    public function sendAudio( $audio )
    { 
        $url = "{$this->host}/sendAudio?chat_id={$this->config->chatid}";  
  
        return $this->exec( $url, array( "param"=> "audio", "file"=> $audio ));
    }

    public function sendVideo( $video )
    { 
        $url = "{$this->host}/sendVideo?chat_id={$this->config->chatid}";  
  
        return $this->exec( $url, array( "param"=> "video", "file"=> $video ));
    }

    public function sendDocument( $document )
    { 
        $url = "{$this->host}/sendDocument?chat_id={$this->config->chatid}";  
  
        return $this->exec( $url, array( "param"=> "document", "file"=> $document ));
    }

    public function sendAnimation( $animation )
    { 
        $url = "{$this->host}/sendAnimation?chat_id={$this->config->chatid}";  
  
        return $this->exec( $url, array( "param"=> "animation", "file"=> $animation ));
    }

    public function sendVoice( $voice )
    { 
        $url = "{$this->host}/sendVoice?chat_id={$this->config->chatid}";  
  
        return $this->exec( $url, array( "param"=> "voice", "file"=> $voice ));
    }

    public function sendVideoNote( $video_note )
    { 
        $url = "{$this->host}/sendVideoNote?chat_id={$this->config->chatid}";  
  
        return $this->exec( $url, array( "param"=> "video_note", "file"=> $video_note ));
    }

    public function sendSticker( $sticker )
    { 
        $url = "{$this->host}/sendSticker?chat_id={$this->config->chatid}";  
  
        return $this->exec( $url, array( "param"=> "sticker", "file"=> $sticker ));
    }

    public function sendLocation( $latitude, $longitude )
    { 
        $url = "{$this->host}/sendLocation?chat_id={$this->config->chatid}&latitude={$latitude}&longitude={$longitude}";  
  
        return $this->exec( $url );
    }


    private function performFileRequest($ch, $param, $file_data)
    { 
        $name       = pathinfo( $file_data, PATHINFO_FILENAME );
        $mime       = mime_content_type( $file_data );
        $post_file  = array ( $param=> curl_file_create($file_data, $mime, $file_data ) );

        curl_setopt($ch, CURLOPT_HTTPHEADER , array ( "Content-Type" => "multipart/form-data" ));
        //curl_setopt($ch, CURLOPT_SAFE_UPLOAD, FALSE );
        curl_setopt($ch, CURLOPT_POSTFIELDS , $post_file ); 
        curl_setopt($ch, CURLOPT_INFILESIZE , filesize( $file_data ));
    }
 
    public function getUpdates( )
    {  
        $url = "{$this->host}/getUpdates"; 

        return $this->exec( $url );
    }

    private function exec( $url, $option = FALSE ) 
    {    
        $ch  = curl_init();  
        
        curl_setopt($ch, CURLOPT_URL           , $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,    1); 

        if($option != FALSE)
        {
            $this->performFileRequest($ch, $option["param"], $option["file"]);
        }
        
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

}