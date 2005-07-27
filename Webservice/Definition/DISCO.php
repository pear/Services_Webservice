<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Class introspection to expose as web service
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

// {{{ class Services_Webservice_Definition_DISCO

/**
 * Format web service DISCO information
 *
 * @author  Manfred Weber <weber@mayflower.de>
 * @author  Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @package Services_Webservices
 * @version
 */
class Services_Webservice_Definition_DISCO
{
    /**
     * Namespace of the web service
     *
     * @var    string
     * @access protected
     */
    protected $namespace;

    /**
     * Protocol of the web service
     *
     * @var    string
     * @access protected
     */
    protected $protocol;

    /**
     * Name of the class from which to create a web service from
     *
     * @var    string
     * @access protected
     */
    protected $classname;

    /**
     * SOAP schema related URIs
     *
     * @access private
     */
    const SOAP_XML_SCHEMA_VERSION  = 'http://www.w3.org/2001/XMLSchema';
    const SOAP_XML_SCHEMA_INSTANCE = 'http://www.w3.org/2001/XMLSchema-instance';
    const SCHEMA_DISCO         = 'http://schemas.xmlsoap.org/disco/';
    const SCHEMA_DISCO_SCL     = 'http://schemas.xmlsoap.org/disco/scl/';
    const SCHEMA_DISCO_SOAP    = 'http://schemas.xmlsoap.org/disco/soap/';

    /**
     * Constructor
     *
     * @var    object  $definition
     * @access public
     */
    public function __construct(Services_Webservice_Definition &$definition)
    {
        $this->namespace = $definition->namespace;
        $this->protocol  = $definition->protocol;
        $this->classname = $definition->getClassName();
    }

    // }}}
    // {{{ toString()
    /**
     * Returns service DISCO information
     *
     * @access public
     * @return string
     */
    public function toString()
    {
        $disco = new DOMDocument('1.0' ,'utf-8');
        $disco_discovery = $disco->createElement('discovery');
        $disco_discovery->setAttribute('xmlns:xsi', self::SOAP_XML_SCHEMA_INSTANCE);
        $disco_discovery->setAttribute('xmlns:xsd', self::SOAP_XML_SCHEMA_VERSION);
        $disco_discovery->setAttribute('xmlns', self::SCHEMA_DISCO);
        $disco_contractref = $disco->createElement('contractRef');
        $urlBase = $this->protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        $disco_contractref->setAttribute('ref',    $urlBase . '?wsdl');
        $disco_contractref->setAttribute('docRef', $urlBase);
        $disco_contractref->setAttribute('xmlns',  self::SCHEMA_DISCO_SCL);
        $disco_soap = $disco->createElement('soap');
        $disco_soap->setAttribute('address',  $urlBase);
        $disco_soap->setAttribute('xmlns:q1', $this->namespace);
        $disco_soap->setAttribute('binding',  'q1:' . $this->classname);
        $disco_soap->setAttribute('xmlns',    self::SCHEMA_DISCO_SCL);
        $disco_contractref->appendChild($disco_soap);
        $disco_discovery->appendChild($disco_contractref);
        $disco->appendChild($disco_discovery);
        return $disco->saveXML();
    }
}

?>