<?php
include_once('Services/Webservice.php');

class myService extends Services_Webservice
{
	/**
	* Says "Hello!"
	*
	* @param int
	* @return string
	*/
	public function hello($i )
	{
		//create some logic here
		return 'myString';
	}
}
	
$myService = new myService(
		'myService',
		'example webservice description',
		array('uri' => 'myService', 'encoding' => SOAP_ENCODED,'soap_version' => SOAP_1_2)
);
$myService->handle();
?>