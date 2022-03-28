<?php

	namespace helpers\Paypal;

	class Manager
	{
		const IPN_URL = 'https://www.paypal.com/cgi-bin/webscr';
		
		const SANDBOX_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		
		public static function form( $params = array( ) , $isSandBox = false )
		{
			$form = new Form( $params , $isSandBox );
			return $form->create( );
		}
		
		public static function verify( $business_email , $sandbox = false , $paymentStatus = 'completed' )
		{
			$status = ( is_array( $paymentStatus ) ) ? $paymentStatus : array( $paymentStatus );
			$ipn = ( $sandbox ) ? static::SANDBOX_URL : static::IPN_URL;
			$raw_post_data = file_get_contents( 'php://input' );
			$raw_post_array = explode( '&' , $raw_post_data );
			$myPost = array( );
			foreach ( $raw_post_array as $keyval ) 
			{
				$keyval = explode ( '=' , $keyval );
				if ( count( $keyval ) == 2 ){ $myPost[ $keyval[ 0 ] ] = urldecode( $keyval[ 1 ] ); }
			}
			$req = 'cmd=_notify-validate';
			if ( function_exists( 'get_magic_quotes_gpc' ) ){ $get_magic_quotes_exists = true; } 
			foreach ( $myPost as $key => $value ) 
			{        
				if ( $get_magic_quotes_exists == true && get_magic_quotes_gpc( ) == 1 ) 
				{ 
					$value = urlencode( stripslashes( $value ) ); 
				} 
				else{ $value = urlencode( $value ); }
				$req .= "&$key=$value";
			}
			$ch = curl_init( $ipn );
			curl_setopt( $ch , CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
			curl_setopt( $ch , CURLOPT_POST , 1 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER , 1 );
			curl_setopt( $ch , CURLOPT_POSTFIELDS , $req );
			curl_setopt( $ch , CURLOPT_SSL_VERIFYPEER , 1 );
			curl_setopt( $ch , CURLOPT_SSL_VERIFYHOST , 2 );
			curl_setopt( $ch , CURLOPT_FORBID_REUSE , 1 );
			curl_setopt( $ch , CURLOPT_HTTPHEADER , array( 'Connection: Close' ) );
			if ( !$res = curl_exec( $ch ) )
			{
				
				$return =
				[
					'success'		=>	false ,
					'error'		=>	true ,
					'error_msg'	=>	str_replace( '{curl_error}' , curl_error( $ch ) , static::$_codes[ 104 ] ) ,
					'code'		=>	104
				];
				curl_close( $ch );
				return $return;
			}
			curl_close( $ch );
			if ( 0 == strcmp( $res , 'VERIFIED' ) ) // The IPN is verified, process it
			{
				$receiver_email = filter_var( $myPost[ 'receiver_email' ] , FILTER_SANITIZE_EMAIL );
				$payment_status = filter_var( $myPost[ 'payment_status' ] , FILTER_SANITIZE_STRING );
				if ( !in_array( strtolower( $payment_status ) , $status ) ) // payment status does not match
				{
					return
					[
						'success'		=>	false ,
						'error'		=>	true ,
						'error_msg'	=>	static::$_codes[ 100 ] ,
						'code'		=>	100
					];
				}
				else if ( $receiver_email !== $business_email )
				{
					return
					[
						'success'		=>	false ,
						'error'		=>	true ,
						'error_msg'	=>	static::$_codes[ 101 ] ,
						'code'		=>	101
					];
				}
				return
				[
						'success'		=>	true ,
						'code'		=>	200 ,
						'message'	=>	'successfull paypal IPN transaction' ,
						'data'		=>	$myPost
				];
			} 
			else if ( strcmp( $res , 'INVALID' ) == 0 )
			{
				return
				[
					'success'		=>	false ,
					'error'		=>	true ,
					'error_msg'	=>	static::$_codes[ 102 ] ,
					'code'		=>	102
				];
			}
			return
			[
				'success'		=>	false ,
				'error'		=>	true ,
				'error_msg'	=>	static::$_codes[ 103 ] ,
				'code'		=>	103
			];
		}
		
		protected static $_codes = 
		[
			100	=>	'IPN transaction failed: payment_status parameter failed' ,
			101	=>	'IPN transaction failed: receiver_email does not match business_email' ,
			102	=>	'IPN transaction failed: IPN is invalid' ,
			103	=>	'IPN transaction failed: unknown error' ,
			104	=>	'paypal ipn failed: got {curl_error} when processing paypal IPN' ,
			200	=>	'successfull paypal IPN transaction'
		];
	}