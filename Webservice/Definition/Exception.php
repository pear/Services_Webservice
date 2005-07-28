<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Exception classes for Services_Webservice_Definition package
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
 * @package    Services_Webservices
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright  2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Services_Webservice
 */

/**
 * PEAR base exception class
 */
require_once 'PEAR/Exception.php';

/**
 * Base exception for Services_Webservice_Definition package
 */
class Services_Webservice_Definition_Exception extends PEAR_Exception
{
}

/**
 * Exception calls for Invalid class parameter for constructor
 */
class Services_Webservice_Definition_NotClassException extends Services_Webservice_Definition_Exception
{
}

/**
 * Exception class for Unknown definition format
 */
class Services_Webservice_Definition_UnknownFormatException extends Services_Webservice_Definition_Exception
{
}

/**
 * Exception class for Missing docblock comment
 */
class Services_Webservice_Definition_NoDocCommentException extends Services_Webservice_Definition_Exception
{
}

/**
 * Exception class for Incomplete docblock comment
 */
class Services_Webservice_Definition_IncompleteDocCommentException extends Services_Webservice_Definition_Exception
{
}

?>