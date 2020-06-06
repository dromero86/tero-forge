<?php
/**
 * Invoice (Facturas)
 * ------------------
 * 
 * Realiza la factura pdf con el siguiente metodo
 * 
 * A) Se locaiza el archivo JSON
 * 	  Este archivo tiene un mapa del documento
 *    (Coordenadas donde van los textos)
 *    Tambien tiene especificacion de donde van
 *    mapeados los datos
 * 
 * B) Se obtiene la fuente de datos
 *    Es un objeto stdclass php con el grupo de
 *    variables standard de la factura
 * 
 * C) Se combinan estos elementos y generan los
 *    datos que necesita la libreria FPDF para 
 *    generar el archivo
 * 
 * @package     Tero
 * @subpackage  Vendor
 * @category    Library
 * @author      Daniel Romero 
 */ 
class invoice
{
	private $source_file          = "app/third_party/fpdf/fpdf.php";
	private $fonts_file           = "app/third_party/fpdf/makefont/makefont.php";
	private $config_file          = "app/config/invoice_with_data.json"      ;
	private $font_family_path     = "sdk/ui/fonts/"  ;
	private $pdf                  = NULL   ;
	private $config               = NULL   ;
	private $default_font_size    = 16     ;
	private $default_font_family  = "Arial";
	private $default_font_align   = "L"    ;
	private $default_font_weight  = ""     ;
	private $default_align_table  = "C"    ;
	private $default_border_table = 0      ;

	private $data = null;

	/**
	 * set_data
	 * 
	 * Set up runtime stdclass with invoice data
	 *
	 * @param   $value  
	 */
	public function set_data($value)
	{
		$this->data = $value;
	}

	/**
	 * set_data
	 * 
	 * Set up runtime stdclass with invoice data
	 *
	 * @param   $value  
	 */
	public function set_invoice_model($value)
	{
		$this->config_file = $value; 
	}

	private function toStringValue($str)
	{
		$regex = "(\{\w+\})"; 
		preg_match_all ( $regex, $str, $matches );

		foreach ($matches  as $f) {

			foreach ($f  as $tag) 
			{ 
				$key = str_replace('{', "", $tag);
				$key = str_replace('}', "", $key); 
  
				if( isset($this->data->{$key}) )
				{
					$str = str_replace($tag, $this->data->{$key}, $str);
				}
			}
		}
		
		return iconv('UTF-8', 'windows-1252', $str);
	}

	private function setText($object)
	{	
		if( isset($object->data) ) 
			if( isset($this->data->{$object->data}))
				$object->value = $this->data->{$object->data}; 

		if( !isset($object->value) ) $object->value = "";

		$fs    = ( isset($object->font_size  ) ? $object->font_size   : $this->default_font_size   );
		$fw    = ( isset($object->font_weight) ? $object->font_weight : $this->default_font_weight );
		$ff    = ( isset($object->font_family) ? $object->font_family : $this->default_font_family );
		$align = ( isset($object->align      ) ? $object->align       : $this->default_font_align  );


		$this->pdf->SetXY($object->position->x, $object->position->y);
		$this->pdf->SetFont($ff, $fw, $fs);
		$this->pdf->Cell(0, 0, $this->toStringValue($object->value), 0, 0, $align );
	}

