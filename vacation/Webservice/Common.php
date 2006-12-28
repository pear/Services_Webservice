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
 * Services_Webservice implementation for common PHP-written backend
 *
 * The PEAR::Services_Webservice class creates web services from your classes
 *
 * @author  Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @package Services_Webservices
 * @version @version@
 * @todo Add input/output filters that will decode/encode the data from/to
 *       a specified format (eventually nest filters)
 */
class Services_Webservice_Common extends Services_Webservice
{
    /**
     * Handles the method call itself
     *
     * @param string $methodName the method to call with the payload
     * @access protected
     * @webservice.hidden
     * @throws Services_Webservice_IllegalCallException
     * @todo Add payload to method calls
     * @todo Fix the passing of parameters to the user's class constructor
     */
    protected function _handle($methodName)
    {
        require_once 'Services/Webservice/Definition.php';
        $definition = new Services_Webservice_Definition($this->_classname, $this->namespace);
        $definition->protocol = $this->protocol;

        if (!$definition->isMethodExposed($method, $static)) {
            require_once 'Services/Webservice/Exception.php';
            throw new Services_Webservice_IllegalCallException('Unknown web service resource');
        }

        if ($static) {
            echo call_user_func_array(array($this->_classname, $methodName));

        } else {
            if (!is_object($this->_classnameInstance)) {
                if (!is_null($this->_initParams)) {
                    $this->_classnameInstance = new $this->_classname($this->_initParams);
                } else {
                    $this->_classnameInstance = new $this->_classname();
                }
            }
            echo call_user_func_array(array(&$this->_classnameInstance, $methodName));
        }
    }
}

?>