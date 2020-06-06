<?php 

class firebase
{ 
    private $url = 'https://fcm.googleapis.com/fcm/send';

    private $server_key = '';

    private function _config($json_data)
    {
        $data       = json_encode($json_data);
        $headers    = array( 'Authorization:key='.$this->server_key, 'Content-Type:application/json' );
        $ch         = curl_init();
        curl_setopt($ch, CURLOPT_URL           , $this->url);
        curl_setopt($ch, CURLOPT_POST          , TRUE      );
        curl_setopt($ch, CURLOPT_HTTPHEADER    , $headers  );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE      );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0         );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE     );
        curl_setopt($ch, CURLOPT_POSTFIELDS    , $data     );

        $result = curl_exec($ch);
        
        if ($result === FALSE) 
        {
            die('Oops! FCM Send Error: ' . curl_error($ch));
        }

        curl_close($ch);

        return $result;
    }

    public function send($topic, $title, $body  )
    { 
        return $this->_config(array
        ( 
            'to'               => "/topics/{$topic}",
            'priority'         => 'high',    
            "notification"      => array
            (  
                "icon"  => "bn" ,
                "title" => $title, 
                "body"  => $body,
                "sound" => "default"
            ), 
            "data"      => array
            (   
                "title" => $title, 
                "body"  => $body   
            ) 
        )); 
    }

}