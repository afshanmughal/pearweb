--TEST--
release-upload.php [upload, user not lead]
--COOKIE--
PEAR_USER=cellog;PEAR_PW=hi
--POST--
verify=1&distfile=Archive_Tar-1.3.2.tgz
--FILE--
<?php
// setup
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['PHP_SELF'] = 'hithere';
$_SERVER['REQUEST_URI'] = '/release-upload.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['QUERY_STRING'] = '';
$_COOKIE['PEAR_USER'] = 'cellog';
$_COOKIE['PEAR_PW'] = 'hi';
$moresetup = dirname(__FILE__) . '/test_upload_step2error5.php.inc';
require dirname(__FILE__) . '/setup.php.inc';
@unlink(PEAR_UPLOAD_TMPDIR . '/' . basename($_POST['distfile']));
copy(dirname(__FILE__) . '/test_upload_step1/Archive_Tar-1.3.2.tgz',
    PEAR_UPLOAD_TMPDIR . '/Archive_Tar-1.3.2.tgz');
include dirname(dirname(dirname(dirname(__FILE__)))) . '/public_html/release-upload.php';
$phpt->assertEquals(array (
  0 => 'SELECT * FROM users WHERE handle = \'cellog\' AND registered = \'1\'',
  1 => 'SELECT * FROM karma WHERE user = \'cellog\' AND level IN (\'pear.user\',\'pear.pepr\',\'pear.dev\',\'pear.admin\',\'pear.group\',\'pear.voter\',\'pear.bug\')',
  2 => 'SELECT * FROM karma WHERE user = \'cellog\' AND level IN (\'pear.user\',\'pear.pepr\',\'pear.dev\',\'pear.admin\',\'pear.group\',\'pear.voter\',\'pear.bug\')',
  3 => 'SELECT * FROM karma WHERE user = \'cellog\' AND level IN (\'pear.dev\',\'pear.admin\',\'pear.group\')',
  4 => 'SELECT id FROM packages p WHERE p.package_type = \'pear\' AND p.approved = 1 AND  p.name = \'Archive_Tar\'',
  5 => 'SELECT * FROM karma WHERE user = \'cellog\' AND level IN (\'pear.admin\',\'pear.group\')',
  6 => 'SELECT * FROM karma WHERE user = \'cellog\' AND level IN (\'pear.qa\',\'pear.admin\',\'pear.group\')',
  7 => 'SELECT id FROM packages p WHERE p.package_type = \'pear\' AND p.approved = 1 AND  p.id = 1',
  8 => 'SELECT role FROM maintains WHERE handle = \'cellog\' AND package = 1 AND role = \'lead\'',
  9 => 'SELECT * FROM karma WHERE user = \'cellog\' AND level IN (\'pear.dev\',\'pear.admin\',\'pear.group\')',
  10 => 'SELECT * FROM karma WHERE user = \'cellog\' AND level IN (\'pear.admin\',\'pear.group\')',
  11 => 'SELECT m.handle
              FROM packages p, maintains m
              WHERE
                 m.handle = \'cellog\' AND
                 p.id = m.package AND
                 m.role = \'lead\'',
), $mock->queries, 'queries');
$phpt->assertEquals(array ()
, $mock->modqueries, 'modification queries');
$phpt->assertFileNotExists(dirname(__FILE__) . '/tarballs/Archive_Tar-1.3.2.tgz', 'tgz');
$phpt->assertFileNotExists(dirname(__FILE__) . '/tarballs/Archive_Tar-1.3.2.tar', 'tar');

$phpt->assertFileNotExists($restdir . '/p/packages.xml', 'REST p/packages.xml');
$phpt->assertFileNotExists($restdir . '/p/archive_tar/info.xml', 'REST p/archive_tar/info.xml');
$phpt->assertFileNotExists($restdir . '/p/archive_tar/maintainers.xml', 'REST p/archive_tar/maintainers.xml');
$phpt->assertFileNotExists($restdir . '/p/archive_tar/maintainers2.xml', 'REST p/archive_tar/maintainers2.xml');
$phpt->assertFileNotExists($restdir . '/r/archive_tar/1.3.2.xml', 'REST r/archive_tar/1.3.2.xml');
$phpt->assertFileNotExists($restdir . '/r/archive_tar/allreleases.xml', 'REST r/archive_tar/allreleases.xml');
$phpt->assertFileNotExists($restdir . '/r/archive_tar/allreleases2.xml', 'REST r/archive_tar/allreleases2.xml');
$phpt->assertFileNotExists($restdir . '/r/archive_tar/deps.1.3.2.txt', 'REST r/archive_tar/deps.13.2.txt');
$phpt->assertFileNotExists($restdir . '/r/archive_tar/latest.txt', 'REST r/archive_tar/latest.txt');
$phpt->assertFileNotExists($restdir . '/r/archive_tar/package.1.3.2.xml', 'REST r/archive_tar/package.1.3.2.xml');
$phpt->assertFileNotExists($restdir . '/r/archive_tar/stable.txt', 'REST r/archive_tar/stable.txt');
$phpt->assertFileNotExists($restdir . '/r/archive_tar/v2.1.3.2.xml', 'REST r/archive_tar/v2.1.3.2.xml');
$phpt->assertFileNotExists($restdir . '/c/Hungry+Hungry+Hippo/packagesinfo.xml', 'REST c/Hungry+Hungry+Hippo/packagesinfo.xml');
__halt_compiler();
?>
===DONE===
--CLEAN--
<?php
require dirname(__FILE__) . '/setup.php.inc';
unlink(PEAR_UPLOAD_TMPDIR . '/Archive_Tar-1.3.2.tgz');
include dirname(__FILE__) . '/teardown.php.inc';
unlinkdir(dirname(__FILE__) . '/rest');
rmdir(dirname(__FILE__) . '/rest');
?>
--EXPECTF--
%s
 <title>PEAR :: Upload New Release</title>
%s
<!-- START MAIN CONTENT -->

  <td class="content">

    <h1>Upload New Release</h1>
<div class="errors">ERROR:<ul><li>You don't have permissions to upload this release.</li>
</ul></div>
<p>
Upload a new package distribution file built using &quot;<code>pear
package</code>&quot; here.  The information from your package.xml file will
be displayed on the next screen for verification. The maximum file size
is 16 MB.
</p>

<p>
Uploading new releases is restricted to each package's lead developer(s).
</p><form action="release-upload.php" method="post" enctype="multipart/form-data" >
<table class="form-holder" cellspacing="1">
 <caption class="form-caption">
  Upload
 </caption>
 <tr>
  <th class="form-label_left"><label for="f" accesskey="i">D<span class="accesskey">i</span>stribution File</label></th>
  <td class="form-input">
   <input type="hidden" name="MAX_FILE_SIZE" value="16777216" />
   <input type="file" name="distfile" size="40" id="f"/>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">&nbsp;</th>
  <td class="form-input">
   <input type="submit" name="upload" value="Upload!" />
  </td>
 </tr>
</table>
<input type="hidden" name="_fields" value="distfile:upload" />
</form>


  </td>

<!-- END MAIN CONTENT -->
%s
</html>