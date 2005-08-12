<?PHP

include_once('Services/Webservice.php');

/**
 * My first service with PEAR::Services_Webservice
 */
class myService extends Services_Webservice
{

    /**
    * This function prints out hello. We also added a DOM-node to the WSDL-file. portType-operation-documentation.
    *
    * What about multiline comments?
    *
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

    /**
    * @deprecated
     */
    public function failMe() {
    }

}
class classB
{
    /**
    * 3D coordinates
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
    * Use "c" instead
    * @var classE[][][][]
    * @deprecated
    */
    public $a;
    /**
    * @var classE[][][][]
    * @webservice.hidden
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

$myService = new myService("http://example.org", "example webservice description", array('uri' => 'http://example.org', 'encoding'=>SOAP_ENCODED ));

$myService->handle();

?>