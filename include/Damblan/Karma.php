<?php
/*
   +----------------------------------------------------------------------+
   | PEAR Web site version 1.0                                            |
   +----------------------------------------------------------------------+
   | Copyright (c) 2003 The PEAR Group                                    |
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

/**
 * Class to manage the PEAR Karma System
 *
 * This system makes it not only possible to provide a fully developed
 * permission system, but it also allows us to set up a php.net-wide
 * single-sign-on system some time in the future.
 *
 * @author  Martin Jansen <mj@php.net>
 * @version $Revision$
 */
class Damblan_Karma {

    var $_dbh;

    /**
     * Constructor
     *
     * @access public
     * @param  object Instance of PEAR::DB
     */
    function Damblan_Karma(&$dbh) {
        glboal $auth_user;

        $this->_dbh = $dbh;

        if ($this->has($auth_user->handle, "global.karma.manager") == false) {
            return PEAR::raiseError("Insufficient privileges");
        }
    }

    /**
     * Determine if the given user has karma for the given $level
     *
     * The given level is either a concrete karma level or an alias
     * that will be mapped to a karma group in this method.
     *
     * @access public
     * @param  string Username
     * @param  string Level
     * @return boolean
     */
    function has($user, $level) {
        $levels = array();

        switch ($level) {
        case "pear.user" :
            $levels = array("pear.user", "pear.dev", "pear.admin", "pear.group");
            break;

        case "pear.dev" :
            $levels = array("pear.dev", "pear.admin", "pear.group");
            break;

        case "pear.admin" :
            $levels = array("pear.admin", "pear.group");
            break;

        case "pear.group" :
            $levels = array("pear.group");
            break;

        case "global.karma.manager" :
            $levels = array("pear.group");
            break;

        default :
            $levels = array($level);
            break;

        }

        $query = "SELECT * FROM karma WHERE user = ? AND level IN (!)";

        $sth = $this->_dbh->query($query, array($user, "'" . implode("','", $levels) . "'"));
        return ($sth->numRows() > 0);
    }

    /**
     * Grant karma for $level to the given $user
     *
     * @access public
     * @param  string Handle of the user
     * @param  string Level
     * @return boolean
     */
    function grant($user, $level) {
        global $auth_user;

        $id = $this->_dbh->nextId("karma");

        $query = "INSERT INTO karma VALUES (?, ?, ?, ?, NOW())";
        $sth = $this->_dbh->query($query, array($id, $user, $level, $auth_user->handle));

        return true;
    }

    /**
     * Remove karma $level for the given $user
     *
     * @access public
     * @param  string Handle of the user
     * @param  string Level
     * @return boolean
     */
    function remove($user, $level) {
        $query = "DELETE FROM karma WHERE user = ? AND level = ?";
        return $this->_dbh->query($query, array($user, $level));
    }

    /**
     * Get karma for given user
     *
     * @access public
     * @param  string Name of the user
     * @return array
     */
    function get($user) {
        $query = "SELECT * FROM karma WHERE user = ?";
        return $this->_dbh->getAll($query, array($user), DB_FETCHMODE_ASSOC);
    }
}
?>
