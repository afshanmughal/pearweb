<?php
/*
   +----------------------------------------------------------------------+
   | PEAR Web site version 1.0                                            |
   +----------------------------------------------------------------------+
   | Copyright (c) 2001-2003 The PHP Group                                |
   +----------------------------------------------------------------------+
   | This source file is subject to version 2.02 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available at through the world-wide-web at                           |
   | http://www.php.net/license/2_02.txt.                                 |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Authors:                                                             |
   +----------------------------------------------------------------------+
   $Id$
*/

$SIDEBAR_DATA='
<p>The syntax highlighted source is automatically generated by PHP from
the plaintext script.</p>
<p>If you want to see the source of this page, have a look
<a href="/source.php?url=/source.php">here</a>.</p>
';

response_header('Show Source');

echo "<h2>Show Source</h2>\n";

if (empty($url)) {
    echo "<p>No page URL specified.</p>";
    response_footer();
    exit;
}
$url = htmlentities($url);
?>
<p>Source of: <?php echo $url; ?></p>

<?php

echo hdelim(); 

$legal_dirs = array(
    "/manual" => 1,
    "/include" => 1,
    "/stats" => 1);

$dir = dirname($url);
if (isset($legal_dirs[$dir])) {
    $page_name = $DOCUMENT_ROOT . $url;
} else {
    $page_name = basename($url);
}

echo("<!-- $page_name -->\n");

if (file_exists($page_name) && !is_dir($page_name)) {
    show_source($page_name);
} else if (@is_dir($page_name)) {
    echo "<p>No file specified.  Can't show source for a directory.</p>\n";
} else {
    echo "<p>Invalid URL specified.</p>\n";
}

response_footer();
?>  
