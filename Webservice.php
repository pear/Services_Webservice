<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Easy Web Service (SOAP) creation
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
 * @package    Webservice
 * @author     Manfred Weber <weber@mayflower.de>
 * @copyright  2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://dschini.org/Services/
 */

// {{{ abstract class Services_WebService

/**
 * PEAR::Services_Webservice
 *
 * The PEAR::Services_WebService class creates web services from your classes
 *
 * @author  Manfred Weber <weber@mayflower.de>
 * @package Webservices
 * @version
 */
abstract class Services_Webservice
{
    /**
     * Namespace of the webservice
     *
     * @var    string
     * @access public
     */
    public $namespace;

    /**
     * Description of the webservice
     *
     * @var    string
     * @access public
     */
    public $description;

    /**
     * Protocol of the webservice
     *
     * @var    string
     * @access public
     */
    public $protocol;


    /**
     * SOAP-server options of the webservice
     *
     * @var    array
     * @access public
     */
    public $soapServerOptions = array();

    /**
     * SOAP schema related URIs
     *
     * @access private
     */
    const SOAP_XML_SCHEMA_VERSION  = 'http://www.w3.org/2001/XMLSchema';
    const SOAP_XML_SCHEMA_INSTANCE = 'http://www.w3.org/2001/XMLSchema-instance';
    const SOAP_SCHEMA_ENCODING   = 'http://schemas.xmlsoap.org/soap/encoding/';
    const SOAP_XML_SCHEMA_MIME   = 'http://schemas.xmlsoap.org/wsdl/mime/';
    const SOAP_ENVELOP           = 'http://schemas.xmlsoap.org/soap/envelope/';
    const SCHEMA_SOAP_HTTP       = 'http://schemas.xmlsoap.org/soap/http';
    const SCHEMA_SOAP            = 'http://schemas.xmlsoap.org/wsdl/soap/';
    const SCHEMA_WSDL            = 'http://schemas.xmlsoap.org/wsdl/';
    const SCHEMA_WSDL_HTTP       = 'http://schemas.xmlsoap.org/wsdl/http';
    const SCHEMA_DISCO           = 'http://schemas.xmlsoap.org/disco/';
    const SCHEMA_DISCO_SCL       = 'http://schemas.xmlsoap.org/disco/scl/';
    const SCHEMA_DISCO_SOAP      = 'http://schemas.xmlsoap.org/disco/soap/';

    /**
     * Simple WSDL types
     *
     * @var    array
     * @access private
     */
    private $simpleTypes = array(
        'string', 'int', 'float', 'bool', 'double', 'integer', 'boolean',
        'varstring', 'varint', 'varfloat', 'varbool', 'vardouble',
        'varinteger', 'varboolean');

    /**
     * classes are parsed into struct
     *
     * @var    array
     * @access private
     */
    private $wsdlStruct;

    /**
     * disco dom root node
     * the disco dom object
     *
     * @var    object
     * @access private
     */
    private $disco;

    /**
     * wsdl dom root node
     * the wsdl dom object
     *
     * @var    object
     * @access private
     */
    private $wsdl;

    /**
     * wsdl-definitions dom node
     *
     * @var    object
     * @access private
     */
    private $wsdl_definitions;

    /**
     * Name of the class from which to create a webservice from
     *
     * @var    string
     * @access private
     */
    private $classname;

    /**
     * exclude these methods from webservice
     *
     * @var    array
     * @access private
     */
    private $preventMethods;

    /**
     * error namespace
     *
     * @var    bool
     * @access private
     */
    private $warningNamespace;

    /**
     * error description
     *
     * @var    bool
     * @access private
     */
    private $errorDescription;

    /**
     * constructor
     *
     * @var    string
     * @var    string
     * @var    array
     * @access public
     */
    public function __construct($namespace, $description, $options)
    {
        if (isset($namespace) && $namespace != '') {
            $this->warningNamespace   = false;
            $this->errorDescription = false;
            $namespace .= (substr($namespace, -1) == '/') ? '' : '/';
        } else {
            $this->warningNamespace   = true;
            $this->errorDescription = true;
            $namespace = 'http://example.org/';
        }
        $this->namespace   = $namespace;
        $this->description = ($description != '') ? $description : 'my example service description';
        $this->soapServerOptions = (isset($options) && count($options) > 0) ? $options : array(
            'uri' => $this->namespace,
            'encoding' => SOAP_ENCODED);
        $this->wsdlStruct = array();
        $this->preventMethods = array(
            '__construct',
            '__destruct',
            'handle');
        $this->protocol = 'http';
    }