	private function setTable($object)
	{
		if( isset($object->data) ) 
			if( isset($this->data->{$object->data}))
				$object->value = $this->data->{$object->data}; 

		$fs    = ( isset($object->font_size  ) ? $object->font_size   : $this->default_font_size   );
		$fw    = ( isset($object->font_weight) ? $object->font_weight : $this->default_font_weight );
		$ff    = ( isset($object->font_family) ? $object->font_family : $this->default_font_family );
		$align = ( isset($object->align      ) ? $object->align       : $this->default_align_table );

		$this->pdf->SetXY($object->position->x, $object->position->y);

		if( isset($object->background_color) ) 
			$this->pdf->SetFillColor
			(
				$object->background_color->r,
				$object->background_color->g,
				$object->background_color->b
			); 

		if( isset($object->text_color) ) 
			$this->pdf->SetTextColor
			(
				$object->text_color->r,
				$object->text_color->g,
				$object->text_color->b
			); 

		if( isset($object->line_color) ) 
			$this->pdf->SetDrawColor
			(
				$object->line_color->r,
				$object->line_color->g,
				$object->line_color->b
			); 
    	
    	if( isset($object->border_width) ) 
    		$this->pdf->SetLineWidth($object->border_width); 
 
 		if(isset($object->header_show))
 		{
 			if($object->header_show) 
		 		foreach($object->columns as $column)
		    	{   
		        	$this->pdf->Cell
		        	(
		        		$column->width,
		        		$object->line_height,
		        		$column->name,
		        		isset($column->border) ? $column->border : $this->default_border_table,
		        		0,
		        		isset($column->align) ? $column->align : $align,
		        		true
		        	);
		    	} 
 		}
		else
		{
	 		foreach($object->columns as $column)
	    	{ 
	        	$this->pdf->Cell
	        	(
	        		$column->width,
	        		$object->line_height,
	        		$column->name,
	        		isset($column->border) ? $column->border : $this->default_border_table,
	        		0,
	        		isset($column->align) ? $column->align : $align,
	        		true
	        	);
	    	}
		}


    	$this->pdf->Ln();

    	$this->pdf->SetLineWidth(0);
    	$this->pdf->SetDrawColor (255);

    	foreach($object->value as $row)
    	{
    		$this->pdf->SetX($object->position->x);

    		foreach($object->columns as $column)
	    	{ 
	    		$this->pdf->SetFont($ff, $fw, $fs);
  
	        	$this->pdf->Cell
	        	(
	        		$column->width,
	        		$object->line_height,
	        		$row->{$column->id},
	        		isset($column->border) ? $column->border : $this->default_border_table,
	        		0,
	        		isset($column->align) ? $column->align : $align ,
	        		true
	        	);
	    	} 
	    	$this->pdf->Ln();
    	} 
	}

	private function setMultiLine($object)
	{
		if( isset($object->data) )
		{ 
			if( isset($this->data->{$object->data}))
				$object->value = $this->data->{$object->data};
		}

		$fs    = ( isset($object->font_size  ) ? $object->font_size   : $this->default_font_size );
		$fw    = ( isset($object->font_weight) ? $object->font_weight : '' );
		$ff    = ( isset($object->font_family) ? $object->font_family : $this->default_font_family );
		$align = ( isset($object->align      ) ? $object->align       : $this->default_font_align );
		
		$this->pdf->SetFont($ff, $fw, $fs);

		foreach ($object->value as $item) 
		{
			$object->position->y+=$object->line_height;
			$this->pdf->SetXY($object->position->x, $object->position->y);
			$this->pdf->Cell(0,0, $this->toStringValue($item), 0,0, $align );
		} 
	}

	private function setLine($object)
	{
		$this->pdf->SetDrawColor($object->border_color); 
		$this->pdf->SetLineWidth($object->border_width);
		$this->pdf->Line
		(
			$object->position->x, 
			$object->position->y, 
			$object->position->x_max, 
			$object->position->y
		);
	}
 
	private function setImage($object)
	{ 
		if( isset($object->data) ) 
			if( isset($this->data->{$object->data}))
				$object->value = $this->data->{$object->data}; 


		if(is_file(BASEPATH.$object->value))
			$this->pdf->Image
			( 
				BASEPATH.$object->value,
				$object->position->x,
				$object->position->y,
				$object->width
			);
	}

	private function _load_data()
	{
        $this->config = file_get_json(BASEPATH.$this->config_file); 
	}

	private function _load_pdf()
	{ 
        require(BASEPATH.$this->source_file); 

		$this->pdf = new FPDF('P','mm','A4');

		$this->pdf->AddFont('PF_EAN_P36','','PF_EAN_P36.php');
	}

    public function load() 
    {  
    	$this->_load_data();
    	$this->_load_pdf(); 
    }

    public function write()
    {
    	$this->pdf->AddPage();

    	foreach ($this->config as $mk=>$mv) 
    	{ 
    		foreach ($mv as $ik=>$iv)
    		{
 				if(isset($iv->view))
 				{
	    			switch ($iv->view) 
	    			{
	    				case 'text'     : $this->setText     ($iv); break; 
	    				case 'image'    : $this->setImage    ($iv); break; 
	    				case 'table'    : $this->setTable    ($iv); break; 
	    				case 'multiline': $this->setMultiLine($iv); break; 
	    				case 'line'     : $this->setLine     ($iv); break; 
	    			}
    			}
    		} 

			if(isset($mv->view))
			{
				switch ($mv->view) 
				{
					case 'text'     : $this->setText     ($mv); break; 
					case 'image'    : $this->setImage    ($mv); break; 
					case 'table'    : $this->setTable    ($mv); break; 
					case 'multiline': $this->setMultiLine($mv); break; 
					case 'line'     : $this->setLine     ($mv); break; 
				}
			}

    	}
 
		$this->pdf->Output();	 
		die();
    }
}