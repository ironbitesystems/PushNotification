<?php

class PushNotification
{
    protected $sandbox = false;
	
	# $device_token - token for connecting with device
	# $body - message data parameters for notifications
	#	Example:
	# 		$body['aps'] = [
	#			/* Add any additional parameters */
	#			'parameter' => (int)$parameter,
	#			'sound' => 'default',
	#			'alert' => [
	#				'title' => 'Title of push notification',
	#				'body' => 'Body of push notification'
	#			]
	#		];
	# Create a pem cert with passphrase prior to call

    public function ios($device_token, $body)
    {
		
        if (!$device_token)
            return;

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', dirname(__FILE__).'/cert.pem');# certificate for connection
        stream_context_set_option($ctx, 'ssl', 'passphrase', 'passphrase_here'); # insert passphrase for cert
        $sandbox = $this->sandbox ? 'sandbox.' : '';
        $url = "ssl://gateway.{$sandbox}push.apple.com:2195";
        $fp = stream_socket_client($url, $err, $errstr, 20, STREAM_CLIENT_ASYNC_CONNECT, $ctx);
		
        if (!$fp)
            return false;

        $payload = json_encode($body);
        $msg = chr(0) . pack('n', 32) . pack('H*', $device_token) . pack('n', strlen($payload)) . $payload;
        fwrite($fp, $msg, strlen($msg));
        fclose($fp);
		
        return true;
		
    }
	
	
	# $device_token - token for connecting with device
	# $post_fields - message data parameters for notifications
	# $post_fields = json_encode(
	#		array(
	#			'registration_ids' => (array)$android_tokens, /* Array of token ids */
	#			'notification' => array(
	#				'body' => 'Body of push notification'
	#				'title' => 'Title of push notification',
	#			),
	#			'data' => array(
	#				/* Add any additional parameters */
	#				'parameter' => (int)$parameter,
	#			)
	#		)
	#	);
	# Get auth key from google prior to call

    public function android($post_fields)
    {
		
        if(!$post_fields)
            return false;
		
		$ch = curl_init();
 
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: key=authkey',# authorization key
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

        // Execute post
        $result = curl_exec($ch);
	
		$header_return = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
        if ($header_return !== 200)
            return false;
 
        // Close connection
        curl_close($ch);
 
        return true;
		
    }


}
?>