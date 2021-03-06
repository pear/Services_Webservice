<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Easy Web Service creation
 *
 * PHP 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Services
 * @package    Services_Webservice
 * @author     Manfred Weber <weber@mayflower.de>
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright  2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Services_Webservice
 */

/**
 * Include parent class
 */
require_once 'Services/Webservice.php';

/**
 * Services_Webservice implementation for SOAP backend
 *
 * The PEAR::Services_Webservice class creates web services from your classes
 *
 * @author  Manfred Weber <weber@mayflower.de>
 * @author  Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @package Services_Webservices
 * @version @version@
 */
class Services_Webservice_SOAP extends Services_Webservice
{
    /**
     * SOAP-server options of the web service
     *
     * @var    array
     * @access protected
     */
    protected $soapServerOptions = array();

    /**
     * Constructor
     *
     * @var    object|string  $class
     * @var    string  $namespace
     * @var    array   $options
     * @access public
     */
    public function __construct($class, $namespace, $options = null)
    {
        $this->soapServerOptions['uri'] = isset($options['uri']) ? $options['uri'] : $this->namespace;
        $this->soapServerOptions['encoding'] = isset($options['encoding']) ? $options['encoding'] : SOAP_ENCODED;
        parent::__construct($class, $namespace, $options);
    }

    // }}}
    // {{{ handle()
    /**
     * Automatically handles the incoming request
     *
     * The result depends on how the service was called
     * If the query string is "WSDL" returns the WSDL document
     * If the query string is "DISCO" returns the DISCO document
     * If the payload is not empty, SOAP call is handled by the SOAP server
     * Otherwise, returns an HTML information page
     *
     * @access public
     */
    public function handle()
    {
        $action = strtoupper(@$_SERVER['QUERY_STRING']);
        switch ($action) {
            case 'WSDL':
            case 'DISCO':
                header('Content-Type: text/xml');
                break;
            default:
            	if (isset($_SERVER['HTTP_SOAPACTION'])) {
                    $action = null;
                } else {
                    header('Content-Type: text/html');
                    $action = 'HTML';
                }
        }
        if ($action) {
            require_once 'Services/Webservice/Definition.php';
            $definition = new Services_Webservice_Definition($this->_classname, $this->namespace);
            $definition->protocol = $this->protocol;
            echo $definition->{'to' . $action}();
        } else {
            $this->createServer();
        }
    }

    // }}}
    // {{{ createServer()
    /**
     * Creates the SOAP-server
     *
     * Creates the SOAP server using the PHP SOAP extension,
     * and automatically handle the call.
     *
     * @access private
     */
    private function createServer()
    {
        $server = new SoapServer(null, $this->soapServerOptions);
        $params = $this->_initParams;
        array_unshift($this->_classname, $params);
        call_user_func_array(array(&$server, 'SetClass'), $params);
        $server->handle();
    }
}

?>