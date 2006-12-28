<?php
include_once('Services/Webservice.php');

class myService extends Services_Webservice
{
	/**
	* Says "Hello!"
	*
	* @param int[]
	* @return string[]
	*/
	public function hello($i)
	{
		$strArray = array();
		$strArray[] = $i[0].'a';
		$strArray[] = $i[1].'b';
		$strArray[] = $i[2].'c';
		$strArray[] = $i[3].'d';
		$strArray[] = $i[4].'e';
		return $strArray;
	}
}

$myService = new myService(
	'myService',
	'example webservice description',
	array('uri' => 'myService', 'encoding' => SOAP_ENCODED,'soap_version' => SOAP_1_2)
);
$myService->handle();
?>