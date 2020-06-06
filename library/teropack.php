<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Tero Framework 
 *
 * @link      https://github.com/dromero86/tero
 * @copyright Copyright (c) 2014-2019 Daniel Romero
 * @license   https://github.com/dromero86/tero/blob/master/LICENSE (MIT License)
 */    

/**
 * teropack
 * 
 * Bundle minified resources
 *
 * @package     Tero
 * @subpackage  Vendor
 * @category    Library
 * @author      Daniel Romero 
 */ 

class teropack {

    /**
     * Directory SDK Halcon
     *
     * @var string 
     */ 
	private $ROOT_DIR    = "sdk"       ;

    /**
     * Directory SDK path
     *
     * @var string 
     */ 
	private $BUILD_DIR   = "build"     ;

    /**
     * Minified js file
     *
     * @var string 
     */ 
	private $FILE_JS     = "bundle.js" ;

    /**
     * Minified css file
     *
     * @var string 
     */ 
	private $FILE_CSS    = "bundle.css";

    /**
     * Halcon config source
     *
     * @var string 
     */ 
    private $config_file = "sdk/config/source.json";

    /**
     * Halcon config endpoint
     *
     * @var string 
     */ 
    private $build_file  = "sdk/config/build.json";

    /**
     * Config object
     *
     * @var object or boolean 
     */ 
    private $config      = FALSE       ;
    private $list        = array()     ;
    private $rebuild     = array()     ;

    /**
     * Create package files
     * 
     * 
     */
    public function build()
    {
    	$packed_time = date("ymdHis");

    	$this->config = file_get_json(BASEPATH.$this->config_file); 

    	//var_dump($this->config);

    	foreach ($this->config as $file) 
    	{
    		if( in_array($file->tag, array( "link", "script" ) ) ) 
    		{  
				if(!isset($this->list[$file->tag])) 
					$this->list[$file->tag]="";

				$html = file_get_contents(BASEPATH.$this->ROOT_DIR."/".$file->url);

				if($file->tag == "link")
				{
					$path = pathinfo($this->ROOT_DIR."/".$file->url, PATHINFO_DIRNAME );
	 
				    $html = preg_replace_callback(
				        '|url\([a-zA-Z0-9\/\.\?\-\=\#\&]+\)|',
				        function ($match) use($path) {

				        	$changes ="";

				        	foreach ($match as $key => $find) {
				        		//$find
				        		$changes = str_replace("url(", "url(".base_url().$path."/", $find);

				        	} 

				        	return $changes; 
				        },
				        $html
				    );

				    $html = preg_replace('!/\*.*?\*/!s', '', $html);
 
				}
				else
				{
					$html = preg_replace('!/\*.*?\*/!s', '', $html);
					$html = preg_replace('/\n\s*\n/', "\n", $html);
				}

				$this->list[$file->tag].= deflate($html); //"\n/*{$file->url}*/\n".
    		}
    		else
    		{
    			$this->rebuild[]= $file;
    		}
    	}


    	$file_link = BASEPATH.$this->ROOT_DIR."/".$this->BUILD_DIR."/".$this->FILE_CSS;
    	$file_js   = BASEPATH.$this->ROOT_DIR."/".$this->BUILD_DIR."/".$this->FILE_JS ;
    	
    	file_put_contents($file_link , deflate($this->list["link"  ]));
    	file_put_contents($file_js   , deflate($this->list["script"]));
    	
    	$item_link      = new stdclass;
    	$item_link->tag = "link";
    	$item_link->url = $this->BUILD_DIR."/".$this->FILE_CSS;//."?packed={$packed_time}";
    	$this->rebuild[]= $item_link;

    	$item_js      = new stdclass;
    	$item_js->tag = "script";
    	$item_js->url = $this->BUILD_DIR."/".$this->FILE_JS;//."?packed={$packed_time}";
    	$this->rebuild[]= $item_js;

    	file_put_contents( BASEPATH.$this->build_file, json_encode($this->rebuild) );
    }
}