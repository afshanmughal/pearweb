<?php
/**
* Filename.......: package-search.php
* Project........: PEAR Website
* Last Modified..: $Date$
* CVS Revision...: $Revision$
*
* Searches for packages matching various user definable
* criteria including:
*  o Name
*  o Maintainer
*  o Category
*  o Release date (on/before/after)
*/
	$template_dir = dirname(dirname(__FILE__)) . '/templates/';
	require_once "HTML/Form.php";

/**
* Setup code for the form
*/
	$form = new HTML_Form($_SERVER['PHP_SELF']);
	
/**
* Months for released date dropdowns
*/
	$months[1]  = 'January';
	$months[2]  = 'February';
	$months[3]  = 'March';
	$months[4]  = 'April';
	$months[5]  = 'May';
	$months[6]  = 'June';
	$months[7]  = 'July';
	$months[8]  = 'August';
	$months[9]  = 'September';
	$months[10] = 'October';
	$months[11] = 'November';
	$months[12] = 'December';

/**
* Used for the Match: radio buttons
*/
	$bool_and_checked = !isset($_GET['bool']) || $_GET['bool'] == "AND" ? 'checked="checked"' : '';
	$bool_or_checked  = @$_GET['bool'] == "OR" ? 'checked="checked"' : '';

/**
* Code to fetch the current category list
*/
	$category_rows = $dbh->getAll('SELECT id, name FROM categories ORDER BY name', DB_FETCHMODE_ASSOC);

/**
* Fetch list of users/maintainers
*/
	$users = $dbh->getAll('SELECT handle, name FROM users ORDER BY name', DB_FETCHMODE_ASSOC);
	for ($i=0; $i<count($users); $i++) {
		if (empty($users[$i]['name'])) {
			$users[$i]['name'] = $users[$i]['handle'];
		}
	}

/**
* Is form submitted? Do search and show
* results.
*/
	if (!empty($_GET)) {
	    $dbh->setFetchmode(DB_FETCHMODE_ASSOC);
	    $where = array();
	    $bool  = @$_GET['bool'] == 'AND' ? ' AND ' : ' OR ';
	
	    // Build package name part of query
	    if (!empty($_GET['pkg_name'])) {
	        $searchwords = preg_split('/\s+/', $_GET['pkg_name']);
	        for ($i=0; $i<count($searchwords); $i++) {
	            $searchwords[$i] = sprintf("name LIKE '%%%s%%'", addslashes($searchwords[$i]));
	        }
	        $where[] = '(' . implode($bool, $searchwords) . ')';
	    }
	
	    // Build maintainer part of query
	    if (!empty($_GET['pkg_maintainer'])) {
	        $where[] = sprintf("handle LIKE '%%%s%%'", addslashes($_GET['pkg_maintainer']));
	    }
	
	    // Build category part of query
	    if (!empty($_GET['pkg_category'])) {
	        $where[] = sprintf("category = '%s'", addslashes($_GET['pkg_category']));
	    }
		
		/**
        * Any release date checking?
        */
		$release_join = '';
		// RELEASED_ON
		if (!empty($_GET['released_on_year']) AND !empty($_GET['released_on_month']) AND !empty($_GET['released_on_day'])) {
			$release_join = ', releases r';
			$where[] = "p.id = r.package";
			$where[] = sprintf("DATE_FORMAT(r.releasedate, '%%Y-%%m-%%d') = '%04d-%02d-%02d'",
			                   (int)$_GET['released_on_year'],
							   (int)$_GET['released_on_month'],
							   (int)$_GET['released_on_day']);
		} else {
			// RELEASED_BEFORE
			if (!empty($_GET['released_before_year']) AND !empty($_GET['released_before_month']) AND !empty($_GET['released_before_day'])) {
				$release_join = ', releases r';
				$where[] = "p.id = r.package";
				$where[] = sprintf("DATE_FORMAT(r.releasedate, '%%Y-%%m-%%d') <= '%04d-%02d-%02d'",
				                   (int)$_GET['released_before_year'],
								   (int)$_GET['released_before_month'],
								   (int)$_GET['released_before_day']);
			}
			
			// RELEASED_SINCE
			if (!empty($_GET['released_since_year']) AND !empty($_GET['released_since_month']) AND !empty($_GET['released_since_day'])) {
				$release_join = ', releases r';
				$where[] = "p.id = r.package";
				$where[] = sprintf("DATE_FORMAT(r.releasedate, '%%Y-%%m-%%d') >= '%04d-%02d-%02d'",
				                   (int)$_GET['released_since_year'],
								   (int)$_GET['released_since_month'],
								   (int)$_GET['released_since_day']);
			}
		}
	    
	    // Compose query and execute
	    $where  = !empty($where) ? 'AND '.implode(' AND ', $where) : '';
	    $sql    = "SELECT p.id,
		                  p.name,
						  p.category,
						  p.summary
		             FROM packages p,
					      maintains m
						  $release_join
		            WHERE p.id = m.package " . $where . "
		         GROUP BY p.id
				 ORDER BY p.name";
	    $result = $dbh->query($sql);

	    // Run through any results
	    if (($numrows = $result->numRows()) > 0) { 
	
	        // Paging
	        include_once('Pager/Pager.php');
	        $params['itemData'] = range(0, $numrows - 1);
	        $pager = &new Pager($params);
	        list($from, $to) = $pager->getOffsetByPageId();
	        $links = $pager->getLinks();
	
	        // Row number
	        $rownum = $from - 1;

	        /**
            * Title html for results borderbox obj
			* Eww.
            */
	        $title_html  = sprintf('<table border="0" width="100%%" cellspacing="0" cellpadding="0">
			                        	<tr>
											<td align="left" width="1"><nobr>%s</nobr></td>
											<td><nobr>Search results (%s - %s of %s)</nobr></td>
											<td align="right" width="1"><nobr>%s</nobr></td>
										</tr>
									</table>',
			                       $links['back'],
								   $from,
								   $to,
								   $numrows,
								   $links['next']);

			while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC, $rownum++) AND $rownum <= $to) {
				$search_results[] = $row;
	        }
	    }    
	}

/**
* Template stuff
*/
	@include($template_dir . 'package-search.html');
?>