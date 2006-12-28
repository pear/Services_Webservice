<?PHP

include_once('Services/Webservice.php');

/**
 * My first service with PEAR::Services_Webservice,
 * it rocks!
 *
 * Long description of service
 */
class myService
{

    /**
     * This function prints out "hello!"
     * We also added a DOM-node to the WSDL-file
     * portType-operation-documentation.
     *
     * What about multiline comments?
     * I am a long description of the operation
     *
     * @param int
     * @param string[][]
     * @param classB
     * @return classC[][][]
     */
    public function hello($i, $j, classB $k)
    {
    }

    /**
     * This function prints out hello2
     *
     * @param int
     * @param  string[][]
     * @param classB[][][]
     * @param classC
     */
    public function hello2($a, $b, $c, $d)
    {
    }

    /**
     * @deprecated
     * @return string
     */
    public function failMe() {
    }

}
class classB
{
    /**
     * 3D coordinates
     * @var  int[][][]
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
     * Ah ha!
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

$myService =& Services_Webservice::factory('SOAP', 'myService', "http://example.org", array('uri' => 'http://example.org', 'encoding' => SOAP_ENCODED));

$myService->handle();

?>