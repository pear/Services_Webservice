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

// {{{ class Services_Webservice_Definition

/**
 * Class to perform the introspection of the web service
 *
 * Uses Reflection API to do introspection of the class's methods.
 *
 * @author  Manfred Weber <weber@mayflower.de>
 * @author  Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @package Services_Webservices
 * @version
 */
class Services_Webservice_Definition
{
    /**
     * Namespace of the web service
     *
     * @var    string
     * @access public
     */
    public $namespace;

    /**
     * Description of the web service
     *
     * @var    string
     * @access public
     */
    public $description;

    /**
     * Protocol of the web service
     *
     * @var    string
     * @access public
     */
    public $protocol;


    /**
     * SOAP-server options of the web service
     *
     * @var    array
     * @access public
     */
    //public $soapServerOptions = array();

    /**
     * SOAP schema related URIs
     *
     * @access private
     */
    const SOAP_XML_SCHEMA_VERSION  = 'http://www.w3.org/2001/XMLSchema';
    const SOAP_XML_SCHEMA_INSTANCE = 'http://www.w3.org/2001/XMLSchema-instance';
    const SOAP_SCHEMA_ENCODING = 'http://schemas.xmlsoap.org/soap/encoding/';
    const SOAP_XML_SCHEMA_MIME = 'http://schemas.xmlsoap.org/wsdl/mime/';
    const SOAP_ENVELOP         = 'http://schemas.xmlsoap.org/soap/envelope/';
    const SCHEMA_SOAP_HTTP     = 'http://schemas.xmlsoap.org/soap/http';
    const SCHEMA_SOAP          = 'http://schemas.xmlsoap.org/wsdl/soap/';
    const SCHEMA_WSDL          = 'http://schemas.xmlsoap.org/wsdl/';
    const SCHEMA_WSDL_HTTP     = 'http://schemas.xmlsoap.org/wsdl/http/';
    const SCHEMA_DISCO         = 'http://schemas.xmlsoap.org/disco/';
    const SCHEMA_DISCO_SCL     = 'http://schemas.xmlsoap.org/disco/scl/';
    const SCHEMA_DISCO_SOAP    = 'http://schemas.xmlsoap.org/disco/soap/';

    /**
     * Simple WSDL types
     *
     * @var    array
     * @access private
     */
    private $_simpleTypes = array(
        'string', 'int', 'float', 'bool', 'double', 'integer', 'boolean',
        'varstring', 'varint', 'varfloat', 'varbool', 'vardouble',
        'varinteger', 'varboolean');

    /**
     * Classes are parsed into this struct
     *
     * @var    array
     * @access private
     */
    private $_wsdlStruct;

    /**
     * DISCO DOM root node
     *
     * The DISCO DOM object
     *
     * @var    object
     * @access private
     */
    private $_disco;

    /**
     * WSDL DOM root node
     *
     * The WSDL DOM object
     *
     * @var    object
     * @access private
     */
    private $_wsdl;

    /**
     * WSDL-definitions DOM node
     *
     * @var    object
     * @access private
     */
    private $_wsdlDefinitions;

    /**
     * Name of the class from which to create a web service from
     *
     * @var    string
     * @access private
     */
    private $_classname;

    /**
     * exclude these methods from web service
     *
     * @var    array
     * @access private
     */
    private $_hiddenMethods;

    /**
     * error namespace
     *
     * @var    bool
     * @access private
     */
    private $_warningNamespace;

    /**
     * constructor
     *
     * @var    object|string  $class
     * @var    string         $namespace
     * @var    string         $description
     * @var    array          $options not currently used
     * @access public
     * @throws Services_Webservice_Definition_NotClassException
     */
    public function __construct($class, $namespace, $description, $options = null)
    {
        if (is_object($class)) {
            $this->_classname = $class->get_class();
        } elseif (is_string($class)) {
            $this->_classname = $class;
        } else {
            require_once 'Services/Webservice/Definition/Exception.php';
            throw new Services_Webservice_Definition_NotClassException();
        }
        $namespace = trim($namespace);
        if ($namespace != '') {
            $this->namespace = $namespace;
            //$this->namespace .= ((substr($namespace, -1) == '/') ? '' : '/');
        } else {
            $this->namespace = 'http://example.org/';
        }
        $this->_warningNamespace = (bool) (strncmp($this->namespace, 'http://example.org', 18) === 0);

        $this->description = ($description != '') ? $description : 'my example service description';
        /*$this->soapServerOptions = (isset($options) && count($options) > 0) ? $options : array(
            'uri' => $this->namespace,
            'encoding' => SOAP_ENCODED);*/
        $this->_wsdlStruct = array();
        $this->_hiddenMethods = array(
            '__construct',
            '__destruct',
            '__call',
            '__get',
            '__set',
            'handle');
        $this->protocol = 'http';
    }

