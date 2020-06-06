<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Aws helper
 *
 * @package     tero
 * @subpackage  app
 * @category    helper
 * @author      Daniel Romero 


	Example of config aws
	[ 
		'version'     => 'latest', 
		'region'      => '', //region aws
		'credentials' => 
		[
			'key'    => '', //region key
			'secret' => ''  //region secret
		]
	]

 
 */ 
  
 
if (!function_exists("amazon_s3_get_file")) 
{ 
    function amazon_s3_get_file($config, $bucket, $s3_path)
    { 
        $s3 = new Aws\S3\S3Client($config);

        $s3->registerStreamWrapper();

        try 
        { 
            $result = $s3->getObject(['Bucket'=> $bucket, 'Key'=> $s3_path]);

            return $result;
        } 
        catch (S3Exception $e) 
        {
            return $e;
        }

    }
}

if (!function_exists("amazon_s3_put_file")) 
{
    function amazon_s3_put_file($config, $bucket, $s3_vm_path, $s3_vm_content)
    {  
        $s3 = new Aws\S3\S3Client($config);

        $s3->registerStreamWrapper();

        $uploader = new Aws\S3\ObjectUploader( $s3, $bucket, $s3_vm_path, $s3_vm_content );

        do 
        {
            try 
            {
                $result = $uploader->upload();
                
                if ($result["@metadata"]["statusCode"] == '200') 
                {
                    return 'File successfully uploaded to '.$result["ObjectURL"];
                }
                
                return $result;
            } 
            catch (Aws\Exception\AwsException\MultipartUploadException $e) 
            {
                rewind($source);
				
                $uploader = new Aws\Exception\AwsException\MultipartUploader($s3Client, $source, [ 'state' => $e->getState() ]);
            }

        } while (!isset($result));
 
        return true;
    }
}


if (!function_exists("amazon_send_sms")) 
{
	function amazon_send_sms($config, $sender_id, $phone, $message)
	{
 
		$sns = new \Aws\Sns\SnsClient($config);

		$args = array
		(
			"MessageAttributes" => 
			[
				'AWS.SNS.SMS.SenderID' => [ 'DataType' => 'String', 'StringValue' => $promo_id ],
				'AWS.SNS.SMS.SMSType'  => [ 'DataType' => 'String', 'StringValue' => 'Promotional' ]
			],
			"Message"     => $message,
			"PhoneNumber" => "+54{$phone}"
		);
 
		return $sns->publish($args); 
	}
}