    // }}}
    // {{{ handle()
    /**
     * handle
     *
     * @access public
     */
    public function handle()
    {
        switch (strtolower($_SERVER['QUERY_STRING'])){
            case 'wsdl':
                $this->intoStruct();
                $this->handleWSDL();
                break;
            case 'disco':
                $this->intoStruct();
                $this->handleDISCO();
                break;
            default:
                $this->intoStruct();
                if (!empty($HTTP_RAW_POST_DATA)) {
                    $this->createServer();
                } else {
                    $this->handleINFO();
                }
                break;
        }
    }

    // }}}
    // {{{ createServer()
    /**
     * create the soap-server
     *
     * @access private
     */
    private function createServer()
    {
        $server = new SoapServer(NULL, $this->soapServerOptions);
        $server->SetClass($this->classname);
        $server->handle();
    }

    // }}}
    // {{{ handleWSDL()
    /**
     * handle wsdl
     *
     * @access private
     */
    private function handleWSDL()
    {
        header('Content-Type: text/xml');
        $this->wsdl = new DOMDocument();
        $this->createWSDL_definitions();
        $this->createWSDL_types();
        $this->createWSDL_messages();
        $this->createWSDL_portType();
        $this->createWSDL_binding();
        $this->createWSDL_service();
        echo $this->wsdl->saveXML();
    }

    // }}}
    // {{{ createDISCO()
    /**
     * handle disco
     *
     * @access private
     */
    private function handleDISCO()
    {
        header('Content-Type: text/xml');
        $this->disco = new DOMDocument();
        $disco_discovery = $this->disco->createElement('discovery');
        $disco_discovery->setAttribute('xmlns:xsi', self::SOAP_XML_SCHEMA_INSTANCE);
        $disco_discovery->setAttribute('xmlns:xsd', self::SOAP_XML_SCHEMA_VERSION);
        $disco_discovery->setAttribute('xmlns', self::SCHEMA_DISCO );
        $disco_contractref = $this->disco->createElement('contractRef');
        $urlBase = $this->protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        $disco_contractref->setAttribute('ref', $urlBase . '?wsdl');
        $disco_contractref->setAttribute('docRef', $urlBase);
        $disco_contractref->setAttribute('xmlns', self::SCHEMA_DISCO_SCL);
        $disco_soap = $this->disco->createElement('soap');
        $disco_soap->setAttribute('address', $urlBase);
        $disco_soap->setAttribute('xmlns:q1', $this->namespace);
        $disco_soap->setAttribute('binding', 'q1:' . $this->classname);
        $disco_soap->setAttribute('xmlns', self::SCHEMA_DISCO_SCL);
        $disco_contractref->appendChild($disco_soap);
        $disco_discovery->appendChild($disco_contractref);
        $this->disco->appendChild($disco_discovery);
        echo $this->disco->saveXML();
    }

    // }}}
    // {{{ handleINFO()
    /**
     * handle info-site
     *
     * @access private
     */
    private function handleINFO()
    {
        header('Content-Type: text/html');

        $css = '
body {
    margin: 0px;
    padding: 10px;
    font-family: sans-serif;
}
#header {
    background-color: #339900;
    color: #FFFFFF;
    padding: 5px 10px;
    margin: -10px;
}
h1 {
    font-size: xx-large;
    color: #CCFF99;
}
#header p {
    font-size: large;
}

dt {
    margin-top: 1em;
}

.description {
    padding-left: 1.5em;
    margin-bottom: 1.5em;
}

a:link {
    color: #006600;
}

a:visited {
    color: #030;
}

a:hover {
    color: #003300;
}
';

        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>' . $this->classname . ' WebService</title>
