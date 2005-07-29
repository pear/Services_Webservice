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
 * Include exception class declarations
 */
require_once 'Services/Webservice/Definition/Exception.php';


/**
 * Class to perform the introspection of the web service
 *
 * Uses Reflection API and parses docblock comments to do introspection
 * of the class's methods.
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
     * Name of the class from which to create a web service from
     *
     * @var    string
     * @access private
     */
    private $_classname;

    /**
     * Exclude these methods from web service
     *
     * @var    array
     * @access private
     */
    private $_hiddenMethods;

    /**
     * Constructor
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
            throw new Services_Webservice_Definition_NotClassException('Expected a class name or instance.');
        }
        if (trim($namespace) != '') {
            $this->namespace = $namespace;
            //$this->namespace .= ((substr($namespace, -1) == '/') ? '' : '/');
        } else {
            $this->namespace = 'http://example.org/';
        }

        $this->description = $description;

        $this->_wsdlStruct = array();
        $this->_hiddenMethods = array(
            '__construct',
            '__destruct',
            '__call',
            '__get',
            '__set',
            '__sleep',
            '__wakeup',
            $this->_classname);
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
    // {{{ getStruct()
    /**
     * Returns the structure of classes as parsed by intoStruct
     *
     * @access public
     */
    public function getStruct()
    {
        $this->intoStruct();
        return $this->_wsdlStruct;
    }

    // }}}
    // {{{ getClassName()
    /**
     * Returns the name of the class being defined
     *
     * @access public
     */
    public function getClassName()
    {
        return $this->_classname;
    }

    // }}}
    // {{{ __call()
    /**
     * Returns web service definition in requested format
     *
     * Catch-all for toWSDL(), toHTML() and toDISCO()
     *
     * @param  string  $name method's name being actually called
     * @param  array
     * @return string
     * @access public
     * @throws Services_Webservice_Definition_UnknownFormatException
     */
    public function __call($name, $arg)
    {
        list($format) = sscanf($name, 'to%s');
        include_once 'Services/Webservice/Definition/' . basename($format) . '.php';
        $class = 'Services_Webservice_Definition_' . $format;
        if (!class_exists($class)) {
            throw new Services_Webservice_Definition_UnknownFormatException('Unknown definition format.');
        }
        $formatter = new $class($this);
        return $formatter->toString();
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
     * @throws Services_Webservice_Definition_NoDocCommentException
     */
    protected function classPropertiesIntoStruct($className)
    {
        if (!isset($this->_wsdlStruct[$className])) {
            $class = new ReflectionClass($className);
            $properties = $class->getProperties();
            $this->_wsdlStruct['class'][$className]['property'] = array();
            for ($i = 0; $i < count($properties); ++$i) {
                if ($properties[$i]->isPublic()) {
                    $docComments = $properties[$i]->getDocComment();

                    $propertyName = $properties[$i]->getName();

                    if (!trim($docComments)) {
                        throw new Services_Webservice_Definition_NoDocCommentException('Property ' . $class . '::' . $propertyName . ' is missing docblock comment.');
                    }

                    preg_match_all('~\* @var\s(\S+)~', $docComments, $var);

                    $_cleanType = str_replace('[]', '', $var[1][0], $_length);
                    $_typens    = str_repeat('ArrayOf', $_length);

                    $_properties =& $this->_wsdlStruct['class'][$className]['property'][$propertyName];

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
     * @throws Services_Webservice_Definition_NoDocCommentException
     * @throws Services_Webservice_Definition_DocCommentMismatchException
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

                if (!trim($docComments)) {
                    throw new Services_Webservice_Definition_NoDocCommentException('Method ' . $this->_classname . '::' . $methodName . '() is missing docblock comment.');
                }

                // Skip method?
                if (strpos($docComments, '* @webservice.hidden') !== false) {
                    continue;
                }

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
                if (count($params) !=  count($param)) {
                    throw new Services_Webservice_Definition_DocCommentMismatchException('Docblock comment doesn\'t match ' . $this->_classname . '::' . $methodName . '() signature.');
                }
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
}

?>