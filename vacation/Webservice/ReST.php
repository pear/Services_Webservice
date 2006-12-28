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
require_once 'Services/Webservice/Common.php';

/**
 * Services_Webservice implementation for ReST backend
 *
 * The method call is done through PATH_INFO
 *
 * The PEAR::Services_Webservice class creates web services from your classes
 *
 * @author  Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @package Services_Webservices
 * @version @version@
 */
class Services_Webservice_ReST extends Services_Webservice_Common
{
    /**
     * Automatically handles the incoming request
     *
     * The result depends on how the service was called
     * If a method name is passed as the PATH_INFO, it is used to call it.
     * If the method was not exposed as a web service method, a 404 HTTP
     * status is returned.
     * Otherwise, returns an HTML information page.
     *
     * @access public
     * @todo Add payload to method calls
     * @todo Fix the passing of parameters to the user's class constructor
     */
    public function handle()
    {
        if (isset($_SERVER['PATH_INFO'])) {
            $method = $_SERVER['PATH_INFO'];

            try {
                $this->_handle($method);

            } catch (Services_Webservice_IllegalCallException $e) {
                header('HTTP/1.0 404 Not Found');
            }

        } else {
            header('Content-Type: text/html');
            echo $definition->toHTML();
        }
    }
}

?>