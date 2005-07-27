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

// {{{ class Services_Webservice_Definition_HTML

/**
 * Format web service information in HTML
 *
 * @author  Manfred Weber <weber@mayflower.de>
 * @author  Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @package Services_Webservices
 * @version
 */
class Services_Webservice_Definition_HTML
{
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
        $this->_parser =& $definition;
    }

    // }}}
    // {{{ toString()
    /**
     * Returns info-site in HTML format
     *
     * @access public
     * @return string
     */
    public function toString()
    {
        $wsdlStruct = $this->_parser->getStruct();
        $namespace  = $this->_parser->namespace;
        $classname  = $this->_parser->getClassName();

        if (trim($this->_parser->description) == '') {
            $description = 'My example service description';
        } else {
            $description = $this->_parser->description;
        }

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
li.deprecated {
    color: #A0A0A0;
}
span.deprecated {
    font-weight: bold;
}
';

        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>' . $classname . ' Web Service</title>
<meta name="generator" content="PEAR::Services_Webservice @version@" />
<style type="text/css">
' . $css . '
</style>
</head>
<body>
<div id="header">
<h1>' . $classname . '</h1>
<p>' . htmlspecialchars($description) . '</p>
</div>
<p>The following operations are supported. For a formal definition, please review the <a href="' . htmlentities($_SERVER['PHP_SELF']) . '?wsdl">Service Description</a>.</p>
<ul>';

        foreach ($wsdlStruct[$classname]['method'] as $methodName => $method) {
            $paramValue = array();
            foreach ($method['var'] as $methodVars) {
                if (isset($methodVars['param'])) {
                    $paramValue[] = $methodVars['type']
                                     . str_repeat('[]', $methodVars['length']);
                }
            }
            $returnValue = array();
            foreach ($method['var'] as $methodVars) {
                if (isset($methodVars['return'])) {
                    $returnValue[] = $methodVars['type']
                                     . str_repeat('[]', $methodVars['length']);
                }
            }
            $html .= sprintf('<li%s><samp><var class="returnedValue">%s</var> <b class="functionName">%s</b>( <var class="parameter">%s</var> )</samp>%s%s</li>'
                    , ((empty($method['deprecated'])) ? '' : ' class="deprecated"')
                    , implode(',', $returnValue)
                    , $methodName
                    , implode('</var> , <var class="parameter">', $paramValue)
                    , ((empty($method['deprecated'])) ? '' : ('<br /><span class="description deprecated">This method is deprecated!</span>'))
                    , ((empty($method['description'])) ? '' : ('<br /><span class="description">' . htmlspecialchars($method['description']) . '</span>')));
        }
        $html .= '</ul>
<p><a href="' . htmlentities($_SERVER['PHP_SELF']) . '?DISCO">DISCO</a> makes it possible for clients to reflect against endpoints to discover services and their associated <acronym title="Web Service Description Language">WSDL</acronym> documents.</p>';

        if (strncmp($namespace, 'http://example.org', 18) === 0) {
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
}

?>