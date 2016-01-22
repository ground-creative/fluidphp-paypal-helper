<?php

	namespace helpers\Paypal;

	class Form
	{	
		public function __construct( $params = array( ) , $isSandBox = false )
		{
			$this->_params = array_merge( $this->_defaultParams , $params );
			$this->_isSandBox = $isSandBox;
			return $this;
		}
		
		public function create( )
		{
			return $this->_setFormParams( );
		}
		
		protected $_params = array( );
		
		protected $_isSandBox = false;
		
		protected $_defaultParams = array
		(
			'amount'			=>	null ,					// required
			'email'			=>	null ,					// required
			'quantity'			=>	null ,					// required
			'request_url'		=>	'https://www.paypal.com/cgi-bin/webscr' ,	
			'sandbox_url'		=>	'https://www.sandbox.paypal.com/cgi-bin/webscr' ,
			'currency'			=>	'EUR' ,
			'url_ok'			=>	null ,
			'url_ko'			=>	null ,
			'ipn_url'			=>	null ,
			'item_name'		=>	null ,
			'item_number'		=>	null ,
			'form_id'			=>	null ,
			'form_tpl'			=>	null
		);
		
		protected function _setFormParams( )
		{
			$params = $this->_params;
			$params[ 'form_url' ] = ( $this->_isSandBox ) ? 
								$params[ 'sandbox_url' ] : $params[ 'request_url' ]; 
			unset( $params[ 'request_url' ] );
			unset( $params[ 'sandbox_url' ] );
			$params[ 'form_tpl' ] = ( $params[ 'form_tpl' ] ) ? 
								$params[ 'form_tpl' ] : __DIR__ . '/form.tpl.html';
			ob_start( );
			require_once( $params[ 'form_tpl' ] );
			$form = ob_get_contents( );
			ob_end_clean( );
			$params[ 'form_id' ] = ( $params[ 'form_id' ] ) ? 
						$params[ 'form_id' ] : 'paypal-form-' . rand( '100' , '999' );
			foreach ( $params as $k => $v )
			{
				$form = str_replace( '{' . $k . '}' , $v , $form );
			}
			return $form;
		}
	}