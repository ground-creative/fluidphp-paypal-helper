<?php

	namespace helpers\Paypal;

	class Transaction
	{
		public function authorize( $amount , $merchantKey , $inputs )
		{
			$this->error = null;
			$signature = $this->_buildVerificationSignature( $amount , $merchantKey , $inputs );
			if ( $inputs[ 'Ds_Signature' ] !== $signature ) // test signature first
			{ 
				$this->_error = $failed_msg . 'generated signature ' . $signature .' does not match';
				return false;
			}
			if ( @$inputs[ 'Ds_ErrorCode' ] ) 			// failed transactions SIS0000-9999
			{
				$this->_error = $failed_msg . 'found Ds_ErrorCode ' . $inputs[ 'Ds_ErrorCode' ];
				return false;
			}
			if ( intval( $inputs[ 'Ds_Response' ] ) > 99 ) 	// visa specific errors, from 0000-0099 is valid
			{
				$error = $inputs[ 'Ds_Response' ] . ' ' . @$this->_codes[ $inputs[ 'Ds_Response' ] ];	
				$this->_error = $failed_msg . 'Ds_Response visa code error ' . $error;
				return false;
			}
			if ( !isset( $inputs[ 'Ds_AuthorisationCode' ] ) || !
				ctype_alnum( $inputs[ 'Ds_AuthorisationCode' ] ) )
			{
				$this->_error = $failed_msg . 'Ds_AuthorisationCode is incorrect format or missing';
				return false;
			}
			return $inputs[ 'Ds_AuthorisationCode' ]; 
		}
		
		public function getError( )
		{
			return $this->_error;
		}
		
		protected $_codes = array
		(
			'0000' => 'Transaction authorized for payments and pre-authorizations' ,
			'0099' => 'Transaction authorized for payments and pre-authorizations' ,
			'0900' => 'Transaction authorized for refunds and confirmations' ,
			'0101' => 'Card expired' ,
			'0102' => 'Card temporarily suspended or under suspicion of fraud' ,
			'0104' => 'Transaction not allowed for the card or terminal' ,
			'0116' => 'Insufficient funds' ,
			'0118' => 'Card not registered' ,
			'0129' => 'Security code (CVV2/CVC2) incorrect' ,
			'0180' => 'Card not recognized' ,
			'0184' => 'Cardholder authentication failed' ,
			'0190' => 'Transaction declined without explanation' ,
			'0191' => 'Wrong expiration date' ,
			'0202' => 'Card temporarily suspended or under suspicion of fraud with confiscation order' ,
			'0912' => 'Issuing bank not available' ,
			'9912' => 'Issuing bank not available'
		);
		
		protected $_error = null;
		
		protected static function _buildVerificationSignature( $amount , $merchantKey , $inputs )
		{
			return strtoupper( sha1( $amount . $inputs[ 'Ds_Order' ] . $inputs[ 'Ds_MerchantCode' ] . 
							$inputs[ 'Ds_Currency' ] . $inputs[ 'Ds_Response' ] . $merchantKey ) );	
		}
	}