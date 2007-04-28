<?php
/*
   +----------------------------------------------------------------------+
   | PEAR Web site version 1.0                                            |
   +----------------------------------------------------------------------+
   | Copyright (c) 2001-2005 The PHP Group                                |
   +----------------------------------------------------------------------+
   | This source file is subject to version 2.02 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available at through the world-wide-web at                           |
   | http://www.php.net/license/2_02.txt.                                 |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Author: Martin Jansen <mj@php.net>                                   |
   +----------------------------------------------------------------------+
   $Id$
*/
// Adding _no_cache=1 to the URL prevents caching
if (!empty($_GET['_no_cache']) && (int)$_GET['_no_cache'] == 1) {
    $no_cache = 1;
} else {
    $no_cache = 0;
}

$script_name = $_SERVER['SCRIPT_NAME'];

$cache_files = array(
                    '/index.php'=>'',
                    '/about/credits.php' => '',
                    '/about/damblan.php' => '',
                    '/about/index.php' => '',
                    '/about/privacy.php' => '',
                    '/copyright.php' => '',
                    '/dtd/index.php' => '',
                    '/download-docs.php' => '',
                    '/support/index.php' => '',
                    '/support/tutorials.php' => '',
                    '/support/slides.php' => '',
                    '/support/icons.php' => '',
                    '/account-info.php' => $_SERVER['PHP_SELF'] . (isset($_GET['handle']) ? $_GET['handle'] : ''),
                    '/accounts.php' => (isset($_GET['letter']) ? $_GET['letter'] : '') . "__" . (isset($_GET['offset']) ? $_GET['offset'] : ''),
                    '/pepr/pepr-overview.php' => array(
                                        'key'   => isset($_GET['filter']) && $_GET['filter'] ? $_GET['filter'] : 'all',
                                        'ttl'   => 5*60
                                        ),
                    '/pepr/pepr-bbcode-help.php' => '',
                    '/packages.php' => array(
                                        'key'   =>  (isset($_GET['catpid']) && $_GET['catpid'] ? $_GET['catpid'] . '__' : '') .
                                                    (isset($_GET['showempty']) && $_GET['showempty'] ? $_GET['showempty'] . '__' : '') .
                                                    (isset($_GET['moreinfo']) && $_GET['moreinfo'] ? $_GET['moreinfo'] . '__' : '') .
                                                    (isset($_GET['pageID']) && $_GET['pageID'] ? $_GET['pageID'] : '')
                                        ,
                                        'ttl'   =>60*60),
                    '/qa/packages_status.php' => array('ttl' => 60*60*24),
                    '/channels/index.php' => '',
                    );

$cache_dirs  = array(
                     "/feeds"      => "",
                     "/news"       => "",
                     "/group"      => "",
                     "/group/docs" => "",
                     "/manual"     => "",
                     "/manual/en"  => "",
                     "/manual/fr"  => "",
                     "/manual/hu"  => "",
                     "/manual/ja"  => "",
                     "/manual/nl"  => "",
                     "/manual/ru"  => "",
                     "/user"       => $_SERVER['PHP_SELF'],
                     'qa'          => ''
                     );

if (DEVBOX === true ||
    !empty($_COOKIE['PEAR_USER']) || (
    !in_array($_SERVER['PHP_SELF'], array_keys($cache_files)) &&
    !in_array(dirname($_SERVER['PHP_SELF']), array_keys($cache_dirs)))) {
    $no_cache = 1;
}

if ($no_cache == 0) {
    require_once "pear-config.php";
    require_once "Cache/Lite.php";

    // Initiate caching
    $options = array('cacheDir' => PEAR_TMPDIR . "/webcache/",
                     'lifeTime' => CACHE_LIFETIME);
    $cache = new Cache_lite($options);

    $id = $_SERVER['PHP_SELF'];

    if (isset($cache_files[$_SERVER['PHP_SELF']])) {
        if (is_array($cache_files[$_SERVER['PHP_SELF']])) {
            $cache_props = $cache_files[$_SERVER['PHP_SELF']];

            $ttl = isset($cache_props['ttl']) ? $cache_props['ttl'] : CACHE_LIFETIME;

            if (isset($cache_props['key']) && !empty($cache_props['key'])) {
                $id .= '__' . $cache_props['key'];
            }
        } elseif (!empty($cache_files[$_SERVER['PHP_SELF']])) {
            if ($cache_files[$_SERVER['PHP_SELF']] > -1) {
                $id .= $cache_files[$_SERVER['PHP_SELF']];
            } else {
                $id = false;
            }
        }
    } else {
        $basedir = dirname($_SERVER['PHP_SELF']);
        if (isset($cache_dirs[$basedir])) {
            $id .= $cache_dirs[$basedir];
        }
    }

    if ($id) {
        if (!$cache_data = $cache->get($id)) {
            ob_start();
        } else {
            exit($cache_data);
        }
    }
}
include_once 'DB.php';

if (empty($dbh)) {
    $options = array(
        'persistent' => false,
        'portability' => DB_PORTABILITY_ALL,
    );
    $dbh =& DB::connect(PEAR_DATABASE_DSN, $options);
}


