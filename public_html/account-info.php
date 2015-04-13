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
   | Authors:                                                             |
   +----------------------------------------------------------------------+
   $Id$
*/


// Details about PEAR accounts
require_once 'Damblan/Karma.php';
require_once 'Damblan/URL.php';
require_once 'HTTP.php';
require 'bugs/pear-bugs.php';
include_once 'pear-database-user.php';

$bugs = new PEAR_Bugs();
$site = new Damblan_URL();
$karma = new Damblan_Karma($dbh);

$params = array('handle' => '', 'action' => '');
$site->getElements($params);

$handle = htmlspecialchars(strtolower($params['handle']));

// Redirect to the accounts list if no handle was specified
if (empty($handle)) {
    localRedirect('/accounts.php');
}

$dbh->setFetchmode(DB_FETCHMODE_ASSOC);


$permissions = $karma->get($handle);
$row = user::info($handle);

if ($row === null) {
    error_handler($handle . ' is not a valid account name.', 'Invalid Account');
}

switch ($params['action']) {
    case 'wishlist' :
        if (!empty($row['wishlist'])) {
            HTTP::redirect($row['wishlist']);
        } else {
            PEAR::raiseError(htmlspecialchars($row['name']) . ' has not registered a wishlist');
        }
        break;

    case 'bugs' :
        HTTP::redirect('/bugs/search.php?handle=' . $handle . '&cmd=display');
        break;

    case 'rss' :
        HTTP::redirect('/feeds/user_' . $handle . '.rss');
        break;

}




$extraHeaders = '<link rel="alternate" href="/map/locationREST.php?handle=' . $handle . '" type="application/rdf+xml" title="FOAF" />';
$extraHeaders .= '<link rel="alternate" href="/feeds/user_' . $handle . '.rss" type="application/rdf+xml" title="Packages involving this user" />';

response_header(htmlspecialchars($row['name']), false, $extraHeaders);

echo '<h1>User Information: ' . htmlspecialchars($row['name']) . "</h1>\n";

if (isset($auth_user) && is_object($auth_user)
    && ($auth_user->handle == $handle ||
        auth_check('pear.admin'))) {

    $nav_items = array(
        'Edit user' => array('url' => '/account-edit.php?handle=' . $handle,
                             'title' => 'Edit user standing data.'),
        'Change password' => array('url' => '/account-edit.php?handle=' . $handle . '#password',
                                   'title' => 'Change your password.')
    );

    if (auth_check('pear.admin')) {
        $nav_items['Edit Karma'] = array('url' => '/admin/karma.php?handle=' . $handle,
                                         'title' => 'Edit karma for this user');
    }

    echo '<div id="nav">';

    foreach ($nav_items as $title => $item) {
        echo '<a href="' . $item['url'] . '"'
            . ' title="' . $item['title'] . '"> '
            . $title
            . '</a>';
    }

    echo '</div>';
}
?>

<table border="0" cellspacing="0" cellpadding="2" style="width: 100%" class="vcard">
 <tr>
  <th class="headrow" colspan="2">&raquo;
  <span class="fn"><?php echo htmlspecialchars($row['name']); ?></span></th>
 </tr>

<?php

if ($row['userinfo']) {
    echo ' <tr>' . "\n";
    echo '  <td class="textcell" colspan="2">';
    echo nl2br(htmlspecialchars($row['userinfo'])) . "</td>\n";
    echo ' </tr>' . "\n";
}

?>

 <tr>
  <td colspan="2">
   <ul>
    <li>Username: <span class="nickname"><?php echo $row['handle']; ?></span></li>
<?php

if ($row['active']) {
    echo '    <li>Active: <strong>Yes</strong></li>' . "\n";
} else {
    echo '    <li>Active: <strong>NO</strong></li>' . "\n";
}

if ($row['lastlogin'] && !is_null($row['lastlogin'])) {
    echo '    <li>Last login: <i>' . date('Y-m-d', $row['lastlogin']) . '</i></li>' . "\n";
}

if (isset($auth_user)) {
    echo '    <li>Email: &nbsp;<span class="email">';
    echo make_mailto_link($row['email']);
    echo "</span></li>\n";
} else if ($row['showemail']) {
    $row['email'] = str_replace(array('@', '.'),
                                array(' at ', ' dot '),
                                $row['email']);
    echo '    <li>Email: &nbsp;';
    echo make_link('/account-mail.php?handle=' . $handle,
               htmlspecialchars($row['email']), '', 'class="email"');
    echo "</li>\n";
} else {
    echo '    <li>Email: &nbsp;';
    echo make_link('/account-mail.php?handle=' . $handle, 'via web form');
    echo "</li>\n";
}

if ($row['homepage']) {
    echo '    <li>Homepage: &nbsp;<span class="url">';
    echo make_link(htmlspecialchars($row['homepage']), '', '', 'rel="nofollow me"');
    echo "</span></li>\n";
}

if ($row['wishlist']) {
    echo '    <li>Wishlist: &nbsp;';
    echo make_link('http://' . PEAR_CHANNELNAME . '/user/' . $handle . '/wishlist');
    echo "</li>\n";
}

if ($row['pgpkeyid']) {
    echo '    <li>PGP Key: &nbsp;';
    echo make_link('http://pgp.mit.edu:11371/pks/lookup?search=0x'
               . htmlspecialchars($row['pgpkeyid']) . '&amp;op=get',
               htmlspecialchars($row['pgpkeyid']));
    echo "</li>\n";
}

