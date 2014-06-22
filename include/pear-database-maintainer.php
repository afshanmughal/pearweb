<?php
/**
 * Class to handle maintainers
 *
 * @class   maintainer
 * @package pearweb
 * @author  Stig S. Bakken <ssb@fast.no>
 * @author  Tomas V.V. Cox <cox@php.net>
 * @author  Martin Jansen <mj@php.net>
 */
class maintainer
{
    /**
     * Add new maintainer
     *
     * @static
     * @param  mixed  Name of the package or it's ID
     * @param  string Handle of the user
     * @param  string Role of the user
     * @param  integer Is the developer actively working on the project?
     * @return mixed True or PEAR error object
     */
    static function add($package, $user, $role, $active = 1)
    {
        global $dbh;

        include_once 'pear-database-user.php';
        if (!user::exists($user)) {
            throw new InvalidArgumentException("User $user does not exist");
        }

        include_once 'pear-database-package.php';
        if (is_string($package)) {
            $package = package::info($package, 'id');
        }

        $sql = 'INSERT INTO maintains (handle, package, role, active) VALUES (?, ?, ?, ?)';
        $err = $dbh->query($sql, array($user, $package, $role, (int)$active));

        if (DB::isError($err)) {
            return $err;
        }
        $packagename = package::info($package, 'name');
        include_once 'pear-rest.php';
        $pear_rest = new pearweb_Channel_REST_Generator(PEAR_REST_PATH, $dbh);
        $pear_rest->savePackageMaintainerREST($packagename);
        return true;
    }

    /**
     * Get maintainer(s) for package
     *
     * @param mixed   Name of the package or it's ID
     * @param boolean Only return lead maintainers?
     * @param boolean Only get all maintainers but possibility
     *                of getting all maintainer if active is set to false.
     *
     * @return array
     */
    static function get($package, $lead = false, $active = false)
    {
        global $dbh;
        if (is_string($package)) {
            include_once 'pear-database-package.php';
            $package = package::info($package, 'id');
        }
        $query = 'SELECT handle, role, active FROM maintains WHERE package = ?';

        if ($lead) {
            $query .= " AND role = 'lead'";
        }

        if ($active) {
            $query .= ' AND active = 1';
        }

        $query .= ' ORDER BY active DESC';

        return $dbh->getAssoc($query, true, array($package), DB_FETCHMODE_ASSOC);
    }

    /**
     * Get maintainer details for package.
     *
     * @internal Used in DOAP package export, package-doap.php
     *
     * @param mixed   Name of the package or it's ID
     * @param boolean Only return lead maintainers?
     * @param boolean Only get all maintainers but possibility
     *                of getting all maintainer if active is set to false.
     *
     * @return array
     */
    static function getDetailled($package, $lead = false, $active = false)
    {
        global $dbh;
        if (is_string($package)) {
            include_once 'pear-database-package.php';
            $package = package::info($package, 'id');
        }
        $query = 'SELECT'
            . ' users.handle as handle, role, users.active as active'
            . ' , name, email, homepage, longitude, latitude'
            . ' FROM maintains, users'
            . ' WHERE users.handle=maintains.handle'
            . ' AND package = ?';

        if ($lead) {
            $query .= " AND role = 'lead'";
        }

        if ($active) {
            $query .= ' AND active = 1';
        }

        $query .= ' ORDER BY active DESC';

        return $dbh->getAssoc($query, true, array($package), DB_FETCHMODE_ASSOC);
    }

    /**
     * Check if role is valid
     *
     * @static
     * @param string Name of the role
     * @return boolean
     */
    static function isValidRole($role)
    {
        require_once 'PEAR/Common.php';
        static $roles;
        if (empty($roles)) {
            $roles = PEAR_Common::getUserRoles();
        }
        return in_array($role, $roles);
    }

    /**
     * Remove user from package
     *
     * @static
     * @param  mixed Name of the package or it's ID
     * @param  string Handle of the user
     * @return True or PEAR error object
     */
    static function remove($package, $user)
    {
        global $dbh, $auth_user;
        include_once 'pear-database-user.php';
        if (!$auth_user->isAdmin() && !$auth_user->isQA()
            && !user::maintains($auth_user->handle, $package, 'lead')
        ) {
            return PEAR::raiseError('maintainer::remove: insufficient privileges');
        }

        if (is_string($package)) {
            include_once 'pear-database-package.php';
            $package = package::info($package, 'id');
        }

        $sql = 'DELETE FROM maintains WHERE package = ? AND handle = ?';
        return $dbh->query($sql, array($package, $user));
    }