<meta name="generator" content="PEAR::Services_Webservice" />
<style type="text/css">
' . $css . '
</style>
</head>
<body>
<div id="header">
<h1>' . $this->classname . '</h1>
<p>' . htmlspecialchars($this->description) . '</p>
</div>
<p>The following operations are supported. For a formal definition, please review the <a href="' . htmlentities($_SERVER['PHP_SELF']) . '?WSDL">Service Description</a>.</p>
<ul>';

        foreach ($this->wsdlStruct[$this->classname]['method'] as $methodName => $method) {
            $paramValue = array();
            foreach ($method['var'] AS $methodVars) {
                if (isset($methodVars['param'])) {
                    $paramValue[] = $methodVars['type']
                                     . str_repeat('[]', $methodVars['length']);
                }
            }
            $returnValue = array();
            foreach ($method['var'] AS $methodVars) {
                if (isset($methodVars['return'])) {
                    $returnValue[] = $methodVars['type']
                                     . str_repeat('[]', $methodVars['length']);
                }
            }
            echo sprintf('<li><samp><var class="returnedValue">%s</var> <b class="functionName">%s</b>( <var class="parameter">%s</var> )</samp>%s</li>'
                    , implode(',', $returnValue)
                    , $methodName
                    , implode('</var> , <var class="parameter">', $paramValue)
                    , ((empty($method['description'])) ? '' : ('<br /><span class="description">' . htmlspecialchars($method['description']) . '</span>')));
        }
        echo '</ul>