echo '    <li>RSS Feed: &nbsp;';
echo make_link('http://' . PEAR_CHANNELNAME . '/feeds/user_' . $handle . '.rss');
echo '</li>' . "\n";

if (!empty($row['latitude']) && !empty($row['longitude'])) {
    echo '    <li class="geo">Map: &nbsp;';
    $geo = '<span class="latitude">'
        . $row['latitude']
        . '</span>, <span class="longitude">'
        . $row['longitude']
        . '</span>';
    echo make_link('http://' . PEAR_CHANNELNAME . '/map/?handle=' . $handle, $geo);
    echo '</li>' . "\n";
}


if ($karma->has($handle, 'pear.dev')) {
    echo '    <li>Bug Statistics: <br />' . "\n";
    echo '     <ul>' . "\n";


    $info = $bugs->developerBugStats($handle);
    echo '      <li>Rank: <strong><a href="/bugs/stats_dev.php">#' . $info['rank'] . ' of ' . count($info['rankings']) . '</a></strong> developers who have fixed bugs <strong>(' .
        $info['alltime'] . ' fixed bugs)</strong></li>' . "\n";
    echo '      <li>Average age of open bugs: <strong>' . $info['openage'] . ' days</strong></li>' . "\n";
    $url = '/bugs/search.php?handle=' . $handle . '&amp;cmd=display&amp;bug_type=Bug&amp;status=OpenFeedback&amp;showmenu=1';
    echo '      <li>Number of open bugs: <strong><a href="' . $url . '">' . $info['opencount'] . '</a></strong></li>' . "\n";
    echo '      <li>Assigned bugs relative to all maintained packages bugs: <strong>' .
        round($info['assigned'] * 100) . '%</strong></li>' . "\n";
    echo '      <li>Number of submitted patches: <strong>' .
        $info['patches'] . '</strong></li>' . "\n";
    echo '      <li>Number of bugs opened using account: <strong>' .
        $info['opened'] . '</strong></li>' . "\n";
    echo '      <li>Number of bug comments using account: <strong>' .
        $info['commented'] . '</strong></li>' . "\n";
    echo '     </ul>' . "\n";
    echo '    </li>' . "\n";
}
?>

   </ul>
  </td>
 </tr>

<?php
$packages = user::getPackages($handle, true);

include_once 'pear-database-note.php';
$notes = note::getAll($handle);

if (count($packages) > 0 || count($notes) > 0) {
?>

 <tr>
  <th class="headrow" style="width: 50%">&raquo; Maintains These Packages</th>
  <th class="headrow" style="width: 50%">Karma and History</th>
 </tr>
 <tr>
  <td valign="top">

<?php
if (count($packages) == 0) {
    echo '<p>This user does not maintain any packages.</p>';
}
?>
   <ul>
<?php
foreach ($packages as $row) {
    echo '<li>';
    echo make_link('/package/' . htmlspecialchars($row['name']),
               htmlspecialchars($row['name']));
    echo ' &nbsp;(' . htmlspecialchars($row['role']) . ($row['active'] == 0 ? ", inactive" : "") . ')';
    echo ' &nbsp;<small><a href="/bugs/search.php?package_name%5B%5D=';
    echo htmlspecialchars($row['name']) . '&amp;cmd=display">Bugs</a></small>';
    echo "</li>\n";
}

?>

   </ul>
  </td>
  <td valign="top">
<?php if (!empty($permissions)) { ?>
    <h4>Current Karma</h4>
    <ul>
        <?php foreach ($permissions as $permission) { ?>
            <li><?php print $permission['level']; ?> by <?php print $permission['granted_by']; ?><br /><span style="opacity: 0.3"><?php print substr($permission['granted_at'], 0, 10); ?></span></li>
        <?php } ?>
    </ul>
<?php } ?>
<h4>History and notes</h4>
<?php
if (empty($notes)) {
    echo '<p>There are no notes for this user.</p>';
}
?>
   <ul>
<?php
foreach ($notes as $nid => $data) {
    echo ' <li>' . "\n";
    echo '' . $data['nby'] . ' ';
    echo substr($data['ntime'], 0, 10) . ":<br />\n";
    echo htmlspecialchars($data['note']);
    echo "\n </li>\n";
}

?>

   </ul>
<?php
$proposals = user::getProposals($handle);
if (count($proposals) > 0) {
    echo "<h4>Package Proposals</h4>";
}
?>
   <ul>
<?php
foreach ($proposals as $nid => $data) {
    $cStatus = $data['status'];
    switch($data['status']) {
        case 'draft':
            $when = $data['draft_date'];
            break;
        case 'proposal':
            $cStatus = 'proposed';
            $when = $data['proposal_date'];
            break;
        case 'finished':
            $when = $data['vote_date'];
            break;
        default:
            $when = $data['proposal_date'];
    }
    echo ' <li>' . "\n";
    echo '<a href="/pepr/pepr-proposal-show.php?id='. $data['id'].'">' . $data['pkg_name'] . '</a>';
    echo ' (' . $cStatus . ', '. format_date(strtotime($when), 'Y-m-d') . ')';
    echo "\n </li>\n";
}

?>

   </ul>
  </td>
 </tr>
<?php
}
?>
</table>

<?php
response_footer();