    /**
     * Update user and roles of a package
     *
     * @static
     * @param int $pkgid The package id to update
     * @param array $users Assoc array containing the list of users
     *                     in the form: '<user>' => array('role' => '<role>', 'active' => '<active>')
     * @param bool Whether to print the logging information to the screen
     * @return mixed PEAR_Error or true
     */
    static function updateAll($pkgid, $users, $print = false, $releasing = false)
    {
        require_once 'Damblan/Log.php';
        global $dbh, $auth_user;

        // Only admins and leads can do this.
        if (maintainer::mayUpdate($pkgid) == false) {
            return PEAR::raiseError('maintainer::updateAll: insufficient privileges');
        }

        $logger = new Damblan_Log;
        if ($print) {
            require_once 'Damblan/Log/Print.php';
            $observer = new Damblan_Log_Print;
            $logger->attach($observer);
        }

        include_once 'pear-database-package.php';
        $pkg_name = package::info((int)$pkgid, "name"); // Needed for logging
        if (empty($pkg_name)) {
            PEAR::raiseError('maintainer::updateAll: no such package');
        }

        $old = maintainer::get($pkgid);
        if (DB::isError($old)) {
            return $old;
        }
        $old_users = array_keys($old);
        $new_users = array_keys($users);

        $admin = $auth_user->isAdmin();
        $qa    = $auth_user->isQA();

        if (!$admin && !$qa && !in_array($auth_user->handle, $new_users)) {
            return PEAR::raiseError("You can not delete your own maintainer role or you will not ".
                                    "be able to complete the update process. Set your name ".
                                    "in package.xml or let the new lead developer upload ".
                                    "the new release");
        }
        if ($releasing && user::maintains($auth_user->handle, (int)$pkgid, 'lead') &&
              $users[$auth_user->handle]['role'] != 'lead') {
            return PEAR::raiseError('You cannot demote your role from lead to ' .
                $users[$auth_user->handle]['role']);
        }
        foreach ($users as $user => $u) {
            $role   = $u['role'];
            $active = $u['active'];

            if (!maintainer::isValidRole($role)) {
                return PEAR::raiseError("invalid role '$role' for user '$user'");
            }
            // The user is not present -> add him
            if (!in_array($user, $old_users)) {
                $e = maintainer::add($pkgid, $user, $role, $active);
                if (PEAR::isError($e)) {
                    return $e;
                }
                $logger->log("[Maintainer] NEW: " . $user . " (" . $role . ") to package " . $pkg_name . " by " . $auth_user->handle);
                continue;
            }
            // Users exists but the role or the "active" flag have changed -> update it
            if ($role != $old[$user]['role'] || $active != $old[$user]['active']) {
                $res = maintainer::update($pkgid, $user, $role, $active);
                if (DB::isError($res)) {
                    return $res;
                }
                $logger->log("[Maintainer] UPDATE: " . $user . " (" . $role . ") to package " . $pkg_name . " by " . $auth_user->handle);
            }
        }
        // Drop users who are no longer maintainers
        foreach ($old_users as $old_user) {
            if (!in_array($old_user, $new_users)) {
                $res = maintainer::remove($pkgid, $old_user);
                if (DB::isError($res)) {
                    return $res;
                }
                $logger->log("[Maintainer] REMOVED: " . $old_user . " (" . $role . ") to package " . $pkg_name . " by " . $auth_user->handle);
            }
        }
        return true;
    }

    /**
     * Update maintainer entry
     *
     * @access public
     * @param  int Package ID
     * @param  string Username
     * @param  string Role
     * @param  string Is the developer actively working on the package?
     */
    static function update($package, $user, $role, $active)
    {
        global $dbh;

        $sql = 'UPDATE maintains SET role = ?, active = ? WHERE package = ? AND handle = ?';
        return $dbh->query($sql, array($role, $active, $package, $user));
    }

    /**
     * Checks if the current user is allowed to update the maintainer data
     *
     * @access public
     * @param  int  ID of the package
     * @return boolean
     */
    static function mayUpdate($package)
    {
        global $auth_user;

        include_once 'pear-database-user.php';
        if (!$auth_user->isAdmin() && !$auth_user->isQA()
            && !user::maintains($auth_user->handle, $package, 'lead')
        ) {
            return false;
        }

        return true;
    }
}