<p><a href="' . htmlentities($_SERVER['PHP_SELF']) . '?DISCO">DISCO</a> makes it possible for clients to reflect against endpoints to discover services and their associated <acronym title="Web Service Description Language">WSDL</acronym> documents.</p>';

        if ($this->warningNamespace == true
            || $this->namespace == 'http://example.org/') {
            echo '
<p class="warning"><strong>This web service is using http://example.org/ as its default namespace.<br />
Recommendation: Change the default namespace before the <acronym title="eXtensible Markup Language">XML</acronym> Web service is made public.</strong></p>

<p>Each XML Web service needs a unique namespace in order for client applications to distinguish it from other services on the Web. http://example.org/ is available for XML Web services that are under development, but published XML Web services should use a more permanent namespace.<br />
Your XML Web service should be identified by a namespace that you control. For example, you can use your company`s Internet domain name as part of the namespace. Although many XML Web service namespaces look like <acronym title="Uniform Resource Locators">URLs</acronym>, they need not point to actual resources on the Web. (XML Web service namespaces are <acronym title="Uniform Resouce Identifiers">URIs</acronym>.)</p>

<p>For more details on XML namespaces, see the <acronym title="World Wide Web Consortium">W3C</acronym> recommendation on <a href="http://www.w3.org/TR/REC-xml-names/">Namespaces in XML</a>.<br />
For more details on <acronym title="Web Service Description Language">WSDL</acronym>, see the <a href="http://www.w3.org/TR/wsdl">WSDL Specification</a>.<br />
For more details on URIs, see <a href="http://www.ietf.org/rfc/rfc2396.txt"><acronym title="Request For Comment">RFC</acronym> 2396</a>.</p>
<p><small>Powered by PEAR <a href="http://pear.php.net/">http://pear.php.net</a></small></p>
</body>
</html>';

        }
    }

    // }}}
    // {{{ intoStruct()
    /**
     * parse classes into struct
     *
     * @access private
     */
    protected function intoStruct()
    {
        $class = new ReflectionObject($this);
        $this->classname = $class->getName();
        $this->classMethodsIntoStruct();
        $this->classStructDispatch();
    }

    // }}}
    // {{{ classStructDispatch()
    /**
     * dispatch types
     *
     * @access private
     */
    protected function classStructDispatch()
    {
        foreach ($this->wsdlStruct[$this->classname]['method'] as $method) {
            foreach ($method['var'] as $var){
                if (($var['class'] == 1 && $var['length'] == 0)
                    || ($var['class'] == 1 && $var['length'] > 0)) {
                    $this->classPropertiesIntoStruct($var['type']);
                }
                if (($var['array'] == 1 && $var['length'] > 0)
                    || ($var['class'] == 1 && $var['length'] > 0)) {
                    $_typensSource = '';
                    for ($i = $var['length']; $i > 0; --$i) {
                        // 7 is strlen('ArrayOf')
                        $_typensSource .= 'ArrayOf';
                        $this->wsdlStruct['array'][$_typensSource . $var['type']] = substr($_typensSource, 0, strlen($_typensSource) - 7) . $var['type'];
                    }
                }
            }
        }
    }

    // }}}
    // {{{ classPropertiesIntoStruct()
    /**
     * parse classes properties into struct
     *
     * @var    string
     * @access private
     */
    protected function classPropertiesIntoStruct($className)
    {
        if (!isset($this->wsdlStruct[$className])) {
            $class = new ReflectionClass($className);
            $properties = $class->getProperties();
            $this->wsdlStruct['class'][$className]['property'] = array();
            for ($i = 0; $i < count($properties); ++$i) {
                if ($properties[$i]->isPublic()) {
                    preg_match_all('~@var\s(\S+)~', $properties[$i]->getDocComment(), $var);

                    $_cleanType = str_replace('[]', '', $var[1][0], $_length);
                    $_typens    = str_repeat('ArrayOf', $_length);

                    $this->wsdlStruct['class'][$className]['property'][$properties[$i]->getName()]['type'] =
                            $_cleanType;
                    $this->wsdlStruct['class'][$className]['property'][$properties[$i]->getName()]['wsdltype'] =
                            $_typens.$_cleanType;
                    $this->wsdlStruct['class'][$className]['property'][$properties[$i]->getName()]['length'] =
                            $_length;
                    $this->wsdlStruct['class'][$className]['property'][$properties[$i]->getName()]['array'] =
                            ($_length > 0 && in_array($_cleanType, $this->simpleTypes))
                            ? true : false;
                    $isObject = (!in_array($_cleanType, $this->simpleTypes) && new ReflectionClass($_cleanType))
                            ? true : false;
                    $this->wsdlStruct['class'][$className]['property'][$properties[$i]->getName()]['class'] =
                            $isObject;
                    if ($isObject == true) {
                        $this->classPropertiesIntoStruct($_cleanType);
                    }
                    if ($_length > 0) {
                        $_typensSource = '';
                        for ($j = $_length; $j > 0;  --$j) {
                            // 7 = strlen('ArrayOf')
                            $_typensSource .= 'ArrayOf';
                            $this->wsdlStruct['array'][$_typensSource.$_cleanType] =
                                    substr($_typensSource, 0, strlen($_typensSource) - 7) . $_cleanType;
                        }
                    }
                }
            }
        }
    }

    // }}}
    // {{{ classMethodsIntoStruct()
    /**
     * parse classes methods into struct
     *
     * @access private
     */
    protected function classMethodsIntoStruct()
    {
        $class = new ReflectionClass($this->classname);
        $methods = $class->getMethods();
        // params
        foreach ($methods AS $method) {
            if ($method->isPublic()
                && !in_array($method->getName(), $this->preventMethods)) {
                $docComments = $method->getDocComment();
                $_docComments_Description = trim(str_replace('/**', '', substr($docComments, 0, strpos($docComments, '@'))));
                $docComments_Description = trim(substr($_docComments_Description, strpos($_docComments_Description, '*') + 1, strpos($_docComments_Description, '*', 1) - 1));
                $this->wsdlStruct[$this->classname]['method'][$method->getName()]['description'] = $docComments_Description;
                preg_match_all('~@param\s(\S+)~', $docComments, $param);
                preg_match_all('~@return\s(\S+)~', $method->getDocComment(), $return);
                $params = $method->getParameters();
                for ($i = 0; $i < count($params); ++$i) {
                    $_class = $params[$i]->getClass();
                    $_type  = ($_class) ? $_class->getName() : $param[1][$i];

                    $_cleanType = str_replace('[]', '', $_type, $_length);
                    $_typens    = str_repeat('ArrayOf', $_length);

                    $this->wsdlStruct[$this->classname]['method'][$method->getName()]['var'][$i]['name'] =
                            $params[$i]->getName();
                    $this->wsdlStruct[$this->classname]['method'][$method->getName()]['var'][$i]['wsdltype'] =
                            $_typens . $_cleanType;
                    $this->wsdlStruct[$this->classname]['method'][$method->getName()]['var'][$i]['type'] =
                            $_cleanType;
                    $this->wsdlStruct[$this->classname]['method'][$method->getName()]['var'][$i]['length'] =
                            $_length;
                    $this->wsdlStruct[$this->classname]['method'][$method->getName()]['var'][$i]['array'] =
                            ($_length > 0 && in_array($_cleanType, $this->simpleTypes))
                            ? true : false;
                    $this->wsdlStruct[$this->classname]['method'][$method->getName()]['var'][$i]['class'] =
                            (!in_array($_cleanType, $this->simpleTypes) && new ReflectionClass($_cleanType))
                            ? true : false;
                    $this->wsdlStruct[$this->classname]['method'][$method->getName()]['var'][$i]['param'] = true;
                }
                // return
                if (isset($return[1][0])) {
                    $_cleanType = str_replace('[]', '', $return[1][0], $_length);
                } else {
                    $_cleanType = 'void';
                    $_length = 0;
                }
                $_typens = str_repeat('ArrayOf', $_length);

                $this->wsdlStruct[$this->classname]['method'][$method->getName()]['var'][$i]['wsdltype'] =
                        $_typens.$_cleanType;
                $this->wsdlStruct[$this->classname]['method'][$method->getName()]['var'][$i]['type'] = $_cleanType;
                $this->wsdlStruct[$this->classname]['method'][$method->getName()]['var'][$i]['length'] = $_length;
                $this->wsdlStruct[$this->classname]['method'][$method->getName()]['var'][$i]['array'] =
                        ($_length > 0 && $_cleanType != 'void' && in_array($_cleanType, $this->simpleTypes)) ? true : false;
                $this->wsdlStruct[$this->classname]['method'][$method->getName()]['var'][$i]['class'] =
                        ($_cleanType != 'void' && !in_array($_cleanType, $this->simpleTypes) && new ReflectionClass($_cleanType))
                        ? true : false;
                $this->wsdlStruct[$this->classname]['method'][$method->getName()]['var'][$i]['return'] = true;
            }
        }
    }

    // }}}
    // {{{ createWSDL_definitions()
    /**
     * Create the definition node
     *
     * @return void
     */
    protected function createWSDL_definitions()
    {
        $this->wsdl_definitions = $this->wsdl->createElement('wsdl:definitions');
        $this->wsdl_definitions->setAttribute('xmlns:soap', self::SCHEMA_SOAP);
        $this->wsdl_definitions->setAttribute('xmlns:soapenc', self::SOAP_SCHEMA_ENCODING);
        $this->wsdl_definitions->setAttribute('xmlns:mime', self::SOAP_XML_SCHEMA_MIME);
        $this->wsdl_definitions->setAttribute('xmlns:tns', $this->namespace);
        $this->wsdl_definitions->setAttribute('xmlns:s', self::SOAP_XML_SCHEMA_VERSION);
        $this->wsdl_definitions->setAttribute('xmlns:http', self::SCHEMA_WSDL_HTTP);
        $this->wsdl_definitions->setAttribute('targetNamespace', $this->namespace);
        $this->wsdl_definitions->setAttribute('xmlns:wsdl', self::SCHEMA_WSDL);
        $this->wsdl->appendChild($this->wsdl_definitions);
    }

    // }}}
    // {{{ createWSDL_types()
    /**
     * Create the types node
     *
     * @return void
     */
    protected function createWSDL_types()
    {
        $types  = $this->wsdl->createElement('wsdl:types');
        $schema = $this->wsdl->createElement('s:schema');
        //$schema->setAttribute('xmlns', self::SOAP_XML_SCHEMA_VERSION);
        $schema->setAttribute('elementFormDefault', 'qualified');
        $schema->setAttribute('targetNamespace', $this->namespace);
        $types->appendChild($schema);

        // methods
        foreach ($this->wsdlStruct[$this->classname]['method'] as $methodName=>$method) {
            $methodIn  = false;
            $methodOut = false;
            foreach ($method['var'] as $methodVars) {
                if (isset($methodVars['param'])) {
                    if ($methodIn == false) {
                        $methodIn = $this->wsdl->createElement('s:element');
                        $methodIn->setAttribute('name', $methodName);
                        $complextype = $this->wsdl->createElement('s:complexType');
                        $sequence = $this->wsdl->createElement('s:sequence');
                        $complextype->appendChild($sequence);
                        $methodIn->appendChild($complextype);
                        $schema->appendChild($methodIn);
                    }
                    $element = $this->wsdl->createElement('s:element');
                    $element->setAttribute('minOccurs', '0');
                    $element->setAttribute('maxOccurs', '1');
                    $element->setAttribute('name', $methodVars['name']);
                    $element->setAttribute('type', (($methodVars['array'] != 1 && $methodVars['class'] != 1)
                        ? 's:' : 'tns:') . $methodVars['wsdltype']);
                    $sequence->appendChild($element);
                }

                if (isset($methodVars['return'])) {
                    if ($methodOut == false) {
                        $methodOut = $this->wsdl->createElement('s:element');
                        $methodOut->setAttribute('name', $methodName . 'Response');
                        $complextype = $this->wsdl->createElement('s:complexType');
                        if ($methodVars['type'] != 'void') {
                            $sequence = $this->wsdl->createElement('s:sequence');
                            $complextype->appendChild($sequence);
                        }
                        $methodOut->appendChild($complextype);
                        $schema->appendChild($methodOut);
                    }
                    if ($methodVars['type'] != 'void') {
                        $element = $this->wsdl->createElement('s:element');
                        $element->setAttribute('minOccurs', '1');
                        $element->setAttribute('maxOccurs', '1');
                        $element->setAttribute('name', $methodName.'Response');
                        $element->setAttribute('type', (($methodVars['array'] != 1 && $methodVars['class'] != 1)
                            ? 's:' : 'tns:') . $methodVars['wsdltype']);
                        $sequence->appendChild($element);
                    }
                }
            }
        }

        // array
        foreach ($this->wsdlStruct['array'] as $source => $target) {
            $complextype = $this->wsdl->createElement('s:complexType');
            $complextype->setAttribute('name', $source);
            $sequence = $this->wsdl->createElement('s:sequence');
            $complextype->appendChild($sequence);
            $schema->appendChild($complextype);
            $element = $this->wsdl->createElement('s:element');
            $element->setAttribute('minOccurs', '0');
            $element->setAttribute('maxOccurs', 'unbounded');
            $element->setAttribute('nillable', 'true');
            $element->setAttribute('name', $target);
            $element->setAttribute('type', ((in_array($target, $this->simpleTypes)) ? 's:' : 'tns:') . $target);
            $sequence->appendChild($element);
        }

        // class
        if (isset($this->wsdlStruct['class'])) {
            foreach ($this->wsdlStruct['class'] as $className=>$classProperty) {
                $complextype = $this->wsdl->createElement('s:complexType');
                $complextype->setAttribute('name', $className);
                $sequence = $this->wsdl->createElement('s:sequence');
                $complextype->appendChild($sequence);
                $schema->appendChild($complextype);
                foreach ($classProperty['property'] as $classPropertyName => $classPropertyValue) {
                    $element = $this->wsdl->createElement('s:element');
                    $element->setAttribute('minOccurs', '0');
                    $element->setAttribute('maxOccurs', '1');
                    $element->setAttribute('name', $classPropertyName);
                    $element->setAttribute('type', ((in_array($classPropertyValue['wsdltype'], $this->simpleTypes)) ? 's:' : 'tns:') . $classPropertyValue['wsdltype']);
                    $sequence->appendChild($element);
                }
            }
        }

        $this->wsdl_definitions->appendChild($types);
    }

    // }}}
    // {{{ createWSDL_messages()
    /**
     * Create the messages node
     *
     * @return void
     */
    protected function createWSDL_messages()
    {
        foreach ($this->wsdlStruct[$this->classname]['method'] AS $methodName => $methodVars){
            $messageInput = $this->wsdl->createElement('wsdl:message');
            $messageInput->setAttribute('name', $methodName . 'SoapIn');
            $messageOutput = $this->wsdl->createElement('wsdl:message');
            $messageOutput->setAttribute('name', $methodName . 'SoapOut');
            $partInput = $this->wsdl->createElement('wsdl:part');
            $partInput->setAttribute('name', 'parameters');
            $partInput->setAttribute('element', 'tns:' . $methodName);
            $partOutput = $this->wsdl->createElement('wsdl:part');
            $partOutput->setAttribute('name', 'parameters');
            $partOutput->setAttribute('element', 'tns:' . $methodName . 'Response');
            $messageInput->appendChild($partInput);
            $messageOutput->appendChild($partOutput);
            $this->wsdl_definitions->appendChild($messageInput);
            $this->wsdl_definitions->appendChild($messageOutput);
        }
    }

    // }}}
    // {{{ createWSDL_binding()
    /**
     * Create the binding node
     *
     * @return void
     */
    protected function createWSDL_binding()
    {
        $binding = $this->wsdl->createElement('wsdl:binding');
        $binding->setAttribute('name', $this->classname . 'Soap');
        $binding->setAttribute('type', 'tns:' . $this->classname . 'Soap');
        $soap_binding = $this->wsdl->createElement('soap:binding');
        //$soap_binding->setAttribute('style', $this->bindingStyle);
        $soap_binding->setAttribute('transport', self::SCHEMA_SOAP_HTTP);
        $binding->appendChild($soap_binding);
        foreach ($this->wsdlStruct[$this->classname]['method'] AS $methodName => $methodVars) {
            $operation = $this->wsdl->createElement('wsdl:operation');
            $operation->setAttribute('name', $methodName);
            $binding->appendChild($operation);
            $soap_operation = $this->wsdl->createElement('soap:operation');
            $soap_operation->setAttribute('soapAction', $this->namespace.$methodName);
            $soap_operation->setAttribute('style', 'document');
            $operation->appendChild($soap_operation);
            $input  = $this->wsdl->createElement('wsdl:input');
            $output = $this->wsdl->createElement('wsdl:output');
            $operation->appendChild($input);
            $operation->appendChild($output);
            $soap_body = $this->wsdl->createElement('soap:body');
            $soap_body->setAttribute('use', 'literal');
            $input->appendChild($soap_body);
            $soap_body = $this->wsdl->createElement('soap:body');
            $soap_body->setAttribute('use', 'literal');
            $output->appendChild($soap_body);
        }
        $this->wsdl_definitions->appendChild($binding);
    }

    // }}}
    // {{{ createWSDL_portType()
    /**
     * Create the portType node
     *
     * @return void
     */
    protected function createWSDL_portType()
    {
        $portType = $this->wsdl->createElement('wsdl:portType');
        $portType->setAttribute('name', $this->classname.'Soap');
        foreach ($this->wsdlStruct[$this->classname]['method'] AS $methodName => $methodVars) {
            $operation = $this->wsdl->createElement('wsdl:operation');
            $operation->setAttribute('name', $methodName);
            $portType->appendChild($operation);

            $documentation = $this->wsdl->createElement('wsdl:documentation');
            $documentation->appendChild($this->wsdl->createTextNode($methodVars['description']));
            $operation->appendChild($documentation);

            $input  = $this->wsdl->createElement('wsdl:input');
            $output = $this->wsdl->createElement('wsdl:output');
            $input->setAttribute('message', 'tns:' . $methodName . 'SoapIn');
            $output->setAttribute('message', 'tns:' . $methodName . 'SoapOut');
            $operation->appendChild($input);
            $operation->appendChild($output);
        }
        $this->wsdl_definitions->appendChild($portType);
    }

    // }}}
    // {{{ createWSDL_service()
    /**
     * Create the service node
     *
     * @return void
     */
    protected function createWSDL_service()
    {
        $service = $this->wsdl->createElement('wsdl:service');
        $service->setAttribute('name', $this->classname);
        $port = $this->wsdl->createElement('wsdl:port');
        $port->setAttribute('name', $this->classname . 'Soap');
        $port->setAttribute('binding', 'tns:' . $this->classname . 'Soap');
        $adress = $this->wsdl->createElement('soap:address');
        $adress->setAttribute('location', $this->protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
        $port->appendChild($adress);
        $service->appendChild($port);
        $this->wsdl_definitions->appendChild($service);
    }
}

?>