    // }}}
    // {{{ handleWSDL()
    /**
     * Returns the WSDL file
     *
     * @access public
     * @return string
     */
    public function handleWSDL()
    {
        $this->intoStruct();

        header('Content-Type: text/xml');
        $this->_wsdl = new DOMDocument('1.0' ,'utf-8');
        $this->createWSDLDefinitions();
        $this->createWSDLTypes();
        $this->createWSDLMessages();
        $this->createWSDLPortType();
        $this->createWSDLBinding();
        $this->createWSDLService();
        return $this->_wsdl->saveXML();
    }

    // }}}
    // {{{ createDISCO()
    /**
     * Returns service DISCO information
     *
     * @access public
     * @return string
     */
    public function handleDISCO()
    {
        $this->intoStruct();

        header('Content-Type: text/xml');
        $this->_disco = new DOMDocument('1.0' ,'utf-8');
        $disco_discovery = $this->_disco->createElement('discovery');
        $disco_discovery->setAttribute('xmlns:xsi', self::SOAP_XML_SCHEMA_INSTANCE);
        $disco_discovery->setAttribute('xmlns:xsd', self::SOAP_XML_SCHEMA_VERSION);
        $disco_discovery->setAttribute('xmlns', self::SCHEMA_DISCO );
        $disco_contractref = $this->_disco->createElement('contractRef');
        $urlBase = $this->protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        $disco_contractref->setAttribute('ref',    $urlBase . '?wsdl');
        $disco_contractref->setAttribute('docRef', $urlBase);
        $disco_contractref->setAttribute('xmlns',  self::SCHEMA_DISCO_SCL);
        $disco_soap = $this->_disco->createElement('soap');
        $disco_soap->setAttribute('address',  $urlBase);
        $disco_soap->setAttribute('xmlns:q1', $this->namespace);
        $disco_soap->setAttribute('binding',  'q1:' . $this->_classname);
        $disco_soap->setAttribute('xmlns',    self::SCHEMA_DISCO_SCL);
        $disco_contractref->appendChild($disco_soap);
        $disco_discovery->appendChild($disco_contractref);
        $this->_disco->appendChild($disco_discovery);
        return $this->_disco->saveXML();
    }

