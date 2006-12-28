<?php
include_once('Services/Webservice.php');

class classB
{
	/**
	* @var string
	*/
	public $c;

	public function __construct($c)
	{
		$this->c=$c;
	}
}

class myService extends Services_Webservice
{
	/**
	* Says "Hello!"
	*
	* @param int
	* @param string
	* @return classB
	*/
	public function hello($i, $j )
	{
		//create some logic here
		return new SoapVar(new ClassB('myString'),
				SOAP_ENC_OBJECT,
				'classB',
				'urn:myService');
	}
}

$myService = new myService(
	'myService',
	'example webservice description',
	array('uri' => 'myService', 'encoding' => SOAP_ENCODED,'soap_version' => SOAP_1_2)
);
$myService->handle();
?>