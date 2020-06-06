<?php 

class mercadoshop_sync
{
    private $is_ready     = FALSE; 
    private $auth_token   = "";
    private $auth_refresh = "";
    private $auth_user    =  0;
    private $auth_expire  =  0;

    public function load()
    { 
        core::getInstance()->cloneIn($this, array("db", "session"));
    }
 
    public function request($action="")
    {
        $union = "?"; if( strpos($action, "?") !== false ) $union = "&";

        $url = "/v1/shops/{$this->auth_user}{$action}{$union}access_token={$this->auth_token}";
  
        $raw = MercadoShop::RAW($url); 
        return json_decode($raw);
    }

    public function auth()
    {   
        $this->is_ready = ( $this->session->recv() == FALSE ? FALSE : TRUE );

        if($this->is_ready == FALSE)
        {
            $rs = $this->db->query(" SELECT * FROM auth ");

            foreach ($rs->result() as $row) 
            {
                $access = MercadoPago::post(array
                (
                    "uri"     => "/oauth/token",
                    "data"    => array( "client_id"    => $row->id, "client_secret" => $row->secret, "grant_type" => "client_credentials"  ),
                    "headers" => array( "content-type" => "application/x-www-form-urlencoded" )
                ));

                if ($access["status"] != 200) 
                {
                    throw new MercadoPagoException ($access['response']['message'], $access['status']);
                    die();
                }
                else
                {
                    $this->auth_token   = $access["response"]["access_token" ];
                    $this->auth_refresh = $access["response"]["refresh_token"];
                    $this->auth_user    = $access["response"]["user_id"      ];
                    $this->auth_expire  = $access["response"]["expires_in"   ];

                    $this->db->query
                    (" 
                        UPDATE 
                            auth 
                        SET 
                            access_token  = '{$this->auth_token}'  ,  
                            refresh_token = '{$this->auth_refresh}', 
                            user_id       = '{$this->auth_user}'   , 
                            expires_in    = '{$this->auth_expire}' ,
                            logged        = now()  
                        WHERE 
                            id = '{$row->id}'  
                    ");
     
                    $this->session->send( $row->id );

                     $this->is_ready = true;
                }
            }
        }
        else
        { 
            $rs = $this->db->query(" SELECT *,  (DATE_SUB(logged, INTERVAL (expires_in*-1) SECOND) > now()) as 'islogon'  FROM auth ");

            foreach ($rs->result() as $row) 
            {
                $this->auth_token   = $row->access_token  ;
                $this->auth_refresh = $row->refresh_token ;
                $this->auth_user    = $row->user_id       ;
                $this->auth_expire  = $row->expires_in    ;

                $row->islogon       = (int)$row->islogon;

                if( $row->islogon == 0 )
                {
                    $this->db->query
                    (" 
                        UPDATE 
                            auth 
                        SET 
                            access_token  = '',  
                            refresh_token = '',  
                            logged        = ''   
                    ");

                    $this->session->close();

                    $this->is_ready = false;

                    $this->auth();
                }
            }
        }
    }
 
    public function shop()
    {  
        $data = $this->request(); 

        $rs = $this->db->query("SELECT * FROM shop WHERE id = '{$data->id}' ");

        $found = FALSE; foreach ($rs->result() as $row) { $found = TRUE; }

        if($found == FALSE)
        { 
            $this->db->query
            (" 
                INSERT 
                    shop 
                SET 
                    id        = '{$data->id}'       ,  
                    status    = '{$data->status}'   ,  
                    locale    = '{$data->locale}'   , 
                    shop_name = '{$data->shop_name}', 
                    email     = '{$data->email}'    ,   
                    plan      = '{$data->plan}'     ,  
                    admin_url = '{$data->admin_url}', 
                    url       = '{$data->url}'      , 
                    has_logo  = '{$data->has_logo}' ,   
                    country   = '{$data->address->country}' , 
                    state     = '{$data->address->state}'   ,   
                    city      = '{$data->address->city}'    ,  
                    zip       = '{$data->address->zip}'     , 
                    address   = '{$data->address->address}' , 
                    phone     = '{$data->address->phone}'  
                    
            ");
        }
        else
        {
            $this->db->query
            (" 
                UPDATE 
                    shop 
                SET 
                    status    = '{$data->status}'   ,  
                    locale    = '{$data->locale}'   , 
                    shop_name = '{$data->shop_name}', 
                    email     = '{$data->email}'    ,   
                    plan      = '{$data->plan}'     ,  
                    admin_url = '{$data->admin_url}', 
                    url       = '{$data->url}'      , 
                    has_logo  = '{$data->has_logo}' ,   
                    country   = '{$data->address->country}' , 
                    state     = '{$data->address->state}'   ,   
                    city      = '{$data->address->city}'    ,  
                    zip       = '{$data->address->zip}'     , 
                    address   = '{$data->address->address}' , 
                    phone     = '{$data->address->phone}'  
                WHERE 
                    id        = '{$data->id}'
                    
            ");
        } 
    }
 
    private function add_if_category($item)
    {

        if( !isset($item->parent_id) ) $item->parent_id =0;

        $rs = $this->db->query("SELECT * FROM category WHERE id = '{$item->id}' ");

        $found = FALSE; foreach ($rs->result() as $row) { $found = TRUE; }

        if($found == FALSE)
        {
            $this->db->query
            (" 
                INSERT 
                    category 
                SET 
                    id               = '{$item->id}'         ,  
                    shop_id          = '{$item->shop_id}'    ,  
                    position         = '{$item->position}'   , 
                    name             = '{$item->name}'       , 
                    default_category = '{$item->default_category}' ,   
                    permalink        = '{$item->permalink}'    ,  
                    date_created     = '{$item->date_created}' , 
                    last_updated     = '{$item->last_updated}' , 
                    parent_id        = '{$item->parent_id}' 
                    
            ");
        }
        else
        {
            $this->db->query
            (" 
                UPDATE 
                    category 
                SET 
                    
                    shop_id          = '{$item->shop_id}'    ,  
                    position         = '{$item->position}'   , 
                    name             = '{$item->name}'       , 
                    default_category = '{$item->default_category}' ,   
                    permalink        = '{$item->permalink}'    ,  
                    date_created     = '{$item->date_created}' , 
                    last_updated     = '{$item->last_updated}' , 
                    parent_id        = '{$item->parent_id}' 
                WHERE
                    id               = '{$item->id}'         
                    
            ");
        }
    }

    private function children_category($children)
    {
        foreach ($children as $item)
        {
            $this->add_if_category($item);
            
            if( isset( $item->children ) )
                if( is_array($item->children) )
            $this->children_category($item->children);
        }
    }

    public function category()
    { 
        $data = $this->request("/categories/tree");

        foreach ($data as $item)
        {
            $this->add_if_category($item);

            if( isset( $item->children ) )
                if( is_array($item->children) )
                    $this->children_category($item->children);
        }
    }

    public function add_if_clientes($item)
    {
        if(!isset($item->nickname)) $item->nickname ="";
        if(!isset($item->asked_questions)) $item->asked_questions ="";
        if(!isset($item->name_for_sort)) $item->name_for_sort ="";
        if(!isset($item->meli_cust_id)) $item->meli_cust_id ="";
        if(!isset($item->purchases->quantity))
        { 
            $item->purchases = new stdclass;
            $item->purchases->quantity ="";
        }

        $rs = $this->db->query("SELECT * FROM clientes WHERE id = '{$item->id}' ");

        $found = FALSE; foreach ($rs->result() as $row) { $found = TRUE; }

        if($found == FALSE)
        {
            $this->db->query
            (" 
                INSERT 
                    clientes 
                SET 
                     id                 = '{$item->id}',
                     store_id           = '{$item->store_id}', 
                     meli_cust_id       = '{$item->meli_cust_id}', 
                     quantity           = '{$item->purchases->quantity}', 
                     type               = '{$item->type}', 
                     name               = '{$item->name}', 
                     name_for_sort      = '{$item->name_for_sort}', 
                     nickname           = '{$item->nickname}', 
                     phone              = '{$item->phone}', 
                     phone2             = '{$item->phone2}', 
                     status             = '{$item->status}', 
                     doc_type           = '{$item->doc_type}', 
                     doc_number         = '{$item->doc_number}', 
                     email              = '{$item->email}', 
                     email2             = '{$item->email2}',
                     asked_questions    = '{$item->asked_questions}',
                     mshops_client      = '{$item->mshops_client}',
                     meli_client        = '{$item->meli_client}',
                     unsubscribe_date   = '{$item->unsubscribe_date}',
                     last_activity_date = '{$item->last_activity_date}',
                     comments           = '{$item->comments}'
                    
            ");
        }
        else
        {
            $this->db->query
            (" 
                UPDATE 
                    clientes 
                SET 
                     
                     store_id           = '{$item->store_id}', 
                     meli_cust_id       = '{$item->meli_cust_id}', 
                     quantity           = '{$item->purchases->quantity}', 
                     type               = '{$item->type}', 
                     name               = '{$item->name}', 
                     name_for_sort      = '{$item->name_for_sort}', 
                     nickname           = '{$item->nickname}', 
                     phone              = '{$item->phone}', 
                     phone2             = '{$item->phone2}', 
                     status             = '{$item->status}', 
                     doc_type           = '{$item->doc_type}', 
                     doc_number         = '{$item->doc_number}', 
                     email              = '{$item->email}', 
                     email2             = '{$item->email2}',
                     asked_questions    = '{$item->asked_questions}',
                     mshops_client      = '{$item->mshops_client}',
                     meli_client        = '{$item->meli_client}',
                     unsubscribe_date   = '{$item->unsubscribe_date}',
                     last_activity_date = '{$item->last_activity_date}',
                     comments           = '{$item->comments}'
                WHERE 
                    id                 = '{$item->id}'    
            ");
        }
    }

    public function clientes()
    {
        $data = $this->request("/clients/search");

        $total = $data->paging->total;
   
        $cant = round($total / 50); 

        foreach ($data->results as $row) 
        {
            $this->add_if_clientes($row);
        }

        for ($i=1; $i <= $cant; $i++) 
        { 
            $offset = $i * 50;

            $data = $this->request("/clients/search?offset={$offset}");  

            foreach ($data->results as $row) 
            {
                $this->add_if_clientes($row);
            }  
        }
    }

    public function add_if_productos($item)
    {
        $rs = $this->db->query("SELECT * FROM productos WHERE id = '{$item->id}' ");

        $found = FALSE; foreach ($rs->result() as $row) { $found = TRUE; }

        if($found == FALSE)
        {
            $this->db->query
            (" 
                INSERT 
                    productos 
                SET 
                     id           = '{$item->id}',
                     store_id     = '{$item->store_id}', 
                     title        = '{$item->title}', 
                     sku          = '{$item->sku}', 
                     category_id  = '{$item->category_id}', 
                     category_name= '{$item->category_name}', 
                     stock        = '{$item->stock}', 
                     currency_id  = '{$item->currency_id}', 
                     price        = '{$item->price}', 
                     status       = '{$item->status}', 
                     permalink    = '{$item->permalink}', 
                     thumbnail    = '{$item->thumbnail}', 
                     image        = '{$item->pictures[0]->url}', 
                     promotion    = '{$item->promotion}', 
                     height       = '{$item->height}',
                     width        = '{$item->width}',
                     depth        = '{$item->depth}',
                     weight       = '{$item->weight}',
                     package_id   = '{$item->package_id}',
                     variations   = '{$item->variations}',
                     date_created = '{$item->date_created}',
                     last_modified= '{$item->last_modified}'
            ");
        }
        else
        {
            $this->db->query
            (" 
                UPDATE 
                    productos 
                SET 
                     
                     store_id     = '{$item->store_id}', 
                     title        = '{$item->title}', 
                     sku          = '{$item->sku}', 
                     category_id  = '{$item->category_id}', 
                     category_name= '{$item->category_name}', 
                     stock        = '{$item->stock}', 
                     currency_id  = '{$item->currency_id}', 
                     price        = '{$item->price}', 
                     status       = '{$item->status}', 
                     permalink    = '{$item->permalink}', 
                     thumbnail    = '{$item->thumbnail}', 
                     image        = '{$item->pictures[0]->url}', 
                     promotion    = '{$item->promotion}', 
                     height       = '{$item->height}',
                     width        = '{$item->width}',
                     depth        = '{$item->depth}',
                     weight       = '{$item->weight}',
                     package_id   = '{$item->package_id}',
                     variations   = '{$item->variations}',
                     date_created = '{$item->date_created}',
                     last_modified= '{$item->last_modified}'
                WHERE 
                    id           = '{$item->id}'
            ");
        }
    }

    public function productos()
    {
        $data = $this->request("/listings/search"); 

        $total = $data->paging->total;
   
        $cant = round($total / 50); 

        foreach ($data->results as $row) 
        {
            $this->add_if_productos($row);
        }
 
        for ($i=1; $i <= $cant; $i++) 
        { 
            $offset = $i * 50;

            $data = $this->request("/listings/search?offset={$offset}");  

            foreach ($data->results as $row) 
            {
                $this->add_if_productos($row);
            }  
        }
    }

    public function add_if_ventas($item)
    {
        $rs = $this->db->query("SELECT * FROM ventas WHERE id = '{$item->id}' ");

        $found = FALSE; foreach ($rs->result() as $row) { $found = TRUE; }

        if($found == FALSE)
        { 
            $this->db->query
            (" 
                INSERT 
                    ventas 
                SET 
                     id                        = '{$item->id}',
                     store_id                  = '{$item->store_id}', 
                     shippings_id              = '{$item->shippings[0]->external_reference}',   
                     buyer_client_id           = '{$item->buyer->client_id}', 
                     payments_id               = '{$item->payments[0]->external_reference}', 
                     external_id               = '{$item->external_reference}',   
                     channel                   = '{$item->channel}', 
                     currency                  = '{$item->currency}', 
                     amount                    = '{$item->amount}', 
                     products_total            = '{$item->products_total}', 
                     products_grand_total      = '{$item->products_grand_total}', 
                     shipping_total            = '{$item->shipping_total}', 
                     creation_date             = '{$item->creation_date}',
                     total_last_updated        = '{$item->total_last_updated}',
                     is_cart                   = '{$item->is_cart}',
                     status_delivered          = '{$item->status->delivered}',
                     status_paid               = '{$item->status->paid}',
                     status_closed             = '{$item->status->closed}',
                     status_closed_reason      = '{$item->status->closed_reason}',
                     status_closed_date        = '{$item->status->closed_date}',
                     status_processed          = '{$item->status->processed}',
                     status_processed_date     = '{$item->status->processed_date}',
                     status_contact_data_status= '{$item->status->contact_data_status}',
                     status_order              = '{$item->status->order_status}'
            ");
        }
        else
        {
            $this->db->query
            (" 
                UPDATE 
                    ventas 
                SET 
                     
                     store_id                  = '{$item->store_id}', 
                     shippings_id              = '{$item->shippings[0]->external_reference}',   
                     buyer_client_id           = '{$item->buyer->client_id}', 
                     payments_id               = '{$item->payments[0]->external_reference}', 
                     external_id               = '{$item->external_reference}',   
                     channel                   = '{$item->channel}', 
                     currency                  = '{$item->currency}', 
                     amount                    = '{$item->amount}', 
                     products_total            = '{$item->products_total}', 
                     products_grand_total      = '{$item->products_grand_total}', 
                     shipping_total            = '{$item->shipping_total}', 
                     creation_date             = '{$item->creation_date}',
                     total_last_updated        = '{$item->total_last_updated}',
                     is_cart                   = '{$item->is_cart}',
                     status_delivered          = '{$item->status->delivered}',
                     status_paid               = '{$item->status->paid}',
                     status_closed             = '{$item->status->closed}',
                     status_closed_reason      = '{$item->status->closed_reason}',
                     status_closed_date        = '{$item->status->closed_date}',
                     status_processed          = '{$item->status->processed}',
                     status_processed_date     = '{$item->status->processed_date}',
                     status_contact_data_status= '{$item->status->contact_data_status}',
                     status_order              = '{$item->status->order_status}'
                WHERE
                    id = '{$item->id}'
            ");
        }
    }

    public function add_if_envios($item)
    {
        $rs = $this->db->query("SELECT * FROM envios WHERE venta_id = '{$item->id}' ");

        $found = FALSE; foreach ($rs->result() as $row) { $found = TRUE; }

        if($found == FALSE)
        {
            
            foreach ($item->shippings as $envio) 
            {
                $this->db->query
                (" 
                    INSERT 
                        envios 
                    SET 
                         id                     = '{$envio->id}',
                         venta_id               = '{$item->id}',  
                         ship_type              = '{$envio->ship_type}', 
                         channel_status         = '{$envio->channel_status}',   
                         status                 = '{$envio->status}', 
                         receiver_doc_type      = '{$envio->receiver->doc_type}', 
                         receiver_doc_number    = '{$envio->receiver->doc_number}',   
                         receiver_name          = '{$envio->receiver->name}', 
                         receiver_email         = '{$envio->receiver->email}', 
                         receiver_phone         = '{$envio->receiver->phone}', 
                         receiver_address       = '{$envio->receiver->address}', 
                         receiver_zip           = '{$envio->receiver->zip}', 
                         receiver_neighbourhood = '{$envio->receiver->neighbourhood}', 
                         receiver_municipality  = '{$envio->receiver->municipality}',
                         receiver_city          = '{$envio->receiver->city}',
                         receiver_state_id      = '{$envio->receiver->state_id}',
                         receiver_country_id    = '{$envio->receiver->country_id}',
                         additional_info        = '{$envio->additional_info}',
                         method_id              = '{$envio->method_id}',
                         method                 = '{$envio->method}',
                         currency               = '{$envio->currency}',
                         amount                 = '{$envio->amount}',
                         tracking_code          = '{$envio->tracking_code}',
                         observations           = '{$envio->observations}',
                         date_created           = '{$envio->date_created}'
                ");
            } 
        }
        else
        {
            foreach ($item->shippings as $envio) 
            {
                $this->db->query
                (" 
                    UPDATE 
                        envios 
                    SET 
                         
                         venta_id               = '{$item->id}',  
                         ship_type              = '{$envio->ship_type}', 
                         channel_status         = '{$envio->channel_status}',   
                         status                 = '{$envio->status}', 
                         receiver_doc_type      = '{$envio->receiver->doc_type}', 
                         receiver_doc_number    = '{$envio->receiver->doc_number}',   
                         receiver_name          = '{$envio->receiver->name}', 
                         receiver_email         = '{$envio->receiver->email}', 
                         receiver_phone         = '{$envio->receiver->phone}', 
                         receiver_address       = '{$envio->receiver->address}', 
                         receiver_zip           = '{$envio->receiver->zip}', 
                         receiver_neighbourhood = '{$envio->receiver->neighbourhood}', 
                         receiver_municipality  = '{$envio->receiver->municipality}',
                         receiver_city          = '{$envio->receiver->city}',
                         receiver_state_id      = '{$envio->receiver->state_id}',
                         receiver_country_id    = '{$envio->receiver->country_id}',
                         additional_info        = '{$envio->additional_info}',
                         method_id              = '{$envio->method_id}',
                         method                 = '{$envio->method}',
                         currency               = '{$envio->currency}',
                         amount                 = '{$envio->amount}',
                         tracking_code          = '{$envio->tracking_code}',
                         observations           = '{$envio->observations}',
                         date_created           = '{$envio->date_created}'
                    WHERE
                        id = '{$envio->id}'
                ");
            } 
        }
    }

    public function add_if_payment($item)
    {
        $rs = $this->db->query("SELECT * FROM payments WHERE venta_id = '{$item->id}' ");

        $found = FALSE; foreach ($rs->result() as $row) { $found = TRUE; }

        if($found == FALSE)
        {
            
            foreach ($item->payments as $pago) 
            {
                $this->db->query
                (" 
                    INSERT 
                        payments 
                    SET 
                         id            = '{$pago->id}',
                         external_id   = '{$pago->external_reference}',
                         venta_id      = '{$item->id}',   
                         status        = '{$pago->status}', 
                         provider      = '{$pago->provider}', 
                         method        = '{$pago->method}',   
                         currency      = '{$pago->currency}', 
                         amount        = '{$pago->amount}', 
                         paid          = '{$pago->paid}', 
                         paid_date     = '{$pago->paid_date}', 
                         provider_id   = '{$pago->provider_id}', 
                         installments  = '{$pago->installments}', 
                         finance_fee   = '{$pago->fees->finance_fee}',
                         meli_order_id = '{$pago->meli_order_id}' 
                ");
            } 
        }
        else
        {
            
            foreach ($item->payments as $pago) 
            {
                $this->db->query
                (" 
                    UPDATE 
                        payments 
                    SET 
                         
                         external_id   = '{$pago->external_reference}',
                         venta_id      = '{$item->id}',   
                         status        = '{$pago->status}', 
                         provider      = '{$pago->provider}', 
                         method        = '{$pago->method}',   
                         currency      = '{$pago->currency}', 
                         amount        = '{$pago->amount}', 
                         paid          = '{$pago->paid}', 
                         paid_date     = '{$pago->paid_date}', 
                         provider_id   = '{$pago->provider_id}', 
                         installments  = '{$pago->installments}', 
                         finance_fee   = '{$pago->fees->finance_fee}',
                         meli_order_id = '{$pago->meli_order_id}' 
                    WHERE 
                        id = '{$pago->id}'
                ");
            } 
        }
    }

    public function add_if_buy($item)
    {
        $rs = $this->db->query("SELECT * FROM buy WHERE venta_id = '{$item->id}' ");

        $found = FALSE; foreach ($rs->result() as $row) { $found = TRUE; }

        if($found == FALSE)
        {
            foreach ($item->products as $pago) 
            {
                if(!isset($pago->method)) $pago->method="";

                $this->db->query
                (" 
                    INSERT 
                        buy 
                    SET 
                         id           = '{$pago->id}',
                         external_id  = '{$pago->external_reference}',
                         venta_id     = '{$item->id}',   
                         channel      = '{$pago->channel}', 
                         sku          = '{$pago->sku}', 
                         title        = '{$pago->title}',   
                         quantity     = '{$pago->quantity}', 
                         method       = '{$pago->method}', 
                         unit_price   = '{$pago->unit_price}', 
                         amount       = '{$pago->amount}' 
                ");
            } 
        }
        else
        {
            foreach ($item->products as $pago) 
            {
                if(!isset($pago->method)) $pago->method="";

                $this->db->query
                (" 
                    UPDATE 
                        buy 
                    SET 
                         
                         external_id  = '{$pago->external_reference}',
                         venta_id     = '{$item->id}',   
                         channel      = '{$pago->channel}', 
                         sku          = '{$pago->sku}', 
                         title        = '{$pago->title}',   
                         quantity     = '{$pago->quantity}', 
                         method       = '{$pago->method}', 
                         unit_price   = '{$pago->unit_price}', 
                         amount       = '{$pago->amount}'
                    WHERE 
                        id = '{$pago->id}' 
                ");
            } 
        }
    }

    public function ventas()
    {
        $data = $this->request("/orders/search"); 

        $total = $data->paging->total;
   
        $cant = round($total / 50); 

        foreach ($data->results as $row) 
        {
            $this->add_if_ventas ($row);
            $this->add_if_payment($row); 
            $this->add_if_envios ($row);
            $this->add_if_buy    ($row);
        } 

        for ($i=1; $i <= $cant; $i++) 
        { 
            $offset = $i * 50;

            $data = $this->request("/orders/search?offset={$offset}");  

            foreach ($data->results as $row) 
            {
                $this->add_if_ventas ($row); 
                $this->add_if_payment($row); 
                $this->add_if_envios ($row); 
                $this->add_if_buy    ($row); 
            }   
        }

    }
 
}