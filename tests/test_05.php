<?php
include_once('Services/Webservice.php');

class classB
{
	/**
	* @var int[]
	*/
	public $i;

	public function __construct()
	{
		$this->i = array(1,2,3,4,5);
	}
}

class myService extends Services_Webservice
{
	/**
	* Says "Hello!"
	*
	* @return classB[]
	*/
	public function hello()
	{
		//create some business logic here
		$classB[] = new SoapVar(new ClassB(),SOAP_ENC_OBJECT,'classB','urn:myService');
		return $classB;
	}
}

$myService = new myService(
	'myService',
	'example webservice description',
	array('uri' => 'myService', 'encoding' => SOAP_ENCODED,'soap_version' => SOAP_1_2)
);
$myService->handle();
?>