    // }}}
    // {{{ handleINFO()
    /**
     * Returns info-site in HTML format
     *
     * @access public
     * @return string
     */
    public function handleINFO()
    {
        $this->intoStruct();

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

        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>' . $this->_classname . ' Web Service</title>
<meta name="generator" content="PEAR::Services_Webservice" />
<style type="text/css">
' . $css . '
</style>
</head>
<body>
<div id="header">
<h1>' . $this->_classname . '</h1>
<p>' . htmlspecialchars($this->description) . '</p>
</div>
<p>The following operations are supported. For a formal definition, please review the <a href="' . htmlentities($_SERVER['PHP_SELF']) . '?WSDL">Service Description</a>.</p>
<ul>';

        foreach ($this->_wsdlStruct[$this->_classname]['method'] as $methodName => $method) {
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
            $html .= sprintf('<li><samp><var class="returnedValue">%s</var> <b class="functionName">%s</b>( <var class="parameter">%s</var> )</samp>%s</li>'
                    , implode(',', $returnValue)
                    , $methodName
                    , implode('</var> , <var class="parameter">', $paramValue)
                    , ((empty($method['description'])) ? '' : ('<br /><span class="description">' . htmlspecialchars($method['description']) . '</span>')));
        }
        $html .= '</ul>
<p><a href="' . htmlentities($_SERVER['PHP_SELF']) . '?DISCO">DISCO</a> makes it possible for clients to reflect against endpoints to discover services and their associated <acronym title="Web Service Description Language">WSDL</acronym> documents.</p>';

        if ($this->_warningNamespace == true
            || strncmp($this->namespace, 'http://example.org', 18) === 0) {
            $html .= '
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

        return $html;
    }

    // }}}
    // {{{ intoStruct()
    /**
     * Parses classes into struct
     *
     * @access protected
     */
    protected function intoStruct()
    {
        static $done = false;
        if (!$done) {
            $this->classMethodsIntoStruct();
            $this->classStructDispatch();
            $done = true;
        }
    }

    // }}}
    // {{{ classStructDispatch()
    /**
     * Dispatches types
     *
     * @access protected
     */
    protected function classStructDispatch()
    {
        foreach ($this->_wsdlStruct[$this->_classname]['method'] as $method) {
            foreach ($method['var'] as $var){
                if (($var['class'] == 1 && $var['length'] == 0)
                    || ($var['class'] == 1 && $var['length'] > 0)) {
                    $this->classPropertiesIntoStruct($var['type']);
                }
                if (($var['array'] == 1 && $var['length'] > 0)
                    || ($var['class'] == 1 && $var['length'] > 0)) {
                    $_typensSource = '';
                    for ($i = $var['length']; $i > 0; --$i) {
                        $_typensSource .= 'ArrayOf';
                        $this->_wsdlStruct['array'][$_typensSource . $var['type']] = substr($_typensSource, 0, strlen($_typensSource) - 7) . $var['type'];
                    }
                }
            }
        }
    }

    // }}}
    // {{{ classPropertiesIntoStruct()
    /**
     * Parses classes properties into struct
     *
     * @var    string
     * @access private
     */
    protected function classPropertiesIntoStruct($className)
    {
        if (!isset($this->_wsdlStruct[$className])) {
            $class = new ReflectionClass($className);
            $properties = $class->getProperties();
            $this->_wsdlStruct['class'][$className]['property'] = array();
            for ($i = 0; $i < count($properties); ++$i) {
                if ($properties[$i]->isPublic()) {
                    preg_match_all('~@var\s(\S+)~', $properties[$i]->getDocComment(), $var);

                    $_cleanType = str_replace('[]', '', $var[1][0], $_length);
                    $_typens    = str_repeat('ArrayOf', $_length);

                    $_properties =& $this->_wsdlStruct['class'][$className]['property'][$properties[$i]->getName()];

                    $_properties['type']     = $_cleanType;
                    $_properties['wsdltype'] = $_typens . $_cleanType;
                    $_properties['length']   = $_length;
                    $_properties['array'] =
                            ($_length > 0 && in_array($_cleanType, $this->_simpleTypes))
                            ? true : false;
                    $isObject = (!in_array($_cleanType, $this->_simpleTypes) && new ReflectionClass($_cleanType))
                            ? true : false;
                    $_properties['class'] = $isObject;
                    if ($isObject == true) {
                        $this->classPropertiesIntoStruct($_cleanType);
                    }
                    if ($_length > 0) {
                        $_typensSource = '';
                        for ($j = $_length; $j > 0;  --$j) {
                            $_typensSource .= 'ArrayOf';
                            $this->_wsdlStruct['array'][$_typensSource.$_cleanType] =
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
     * Parses classes methods into struct
     *
     * @access protected
     */
    protected function classMethodsIntoStruct()
    {
        $class = new ReflectionClass($this->_classname);
        $methods = $class->getMethods();

        foreach ($methods as $method) {
            $methodName = $method->getName();
            if ($method->isPublic()
                && !in_array($methodName, $this->_hiddenMethods)) {

                $docComments = $method->getDocComment();






                // Deprecated?
                if (strpos($docComments, '* @deprecated') !== false) {
                    $this->_wsdlStruct[$this->_classname]['method'][$methodName]['deprecated'] = true;
                }

                // Description
                $_docComments_Description = trim(str_replace('/**', '', substr($docComments, 0, strpos($docComments, '@'))));
                $docComments_Description = trim(substr($_docComments_Description, strpos($_docComments_Description, '*') + 1, strpos($_docComments_Description, '*', 1) - 1));
                $this->_wsdlStruct[$this->_classname]['method'][$methodName]['description'] = $docComments_Description;

                // Params
                preg_match_all('~@param\s(\S+)~', $docComments, $param);
                $params = $method->getParameters();
                for ($i = 0; $i < count($params); ++$i) {
                    $_class = $params[$i]->getClass();
                    $_type  = ($_class) ? $_class->getName() : $param[1][$i];

                    $_cleanType = str_replace('[]', '', $_type, $_length);
                    $_typens    = str_repeat('ArrayOf', $_length);

                    $_var =& $this->_wsdlStruct[$this->_classname]['method'][$methodName]['var'][$i];

                    $_var['name']     = $params[$i]->getName();
                    $_var['wsdltype'] = $_typens . $_cleanType;
                    $_var['type']     = $_cleanType;
                    $_var['length']   = $_length;
                    $_var['array'] =
                            ($_length > 0 && in_array($_cleanType, $this->_simpleTypes))
                            ? true : false;
                    $_var['class'] =
                            (!in_array($_cleanType, $this->_simpleTypes) && new ReflectionClass($_cleanType))
                            ? true : false;
                    $_var['param'] = true;
                }

                // return
                preg_match_all('~@return\s(\S+)~', $docComments, $return);
                if (isset($return[1][0])) {
                    $_cleanType = str_replace('[]', '', $return[1][0], $_length);
                } else {
                    $_cleanType = 'void';
                    $_length = 0;
                }
                $_typens = str_repeat('ArrayOf', $_length);

                $_var =& $this->_wsdlStruct[$this->_classname]['method'][$methodName]['var'][$i];

                $_var['wsdltype'] = $_typens . $_cleanType;
                $_var['type']     = $_cleanType;
                $_var['length']   = $_length;
                $_var['array'] =
                        ($_length > 0 && $_cleanType != 'void' && in_array($_cleanType, $this->_simpleTypes)) ? true : false;
                $_var['class'] =
                        ($_cleanType != 'void' && !in_array($_cleanType, $this->_simpleTypes) && new ReflectionClass($_cleanType))
                        ? true : false;
                $_var['return'] = true;
            }
        }
    }

    // }}}
    // {{{ createWSDLDefinitions()
    /**
     * Creates the definition node
     *
     * @return void
     */
    protected function createWSDLDefinitions()
    {
        /*
        <definitions name="myService"
            targetNamespace="urn:myService"
            xmlns:typens="urn:myService"
            xmlns:xsd="http://www.w3.org/2001/XMLSchema"
            xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
            xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/"
            xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
            xmlns="http://schemas.xmlsoap.org/wsdl/">
        */

        $this->_wsdlDefinitions = $this->_wsdl->createElement('definitions');
        $this->_wsdlDefinitions->setAttribute('name', $this->_classname);
        $this->_wsdlDefinitions->setAttribute('targetNamespace', 'urn:'.$this->_classname);
        $this->_wsdlDefinitions->setAttribute('xmlns:typens', 'urn:'.$this->_classname);
        $this->_wsdlDefinitions->setAttribute('xmlns:xsd', self::SOAP_XML_SCHEMA_VERSION);
        $this->_wsdlDefinitions->setAttribute('xmlns:soap', self::SCHEMA_SOAP);
        $this->_wsdlDefinitions->setAttribute('xmlns:soapenc', self::SOAP_SCHEMA_ENCODING);
        $this->_wsdlDefinitions->setAttribute('xmlns:wsdl', self::SCHEMA_WSDL);
        $this->_wsdlDefinitions->setAttribute('xmlns', self::SCHEMA_WSDL);

        //$this->_wsdlDefinitions->setAttribute('xmlns:mime', self::SOAP_XML_SCHEMA_MIME);
        //$this->_wsdlDefinitions->setAttribute('xmlns:tns', $this->namespace);
        //$this->_wsdlDefinitions->setAttribute('xmlns:http', self::SCHEMA_WSDL_HTTP);

        $this->_wsdl->appendChild($this->_wsdlDefinitions);
    }

    // }}}
    // {{{ createWSDLTypes()
    /**
     * Creates the types node
     *
     * @return void
     */
    protected function createWSDLTypes()
    {
        /*
        <types>
            <xsd:schema xmlns="http://www.w3.org/2001/XMLSchema" targetNamespace="urn:myService"/>
        </types>
        */
        $types  = $this->_wsdl->createElement('types');
        $schema = $this->_wsdl->createElement('xsd:schema');
        $schema->setAttribute('xmlns', self::SOAP_XML_SCHEMA_VERSION );
        $schema->setAttribute('targetNamespace', 'urn:'.$this->_classname);
        $types->appendChild($schema);

        // array
        /*
        <xsd:complexType name="ArrayOfclassC">
            <xsd:complexContent>
                <xsd:restriction base="soapenc:Array">
                    <xsd:attribute ref="soapenc:arrayType" wsdl:arrayType="typens:classC[]"/>
                </xsd:restriction>
            </xsd:complexContent>
        </xsd:complexType>
        */
        if (isset($this->_wsdlStruct['array'])) {

            foreach ($this->_wsdlStruct['array'] as $source => $target) {

                //<s:complexType name="ArrayOfArrayOfInt">
                //<s:sequence>
                //<s:element minOccurs="0" maxOccurs="unbounded" name="ArrayOfInt" nillable="true" type="tns:ArrayOfInt"/>
                //</s:sequence>

                $complexType    = $this->_wsdl->createElement('xsd:complexType');
                $complexContent = $this->_wsdl->createElement('xsd:complexContent');
                $restriction    = $this->_wsdl->createElement('xsd:restriction');
                $attribute      = $this->_wsdl->createElement('xsd:attribute');
                $restriction->appendChild($attribute);
                $complexContent->appendChild($restriction);
                $complexType->appendChild($complexContent);
                $schema->appendChild($complexType);

                $complexType->setAttribute('name', $source);
                $restriction->setAttribute('base', 'soapenc:Array');
                $attribute->setAttribute('ref', 'soapenc:arrayType');

                try {
                    $class = new ReflectionClass($target);
                } catch (Exception $e) {
                }

                if(in_array($target, $this->_simpleTypes)){
                    $attribute->setAttribute('wsdl:arrayType', 'xsd:'    . $target . '[]');
                }elseif(isset($class)){
                    $attribute->setAttribute('wsdl:arrayType', 'typens:' . $target . '[]');
                }else{
                    $attribute->setAttribute('wsdl:arrayType', 'typens:' . $target . '[]');
                }
                unset($class);

            }
        }

        // class
        /*
        <xsd:complexType name="classB">
            <xsd:all>
                <xsd:element name="classCArray" type="typens:ArrayOfclassC" />
            </xsd:all>
        </xsd:complexType>
        */
        if (isset($this->_wsdlStruct['class'])) {
            foreach ($this->_wsdlStruct['class'] as $className=>$classProperty) {
                $complextype = $this->_wsdl->createElement('xsd:complexType');
                $complextype->setAttribute('name', $className);
                $sequence = $this->_wsdl->createElement('xsd:all');
                $complextype->appendChild($sequence);
                $schema->appendChild($complextype);
                foreach ($classProperty['property'] as $classPropertyName => $classPropertyValue) {
                    $element = $this->_wsdl->createElement('xsd:element');
                    $element->setAttribute('name', $classPropertyName);
                    $element->setAttribute('type', ((in_array($classPropertyValue['wsdltype'], $this->_simpleTypes))
                                                            ? 'xsd:'
                                                            : 'typens:') . $classPropertyValue['wsdltype']);
                    $sequence->appendChild($element);
                }
            }
        }

        $this->_wsdlDefinitions->appendChild($types);
    }

    // }}}
    // {{{ createWSDLMessages()
    /**
     * Creates the messages node
     *
     * @return void
     */
    protected function createWSDLMessages()
    {
        /*
        <message name="hello">
            <part name="i" type="xsd:int"/>
            <part name="j" type="xsd:string"/>
        </message>
        <message name="helloResponse">
            <part name="helloResponse" type="xsd:string"/>
        </message>
        */
        foreach ($this->_wsdlStruct[$this->_classname]['method'] as $methodName => $method) {
            $messageInput = $this->_wsdl->createElement('message');
            $messageInput->setAttribute('name', $methodName);
            $messageOutput = $this->_wsdl->createElement('message');
            $messageOutput->setAttribute('name', $methodName . 'Response');
            $this->_wsdlDefinitions->appendChild($messageInput);
            $this->_wsdlDefinitions->appendChild($messageOutput);

            foreach ($method['var'] as $methodVars) {
                if (isset($methodVars['param'])) {
                    $part = $this->_wsdl->createElement('part');
                    $part->setAttribute('name', $methodVars['name']);
                    $part->setAttribute('type', (($methodVars['array'] != 1 && $methodVars['class'] != 1)
                        ? 'xsd:' : 'typens:') . $methodVars['wsdltype']);
                    $messageInput->appendChild($part);
                }
                if (isset($methodVars['return'])) {
                    $part = $this->_wsdl->createElement('part');
                    $part->setAttribute('name', $methodName.'Response'); //$methodVars['wsdltype']);
                    $part->setAttribute('type', (($methodVars['array'] != 1 && $methodVars['class'] != 1)
                        ? 'xsd:' : 'typens:') . $methodVars['wsdltype']);
                    $messageOutput->appendChild($part);
                }
            }
        }
    }

    // }}}
    // {{{ createWSDLBinding()
    /**
     * Create the binding node
     *
     * @return void
     */
    protected function createWSDLBinding()
    {
        /*
        <binding name="myServiceBinding" type="typens:myServicePort">
            <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
                <operation name="hello">
                    <soap:operation soapAction="urn:myServiceAction"/>
                    <input>
                        <soap:body use="encoded" namespace="urn:myService" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
                    </input>
                    <output>
                        <soap:body use="encoded" namespace="urn:myService" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
                    </output>
            </operation>
        </binding>
        */
        $binding = $this->_wsdl->createElement('binding');
        $binding->setAttribute('name', $this->_classname . 'Binding');
        $binding->setAttribute('type', 'typens:' . $this->_classname . 'Port');
        $soap_binding = $this->_wsdl->createElement('soap:binding');
        $soap_binding->setAttribute('style', 'rpc');
        $soap_binding->setAttribute('transport', self::SCHEMA_SOAP_HTTP);
        $binding->appendChild($soap_binding);
        foreach ($this->_wsdlStruct[$this->_classname]['method'] as $methodName => $methodVars) {
            $operation = $this->_wsdl->createElement('operation');
            $operation->setAttribute('name', $methodName);
            $binding->appendChild($operation);
            $soap_operation = $this->_wsdl->createElement('soap:operation');
            $soap_operation->setAttribute('soapAction', 'urn:'.$this->_classname.'Action');
            $operation->appendChild($soap_operation);
            $input  = $this->_wsdl->createElement('input');
            $output = $this->_wsdl->createElement('output');
            $operation->appendChild($input);
            $operation->appendChild($output);
            $soap_body = $this->_wsdl->createElement('soap:body');
            $soap_body->setAttribute('use', 'encoded');
            $soap_body->setAttribute('namespace', 'urn:'.$this->namespace);
            $soap_body->setAttribute('encodingStyle', self::SOAP_SCHEMA_ENCODING );
            $input->appendChild($soap_body);
            $soap_body = $this->_wsdl->createElement('soap:body');
            $soap_body->setAttribute('use', 'encoded');
            $soap_body->setAttribute('namespace', 'urn:'.$this->namespace);
            $soap_body->setAttribute('encodingStyle', self::SOAP_SCHEMA_ENCODING );
            $output->appendChild($soap_body);
        }
        $this->_wsdlDefinitions->appendChild($binding);
    }

    // }}}
    // {{{ createWSDLPortType()
    /**
     * Creates the portType node
     *
     * @return void
     */
    protected function createWSDLPortType()
    {
        /*
        <portType name="myServicePort">
            <operation name="hello">
                <input message="typens:hello"/>
                <output message="typens:helloResponse"/>
            </operation>
        </portType>
        */
        $portType = $this->_wsdl->createElement('portType');
        $portType->setAttribute('name', $this->_classname.'Port');
        foreach ($this->_wsdlStruct[$this->_classname]['method'] as $methodName => $methodVars) {
            $operation = $this->_wsdl->createElement('operation');
            $operation->setAttribute('name', $methodName);
            $portType->appendChild($operation);

            $documentation = $this->_wsdl->createElement('documentation');
            $documentation->appendChild($this->_wsdl->createTextNode($methodVars['description']));
            $operation->appendChild($documentation);

            $input  = $this->_wsdl->createElement('input');
            $output = $this->_wsdl->createElement('output');
            $input->setAttribute('message', 'typens:' . $methodName );
            $output->setAttribute('message', 'typens:' . $methodName . 'Response');
            $operation->appendChild($input);
            $operation->appendChild($output);
        }
        $this->_wsdlDefinitions->appendChild($portType);
    }

    // }}}
    // {{{ createWSDLService()
    /**
     * Creates the service node
     *
     * @return void
     */
    protected function createWSDLService()
    {
        /*
        <service name="myService">
            <port name="myServicePort" binding="typens:myServiceBinding">
                <soap:address location="http://dschini.org/test1.php"/>
            </port>
        </service>
        */
        $service = $this->_wsdl->createElement('service');
        $service->setAttribute('name', $this->_classname);
        $port = $this->_wsdl->createElement('port');
        $port->setAttribute('name', $this->_classname . 'Port');
        $port->setAttribute('binding', 'typens:' . $this->_classname . 'Binding');
        $adress = $this->_wsdl->createElement('soap:address');
        $adress->setAttribute('location', $this->protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
        $port->appendChild($adress);
        $service->appendChild($port);
        $this->_wsdlDefinitions->appendChild($service);
    }
}

?>