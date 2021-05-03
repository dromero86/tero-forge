<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Tero Framework 
 *
 * @link      https://github.com/dromero86/tero
 * @copyright Copyright (c) 2014-2021 Daniel Romero
 * @license   https://github.com/dromero86/tero/blob/master/LICENSE (MIT License)
 */    

/**
 * Crunch
 *
 * Generate css file and build output
 * 
 * @package     Tero
 * @subpackage  Vendor
 * @category    Library
 * @author      Daniel Romero 
 */ 

class crunch {
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
    private $config_file = "app/config/crunch.json";

    /**
     * Load config objects
     * 
     * 
     */
    function __construct() 
    {
        $this->config = file_get_json(BASEPATH.$this->config_file);  
    }

    /**
     * Generate css and save file
     * 
     * 
     */
    public function build()
    {
        $css = "";

        foreach($this->config->files as $file)
        {
            $css .= "\n/* BEGIN {$file} */\n\n".file_get_contents(BASEPATH.$file)."\n/* END {$file} */\n";
        }

        foreach($this->config->vars  as $key=>$vars)
        { 
            $css = str_replace("@{$key}", $vars, $css);
        }

        $css = str_replace("[url]", base_url(), $css);

        file_put_contents($this->config->output, $css);

        header("Content-type: text/css; charset: UTF-8");
        die($css);
    }

}