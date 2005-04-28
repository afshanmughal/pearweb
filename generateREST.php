<?php
/** 
 * Generate static REST files for pear.php.net from existing data
 * @author Greg Beaver <cellog@php.net>
 * @version $Id$
 */
/**
 * Useful files to have
 */
set_include_path(dirname(__FILE__) . '/include' . PATH_SEPARATOR . get_include_path());
ob_start();
@require_once 'pear-prepend.php';
ob_end_clean();
PEAR::setErrorHandling(PEAR_ERROR_DIE);
echo "Generating Category REST...\n";
foreach (category::listAll() as $category) {
    echo "  $category[name]...";
    $pear_rest->saveCategoryREST($category['name']);
    echo "done\n";
}
echo "Generating Maintainer REST...\n";
$maintainers = $dbh->getAll('SELECT * FROM users', array(), DB_FETCHMODE_ASSOC);
foreach ($maintainers as $maintainer) {
    echo "  $maintainer[handle]...";
    $pear_rest->saveMaintainerREST($maintainer['handle']);
    echo "done\n";
}
echo "Generating Package REST...\n";
$pear_rest->saveAllPackagesREST();
require_once 'Archive/Tar.php';
require_once 'PEAR/PackageFile.php';
$config = &PEAR_Config::singleton();
$pkg = new PEAR_PackageFile($config);
foreach (package::listAll(false, false, false) as $package => $info) {
    echo "  $package\n";
    $pear_rest->savePackageREST($package);
    echo "     Maintainers...";
    $pear_rest->savePackageMaintainerREST($package);
    echo "...done\n";
    $releases = package::info($package, 'releases');
    if ($releases) {
        echo "     Processing All Releases...";
        $pear_rest->saveAllReleasesREST($package);
        echo "done\n";
        foreach ($releases as $version => $blah) {
            echo "     Version $version...";
            $fileinfo = $dbh->getOne('SELECT fullpath FROM files WHERE release = ?',
                array($blah['id']));
            $tar = &new Archive_Tar($fileinfo);
            if ($pxml = $tar->extractInString('package2.xml')) {
            } elseif ($pxml = $tar->extractInString('package.xml'));
            $pf = $pkg->fromAnyFile($fileinfo, PEAR_VALIDATE_NORMAL);
            $pear_rest->saveReleaseREST($fileinfo, $pxml, $pf, $blah['doneby'],
                $blah['id']);
            echo "done\n";
        }
        echo "\n";
    } else {
        echo "  done\n";
    }
}
?>