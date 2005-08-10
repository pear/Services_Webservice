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

// {{{ class Services_Webservice_Definition_WSDL

/**
 * Generator of WSDL document
 *
 * @author  Manfred Weber <weber@mayflower.de>
 * @author  Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @package Services_Webservices
 * @version
 */
class Services_Webservice_Definition_WSDL
{
    /**
     * Namespace of the web service
     *
     * @var    string
     * @access public
     */
    public $namespace;

    /**
     * Protocol of the web service
     *
     * @var    string
     * @access public
     */
    public $protocol;

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
    private $classname;

    /**
     * Class analyzer (introspection)
     *
     * @var    object Instance of Service_Webservice_Definition
     * @access private
     */
    private $_parser;

    /**
     * Constructor
     *
     * @var    object  $definition
     * @access public
     */
    public function __construct(Services_Webservice_Definition &$definition)
    {
        $this->namespace =  $definition->namespace;
        $this->protocol  =  $definition->protocol;
        $this->classname =  $definition->getClassName();
        $this->_parser   =& $definition;
    }

    // }}}
    // {{{ toString()
    /**
     * Returns the WSDL document
     *
     * @access public
     * @return string
     */
    public function toString()
    {
        $this->_wsdlStruct = $this->_parser->getStruct();

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
        $this->_wsdlDefinitions->setAttribute('name', $this->classname);
        $this->_wsdlDefinitions->setAttribute('targetNamespace', 'urn:'.$this->classname);
        $this->_wsdlDefinitions->setAttribute('xmlns:typens', 'urn:'.$this->classname);
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
        $schema->setAttribute('targetNamespace', 'urn:'.$this->classname);
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
            foreach ($this->_wsdlStruct['class'] as $className => $classProperty) {
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
        foreach ($this->_wsdlStruct[$this->classname]['method'] as $methodName => $method) {
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
                if (isset($methodVars['return']) && $methodVars['type']!='void') {
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
        $binding->setAttribute('name', $this->classname . 'Binding');
        $binding->setAttribute('type', 'typens:' . $this->classname . 'Port');
        $soap_binding = $this->_wsdl->createElement('soap:binding');
        $soap_binding->setAttribute('style', 'rpc');
        $soap_binding->setAttribute('transport', self::SCHEMA_SOAP_HTTP);
        $binding->appendChild($soap_binding);
        foreach ($this->_wsdlStruct[$this->classname]['method'] as $methodName => $methodVars) {
            $operation = $this->_wsdl->createElement('operation');
            $operation->setAttribute('name', $methodName);
            $binding->appendChild($operation);
            $soap_operation = $this->_wsdl->createElement('soap:operation');
            $soap_operation->setAttribute('soapAction', 'urn:' . $this->classname.'Action');
            $operation->appendChild($soap_operation);
            $input  = $this->_wsdl->createElement('input');
            $output = $this->_wsdl->createElement('output');
            $operation->appendChild($input);
            $operation->appendChild($output);
            $soap_body = $this->_wsdl->createElement('soap:body');
            $soap_body->setAttribute('use', 'encoded');
            $soap_body->setAttribute('namespace', 'urn:' . $this->namespace);
            $soap_body->setAttribute('encodingStyle', self::SOAP_SCHEMA_ENCODING );
            $input->appendChild($soap_body);
            $soap_body = $this->_wsdl->createElement('soap:body');
            $soap_body->setAttribute('use', 'encoded');
            $soap_body->setAttribute('namespace', 'urn:' . $this->namespace);
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
        $portType->setAttribute('name', $this->classname.'Port');
        foreach ($this->_wsdlStruct[$this->classname]['method'] as $methodName => $methodVars) {
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
        $service->setAttribute('name', $this->classname);

        $documentation = $this->_wsdl->createElement('documentation');
        $documentation->appendChild($this->_wsdl->createTextNode($this->_parser->description));
        $service->appendChild($documentation);

        $port = $this->_wsdl->createElement('port');
        $port->setAttribute('name', $this->classname . 'Port');
        $port->setAttribute('binding', 'typens:' . $this->classname . 'Binding');
        $address = $this->_wsdl->createElement('soap:address');
        if (!($urlService = $this->_parser->getURI('service'))) {
            $urlService = $this->protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        }
        $address->setAttribute('location', $urlService);
        $port->appendChild($address);
        $service->appendChild($port);
        $this->_wsdlDefinitions->appendChild($service);
    }
}

?>