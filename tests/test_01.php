<?PHP

include_once('Services/Webservice.php');

class myService extends Services_Webservice
{

    /**
    * This function prints out hello. We also added a dom-node to the wsdl-file. portType-operation-documentation.
    *
    * @param int
    * @param string[][]
    * @param classB[][][]
    * @return classC[][][]
    */	
    public function hello($i,$j,$k)
    {		
    }

    /**
    * This function prints out hello2
    *
    * @param int
    * @param string[][]
    * @param classB[][][]
    * @param classC
    */	
    public function hello2($a,$b,$c,$d)
    {		
    }

}
class classB
{
    /**
    * @var int[][][]
    */
    public $a;
    /**
    * @var classC[][][]
    */
    public $b;
}
class classC
{
    /**
    * @var classD
    */
    public $a;
}
class classD
{
    /**
    * @var classE[][][][]
    */
    public $a;
    /**
    * @var classE[][][][]
    */
    public $b;
    /**
    * @var int[][][][]
    */
    public $c;
}
class classE
{
    /**
    * @var int
    */
    public $a;
}

$myService = new myService("http://example.org","example webservice description",array('uri' => 'http://example.org','encoding'=>SOAP_ENCODED ));
$myService->handle();
?>
