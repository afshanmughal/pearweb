--TEST--
account-request-confirm.php [Election request deleted]
--GET--
salt=12345678901234567890123456789012
--FILE--
<?php
// setup
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['PHP_SELF'] = '/account-request-confirm.php';
$_SERVER['REQUEST_URI'] = null;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['QUERY_STRING'] = '';
require dirname(__FILE__) . '/setup.php.inc';

$time = gmdate('Y-m-d', strtotime('-1 day'));

$mock->addDeleteQuery("DELETE FROM election_account_request WHERE created_on < '" . $time . "'", array(), array());

$mock->addDataQuery("SELECT handle FROM election_account_request WHERE created_on < '" . $time . "'",
                 array(),
                 array('handle')
                 );

$mock->addDataQuery("SELECT id, created_on, salt, handle
            FROM election_account_request
            WHERE salt = '12345678901234567890123456789012'",
            array(array(
                'id'         => 1,
                'created_on' => gmdate('Y-m-d H:i', strtotime('-10 minutes')),
                'salt'       => '12345678901234567890123456789012',
                'handle'     => 'helgi',
            )),
            array('id', 'created_on', 'salt', 'handle'));

$mock->addDataQuery("SELECT * FROM users WHERE handle = 'helgi'", array(), array());

include dirname(dirname(dirname(dirname(__FILE__)))) . '/public_html/account-request-confirm.php';

$phpt->assertEquals(array (
    0 => "SELECT handle FROM election_account_request WHERE created_on < '" . $time . "'",
    1 => "DELETE FROM election_account_request WHERE created_on < '" . $time . "'",
    2 => "
            SELECT id, created_on, salt, handle
            FROM election_account_request
            WHERE salt = '12345678901234567890123456789012'
        ",
    3 => 'SELECT * FROM users WHERE handle = \'helgi\'',
), $mock->queries, 'queries');
__halt_compiler();
?>
===DONE===
--EXPECTF--
%s
 <title>PEAR :: Account confirmation</title>
%s
<!-- START MAIN CONTENT -->

  <div id="body">

<h1>Confirm Account</h1><div class="errors">ERROR:<ul><li>Error - user request was deleted, please try again</li>
</ul></div>

  </div>

<!-- END MAIN CONTENT -->
%s