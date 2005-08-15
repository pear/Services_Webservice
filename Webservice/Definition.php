<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Class introspection to expose class as web service
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
     * @access protected
     */
    protected $description;

    /**
     * Simple WSDL types
     *
     * @var    array
     * @access protected
     */
    protected $_simpleTypes = array(
        'string', 'int', 'float', 'bool', 'double', 'integer', 'boolean',
        'varstring', 'varint', 'varfloat', 'varbool', 'vardouble',
        'varinteger', 'varboolean');

    /**
     * Classes are parsed into this struct
     *
     * @var    array
     * @access protected
     */
    protected $_wsdlStruct;

    /**
     * Name of the class from which to create a web service from
     *
     * @var    string
     * @access protected
     */
    protected $_classname;

    /**
     * Exclude these methods from web service
     *
     * @var    array
     * @access protected
     */
    protected $_hiddenMethods;

    /**
     * Some user-overwritten URIs
     *
     * @var    array
     * @access protected
     */
    protected $_URI = array();

    /**
     * Constructor
     *
     * @var    object|string  $class
     * @var    string         $namespace
     * @var    array          $options not currently used
     * @access public
     * @throws Services_Webservice_Definition_NotClassException
     */
    public function __construct($class, $namespace, $options = null)
    {
        if (is_object($class)) {
            $this->_classname = $class->get_class();
        } elseif (is_string($class)) {
            $this->_classname = $class;
        } else {
            throw new Services_Webservice_Definition_NotClassException(
                'Expected a class name or instance.');
        }
        if (trim($namespace) != '') {
            $this->namespace = $namespace;
        } else {
            $this->namespace = 'http://example.org/';
        }

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
     * @retirn array
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
     * @return string the nane of the class being introspected
     * @access public
     */
    public function getClassName()
    {
        return $this->_classname;
    }

    // }}}
    // {{{ setURI()
    /**
     * Sets some URIs
     *
     * Allows to override the default value for some URI set in the definition
     * Currently documented URI identifier:
     *  - WSDL   : URI of the WSDL as it should be made public
     *  - DISCO  : URI of the DISCO as it should be made public
     *  - doc    : URI of the documentation page
     *  - service: Base URI of the service
     *
     * @param  string|array $name URI identifier or array of URI
     * @param  string       $URI
     * @access public
     */
    public function setURI($name, $URI = null)
    {
        if (is_array($name)) {
            $this->_URI = array_merge($this->_URI, $name);
        } elseif (trim($name) && $URI) {
            $this->_URI[$name] = $URI;
        }
    }

    // }}}
    // {{{ getURI()
    /**
     * Returns some URIs
     *
     * @param  string $name URI identifier or array of URI identifiers
     * @return mixed   an array of found URIs if $name is an array,
     *                 a string if $name is a string,
     *                 or and NULL if $name is not found
     * @access public
     * @see setURI()
     */
    public function getURI($name = null)
    {
        if (is_array($name)) {
            return array_intersect_key($this->_URI, array_flip($name));
        } elseif (isset($this->_URI[$name])) {
            return $this->_URI[$name];
        }
        return null;
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
     * @throws Services_Webservice_Definition_Exception
     */
    public function __call($name, $arg)
    {
        list($format) = sscanf($name, 'to%s');
        include_once 'Services/Webservice/Definition/' . basename($format) . '.php';
        $class = 'Services_Webservice_Definition_' . $format;
        if (!class_exists($class)) {
            throw new Services_Webservice_Definition_UnknownFormatException(
                'Unknown definition format.');
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
     * @throws Services_Webservice_Definition_Exception
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
     * @throws Services_Webservice_Definition_IncompleteDocCommentException
     */
    protected function classPropertiesIntoStruct($className)
    {
        if (!isset($this->_wsdlStruct[$className])) {
            $class = new ReflectionClass($className);
            $properties = $class->getProperties();
            $this->_wsdlStruct['class'][$className]['property'] = array();
            for ($i = 0; $i < count($properties); ++$i) {
                if ($properties[$i]->isPublic()) {

                    $propertyName = $properties[$i]->getName();

                    try {
                        $docComments = $this->_parseDocBlock($properties[$i]->getDocComment());
                    } catch (Services_Webservice_Definition_Exception $e) {
                        throw new Services_Webservice_Definition_Exception('Error in ' . $className . '::' . $propertyName . ' property docblock', $e);
                    }

                    if (!$docComments) {
                        throw new Services_Webservice_Definition_NoDocCommentException(
                            'Empty or missing docblock comment for ' . $className . '::' . $propertyName . ' property.');
                    }

                    // Skip property?
                    if (isset($docComments['webservice.hidden'])) {
                        continue;
                    }

                    $_properties =& $this->_wsdlStruct['class'][$className]['property'][$propertyName];

                    // Deprecated?
                    if (isset($docComments['deprecated'])) {
                        $_properties['deprecated'] = true;
                    }

                    // Description
                    $_properties['description'] = @$docComments['shortDescription'];

                    if (!isset($docComments['var'])) {
                        throw new Services_Webservice_Definition_IncompleteDocCommentException('@var missing in docblock comment for ' . $className . '::' . $propertyName . ' property.');
                    }
                    $var = $docComments['var'];
                    $_cleanType = str_replace('[]', '', $var[0][0], $_length);
                    $_typens    = str_repeat('ArrayOf', $_length);

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
     * @throws Services_Webservice_Definition_Exception
     */
    protected function classMethodsIntoStruct()
    {
        $class = new ReflectionClass($this->_classname);

        $docComments = $this->_parseDocBlock($class->getDocComment());

        if (!$docComments) {
            throw new Services_Webservice_Definition_NoDocCommentException('Empty or missing docblock for ' . $this->_classname . ' class');
        }

        $this->_wsdlStruct['service']['description'] = $docComments['shortDescription'];

        $methods = $class->getMethods();

        foreach ($methods as $method) {
            $methodName = $method->getName();
            if ($method->isPublic()
                && !in_array($methodName, $this->_hiddenMethods)) {

                try {
                    $docComments = $this->_parseDocBlock($method->getDocComment());
                } catch (Services_Webservice_Definition_Exception $e) {
                    throw new Services_Webservice_Definition_Exception('Error in ' . $this->_classname . '::' . $methodName . '() docblock', $e);
                }

                if (!$docComments) {
                    throw new Services_Webservice_Definition_NoDocCommentException('empty or missing docblock for ' . $this->_classname . '::' . $methodName . '() method');
                }

                // Skip method?
                if (isset($docComments['webservice.hidden'])) {
                    continue;
                }

                // Deprecated?
                if (isset($docComments['deprecated'])) {
                    $this->_wsdlStruct[$this->_classname]['method'][$methodName]['deprecated'] = true;
                }

                // Description
                $this->_wsdlStruct[$this->_classname]['method'][$methodName]['description'] = @$docComments['shortDescription'];

                // Params
                $param = (array) @$docComments['param'];
                $params = $method->getParameters();
                if (count($params) !=  count($param)) {
                    throw new Services_Webservice_Definition_DocCommentMismatchException(
                        'Docblock comment doesn\'t match ' . $this->_classname
                        . '::' . $methodName . '() signature');
                }
                for ($i = 0; $i < count($params); ++$i) {
                    // Type hint
                    if ($_class = $params[$i]->getClass()) {
                        $_type  = $_class->getName();
                        if ($_type != $param[$i][0]) {
                            throw new Services_Webservice_Definition_DocCommentMismatchException('Docblock comment doesn\'t match ' . $this->_classname . '::' . $methodName . '() type hints');
                        }
                    } else {
                        $_type  = $param[$i][0];
                    }

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
                if ($return = @$docComments['return']) {
                    $_cleanType = str_replace('[]', '', $return[0][0], $_length);
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

    /**
     * Parses a docblock comment
     *
     * @param string $comments
     * @return array
     * @access protected
     * @throws Services_Webservice_Definition_InvalidDocCommentException
     */
    protected function _parseDocBlock($comments)
    {
        $comments  = explode("\n", $comments);

        $info      = array();
        $tag       = '';
        $lastTag   = 0;
        $shortDesc = array();
        $longDesc  = '';
        $inDesc    = 'short';

        foreach ($comments as $line) {
            $line = ltrim(trim($line), "/* \t");
            if ($line && $line{0} == '@') {
                $inDesc = false;
                $line   = explode(' ', strtr($line, "\t", ' '), 2);
                $tag    = substr(array_shift($line), 1);
                $attr   = trim(@$line[0]);
                switch ($tag) {
                    case 'return':
                    case 'var':
                        if (isset($info[$tag])) {
                            throw new Services_Webservice_Definition_InvalidDocCommentException('Only 1 @' . $tag . ' doctag allowed in docblock');
                        }
                        // No "break" here. This is intentional!
                    case 'param':
                        if (!$attr) {
                            throw new Services_Webservice_Definition_InvalidDocCommentException('Incomplete @' . $tag . ' docblock tag');
                        }
                        $attr = explode(' ', $attr, 2);
                        break;
                }
                $info[$tag][] = $attr;
                $lastTag = count($info[$tag]) - 1;

            } elseif ($inDesc) {
                switch ($inDesc) {
                    case 'short':
                        if ($line == '' || $line == '.') {
                            if ($shortDesc) {
                                $inDesc = 'long';
                            }
                            continue 2;
                        } elseif (count($shortDesc) < 3) {
                            $shortDesc[] = $line;
                            if (substr($line, -1) == '.') {
                                $inDesc = 'long';
                            }
                            break;
                        } else {
                            $line = array_pop($shortDesc) . "\n" . $line;
                            $line = array_pop($shortDesc) . "\n" . $line;
                            $inDesc = 'long';
                        }
                        // No "break" here. This is intentional!
                    case 'long':
                        $longDesc .= $line . "\n";
                        break;
                }

            } elseif ($line != '') {
                // Multiline tag
                $info[$tag][$lastTag] .= "\n" . $line;
            }
        }
        if ($shortDesc) {
            $info['shortDescription'] = implode("\n", $shortDesc);
        }
        if (trim($longDesc)) {
            $info['longDescription'] = $longDesc;
        }

        return $info;
    }
}

?>