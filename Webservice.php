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

// {{{ abstract class Services_Webservice

/**
 * PEAR::Services_Webservice
 *
 * The PEAR::Services_Webservice class creates web services from your classes
 *
 * @author  Manfred Weber <weber@mayflower.de>
 * @author  Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @package Services_Webservices
 * @version @version@
 */
class Services_Webservice
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
     * Name of the class from which to create a web service from
     *
     * @var    string
     * @access protected
     */
    protected $_classname;

    /**
     * Constructor
     *
     * @var    object|string  $class
     * @var    string  $namespace
     * @var    array   $options
     * @access protected
     * @throws Services_Webservice_NotClassException
     */
    protected function __construct($class, $namespace, $options = null)
    {
        if (is_object($class)) {
            $this->_classname = $class->get_class();
        } elseif (is_string($class)) {
            $this->_classname = $class;
        } else {
            throw new Services_Webservice_NotClassException(
                'Expected a class name or instance.');
        }
        if (trim($namespace) == '') {
            $namespace = 'http://example.org/';
        }
        $this->namespace  = $namespace;
        $this->protocol   = 'http';
    }

    // }}}
    // {{{ handle()
    /**
     * Returns a Services_Webservice server instance to handle incoming
     * requests.
     *
     * @param  string  $driver backend service type (for now only SOAP)
     * @var    object|string  $class
     * @var    string  $namespace
     * @var    array   $options
     * @access public
     * @throws Services_Webservice_UnknownDriverException
     * @throws Services_Webservice_NotClassException
     * @webservice.hidden
     */
    public function &factory($driver, $class, $namespace, $options = null)
    {
        $backend = 'Services_Webservice_' . $driver;
        include_once 'Services/Webservice/' . basename($driver) . '.php';
        if (!class_exists($backend)) {
            require_once 'Services/Webservice/Exception.php';
            throw Services_Webservice_UnknownDriverException('Unknown backend driver: ' . $driver);
        }
        $instance = new $backend($class, $namespace, $options);
        return $instance;
    }
}

?>