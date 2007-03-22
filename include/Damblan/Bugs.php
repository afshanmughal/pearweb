<?php
/*
   +----------------------------------------------------------------------+
   | PEAR Web site version 1.0                                            |
   +----------------------------------------------------------------------+
   | Copyright (c) 2003-2007 The PEAR Group                               |
   +----------------------------------------------------------------------+
   | This source file is subject to version 2.02 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available at through the world-wide-web at                           |
   | http://www.php.net/license/2_02.txt.                                 |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Author: David Coallier <davidc@php.net>                              |
   +----------------------------------------------------------------------+
   $Id$
*/

/**
 * Bugs utilisies
 *
 * @author David Coallier <davidc@php.net>
 */
class Damblan_Bugs
{
    // {{{ public function getMaintainers
    /**
     * Get maintainers
     *
     * Get maintainers to inform of a trackback (the 
     * lead maintainers of a package).
     *
     * @since
     * @access public
     * @param  boolean $activeOnly  To get only active leads
     *                 is set to false by default so there's
     *                 no bc problems.
     *
     * @return array(string) The list of maintainer emails.
     */
    function getMaintainers ($id, $leadOnly = true, $activeOnly = true)
    {
        $maintainers = maintainer::get($id, $leadOnly, $activeOnly);
        $res = array();

        foreach ($maintainers as $maintainer => $data) {
            $tmpUser = user::info($maintainer, 'email');
            if (empty($tmpUser['email'])) {
                continue;
            }
            $res[] = $tmpUser['email'];
        }
        return $res;
    }
    // }}}
}
?>