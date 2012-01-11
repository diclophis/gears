<?php

/*
	Special case of controller that is used to handle RPC.
	Instead of processing the action through a template, this controller returns a serialized value of the result reasdy for consumtption on the client side
	It also checks to see if the request came over the correct port, and the authentication tokens match up
*/

class RemoteProcedureController extends Controller {

	public static $salt = "h0td0g";
	public $rpc_port = 80;

	function before_call ($method)
	{
		$port = $this->port();
		if ($port != $this->rpc_port) {
			throw new GearsException("Wrong RPC Port");
		}

		if (!isset($this->params['api_user_name']) or !isset($this->params['api_key'])) {
			throw new GearsException("api_user_name or api_key is missing");
		}

		$api_user_name = $this->params['api_user_name'];
		$api_key = $this->params['api_key'];
		$rpc_authentication_tokens = Config::settings()->rpc;

		$api_user_name_exists = ($rpc_authentication_tokens[$api_user_name]) !== null;
		$api_key_authorized = ($rpc_authentication_tokens[$api_user_name] == $api_key);

		if ($api_user_name_exists && $api_key_authorized) {
			return true;
		} else{
			throw new GearsException(sprintf("invalid api_user_name and api_key combination(%s:%s)", $api_user_name, $api_key));
		}
	}

	function index ()
	{
		return $this->render_to_php();
	}

	function mirror ()
	{
		return $this->render_to_php($this->params);
	}

	function render_to_php ($value = null)
	{
		return serialize($value);
	}

}

?>
