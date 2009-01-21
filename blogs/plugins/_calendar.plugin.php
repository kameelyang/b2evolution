<?php
/**
 * This file implements the Calendar plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 * @author hansreinders: Hans REINDERS
 * @author cafelog (team)
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Calendar Plugin
 *
 * This plugin displays
 */
class calendar_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */

	var $name = 'Calendar Widget';
	var $code = 'evo_Calr';
	var $priority = 20;
	var $version = '3.0';
	var $author = 'The b2evo Group';
	var $group = 'widget';


  /**
	 * @var ItemQuery
	 */
	var $ItemQuery;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('This skin tag displays a navigable calendar.');
		$this->long_desc = T_('Days containing posts are highlighted.');

		$this->dbtable = 'T_items__item';
		$this->dbprefix = 'post_';
		$this->dbIDname = 'post_ID';
	}


  /**
   * Get definitions for widget specific editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_widget_param_definitions( $params )
	{
		$r = array(
			'displaycaption' => array(
				'label' => T_('Display caption'),
				'note' => T_('Display caption on top of calendar'),
				'type' => 'checkbox',
				'defaultvalue' => true,
			),
			'linktomontharchive' => array(
				'label' => T_('Link caption to archives'),
				'note' => T_('The month in the caption can be clicked to see all posts for this month'),
				'type' => 'checkbox',
				'defaultvalue' => true,
			),
			'headerdisplay' => array(
				'label' => 'Column headers',
				'note' => T_('How do you want to display the days of the week in the column headers?'),
				'type' => 'select',
				'options' => array( 'e' => 'F', 'D' => 'Fri', 'l' => 'Friday', '' => T_('No header') ),
				'defaultvalue' => 'D',
			),
			'navigation' => array(
				'label' => 'Navigation arrows',
				'note' => T_('Where do you want to display the navigation arrows?'),
				'type' => 'select',
				'options' => array( 'caption' => T_('Top'), 'tfoot' => T_('Bottom'), '' => T_('No navigation') ),
				'defaultvalue' => 'tfoot',
			),
			'navigation' => array(
				'label' => 'Navigation arrows',
				'note' => T_('Where do you want to display the navigation arrows?'),
				'type' => 'select',
				'options' => array( 'caption' => T_('Top'), 'tfoot' => T_('Bottom'), '' => T_('No navigation') ),
				'defaultvalue' => 'tfoot',
			),
			'browseyears' => array(
				'label' => T_('Navigate years'),
				'note' => T_('Display double arrows for yearly navigation?'),
				'type' => 'checkbox',
				'defaultvalue' => true,
			),
		);
		return $r;
	}


	/**
	 * Event handler: SkinTag (widget)
	 *
	 * @param array Associative array of parameters. Valid keys are:
	 *      - 'block_start' : (Default: '<div class="bSideItem">')
	 *      - 'block_end' : (Default: '</div>')
	 *      - 'title' : (Default: T_('Calendar'))
	 *      - 'displaycaption'
	 *      - 'monthformat'
	 *      - 'linktomontharchive'
	 *      - 'tablestart'
	 *      - 'tableend'
	 *      - 'monthstart'
	 *      - 'monthend'
	 *      - 'rowstart'
	 *      - 'rowend'
	 *      - 'headerdisplay'
	 *      - 'headerrowstart'
	 *      - 'headerrowend'
	 *      - 'headercellstart'
	 *      - 'headercellend'
	 *      - 'cellstart'
	 *      - 'cellend'
	 *      - 'linkpostcellstart'
	 *      - 'linkposttodaycellstart'
	 *      - 'todaycellstart'
	 *      - 'todaycellstartpost'
	 *      - 'navigation' : Where do we want to have the navigation arrows? (Default: 'tfoot')
	 *      - 'browseyears' : boolean  Do we want arrows to move one year at a time?
	 *      - 'min_timestamp' : Minimum unix timestamp the user can browse too or 'query' (Default: 2000-01-01)
	 *      - 'max_timestamp' : Maximum unix timestamp the user can browse too or 'query' (Default: now + 1 year )
	 *      - 'postcount_month_atitle'
	 *      - 'postcount_month_atitle_one'
	 *      - 'postcount_year_atitle'
	 *      - 'postcount_year_atitle_one'
	 *      - 'link_type' : 'canonic'|'context' (default: canonic)
	 * @return boolean did we display?
	 */
	function SkinTag( $params )
	{
		global $month;
		global $Blog, $cat_array, $cat_modifier;
		global $show_statuses;
		global $author, $assgn, $status, $types;
		global $m, $w, $dstart, $timestamp_min, $timestamp_max;
		global $s, $sentence, $exact;

		/**
		 * Default params:
		 */
		// This is what will enclose the block in the skin:
		if(!isset($params['block_start'])) $params['block_start'] = '<div class="bSideItem">';
		if(!isset($params['block_end'])) $params['block_end'] = "</div>\n";

		// Title:
		if(!isset($params['block_title_start'])) $params['block_title_start'] = '<h3>';
		if(!isset($params['block_title_end'])) $params['block_title_end'] = '</h3>';
		if(!isset($params['title'])) $params['title'] = '';


		$Calendar = & new Calendar( $m, $params );

		// TODO: automate with a table inside of Calendatr object. Table should also contain descriptions and default values to display in help screen.
		// Note: minbrowse and maxbrowe already work this way.
		if( isset($params['displaycaption']) ) $Calendar->set( 'displaycaption', $params['displaycaption'] );
		if( isset($params['monthformat']) ) $Calendar->set( 'monthformat', $params['monthformat'] );
		if( isset($params['linktomontharchive']) ) $Calendar->set( 'linktomontharchive', $params['linktomontharchive'] );
		if( isset($params['tablestart']) ) $Calendar->set( 'tablestart', $params['tablestart'] );
		if( isset($params['tableend']) ) $Calendar->set( 'tableend', $params['tableend'] );
		if( isset($params['monthstart']) ) $Calendar->set( 'monthstart', $params['monthstart'] );
		if( isset($params['monthend']) ) $Calendar->set( 'monthend', $params['monthend'] );
		if( isset($params['rowstart']) ) $Calendar->set( 'rowstart', $params['rowstart'] );
		if( isset($params['rowend']) ) $Calendar->set( 'rowend', $params['rowend'] );
		if( isset($params['headerdisplay']) ) $Calendar->set( 'headerdisplay', $params['headerdisplay'] );
		if( isset($params['headerrowstart']) ) $Calendar->set( 'headerrowstart', $params['headerrowstart'] );
		if( isset($params['headerrowend']) ) $Calendar->set( 'headerrowend', $params['headerrowend'] );
		if( isset($params['headercellstart']) ) $Calendar->set( 'headercellstart', $params['headercellstart'] );
		if( isset($params['headercellend']) ) $Calendar->set( 'headercellend', $params['headercellend'] );
		if( isset($params['cellstart']) ) $Calendar->set( 'cellstart', $params['cellstart'] );
		if( isset($params['cellend']) ) $Calendar->set( 'cellend', $params['cellend'] );
		if( isset($params['emptycellstart']) ) $Calendar->set( 'emptycellstart', $params['emptycellstart'] );
		if( isset($params['emptycellend']) ) $Calendar->set( 'emptycellend', $params['emptycellend'] );
		if( isset($params['emptycellcontent']) ) $Calendar->set( 'emptycellcontent', $params['emptycellcontent'] );
		if( isset($params['linkpostcellstart']) ) $Calendar->set( 'linkpostcellstart', $params['linkpostcellstart'] );
		if( isset($params['linkposttodaycellstart']) ) $Calendar->set( 'linkposttodaycellstart', $params['linkposttodaycellstart'] );
		if( isset($params['todaycellstart']) ) $Calendar->set( 'todaycellstart', $params['todaycellstart'] );
		if( isset($params['todaycellstartpost']) ) $Calendar->set( 'todaycellstartpost', $params['todaycellstartpost'] );
		if( isset($params['navigation']) ) $Calendar->set( 'navigation', $params['navigation'] );
		if( isset($params['browseyears']) ) $Calendar->set( 'browseyears', $params['browseyears'] );
		if( isset($params['postcount_month_atitle']) ) $Calendar->set( 'postcount_month_atitle', $params['postcount_month_atitle'] );
		if( isset($params['postcount_month_atitle_one']) ) $Calendar->set( 'postcount_month_atitle_one', $params['postcount_month_atitle_one'] );
		if( isset($params['postcount_year_atitle']) ) $Calendar->set( 'postcount_year_atitle', $params['postcount_year_atitle'] );
		if( isset($params['postcount_year_atitle_one']) ) $Calendar->set( 'postcount_year_atitle_one', $params['postcount_year_atitle_one'] );
		// Link type:
		if( isset($params['link_type']) ) $Calendar->set( 'link_type', $params['link_type'] );
		if( isset($params['context_isolation']) ) $Calendar->set( 'context_isolation', $params['context_isolation'] );

		echo $params['block_start'];

		if( !empty($params['title']) )
		{	// We want to display a title for the widget block:
			echo $params['block_title_start'];
			echo $params['title'];
			echo $params['block_title_end'];
		}

		// CONSTRUCT THE WHERE CLAUSE:

		// - - Select a specific Item:
		// $this->ItemQuery->where_ID( $p, $title );

		if( $Calendar->link_type == 'context' )
		{	// We want to preserve the current context:
			// * - - Restrict to selected blog/categories:
			$Calendar->ItemQuery->where_chapter2( $Blog, $cat_array, $cat_modifier );

			// * Restrict to the statuses we want to show:
			$Calendar->ItemQuery->where_visibility( $show_statuses );

			// Restrict to selected authors:
			$Calendar->ItemQuery->where_author( $author );

			// Restrict to selected assignees:
			$Calendar->ItemQuery->where_assignees( $assgn );

			// Restrict to selected satuses:
			$Calendar->ItemQuery->where_statuses( $status );

			// - - - + * * if a month is specified in the querystring, load that month:
			$Calendar->ItemQuery->where_datestart( /* NO m */'', /* NO w */'', $dstart, '', $timestamp_min, $timestamp_max );

			// Keyword search stuff:
			$Calendar->ItemQuery->where_keywords( $s, $sentence, $exact );

			// Exclude pages and intros:
			$Calendar->ItemQuery->where_types( $types );
		}
		else
		{	// We want to preserve only the minimal context:
			// * - - Restrict to selected blog/categories:
			$Calendar->ItemQuery->where_chapter2( $Blog, array(), '' );

			// * Restrict to the statuses we want to show:
			$Calendar->ItemQuery->where_visibility( $show_statuses );

			// - - - + * * if a month is specified in the querystring, load that month:
			$Calendar->ItemQuery->where_datestart( /* NO m */'', /* NO w */'', '', '', $timestamp_min, $timestamp_max );

			// Exclude pages and intros:
			$Calendar->ItemQuery->where_types( '-1000,1500,1520,1530,1570,1600' );
		}

		// DISPLAY:
		$Calendar->display( );

		echo $params['block_end'];

		return true;
	}
}


/**
 * Calendar
 *
 * @package evocore
 */
class Calendar
{
	var $year;

	/**
	 * The month to display or empty in mode 'year' with no selected month.
	 * @var string
	 */
	var $month;

	/**
	 * 'month' or 'year'
	 * @var string
	 */
	var $mode;

	var $where;
	/**
	 * SQL query string
	 * @var string
	 */
	var $request;
	/**
	 * Result set
	 */
	var $result;
	/**
	 * Number of rows in result set
	 * @var integer
	 */
	var $result_num_rows;

	var $displaycaption;
	var $monthformat;
	var $monthstart;
	var $monthend;
	var $linktomontharchive;
	/**
	 * Where to do the navigation
	 *
	 * 'caption' or 'tfoot';
	 *
	 * @var string
	 */
	var $navigation = 'tfoot';

	var $tablestart;
	var $tableend;

	var $rowstart;
	var $rowend;

	var $headerdisplay;
	var $headerrowstart;
	var $headerrowend;
	var $headercellstart;
	var $headercellend;

	var $cellstart;
	var $cellend;

	var $emptycellstart;
	var $emptycellend;

	var $emptycellcontent;

	/**
	 * Do we want to browse years in the caption? True by default for mode == year,
	 * false for mode == month (gets set in constructor).
	 * @var boolean
	 */
	var $browseyears;

	/**
	 * Is today in the displayed frame?
	 * @var boolean
	 * @access protected
	 */
	var $today_is_visible;


	var $link_type;
	var $context_isolation;

	var $params = array( );

	/**
	 * Calendar::Calendar(-)
	 *
	 * Constructor
	 *
	 * @param string Month ('YYYYMM'), year ('YYYY'), current ('')
	 * @param array Associative array of parameters. Valid keys are:
	 *      - 'min_timestamp' : Minimum unix timestamp the user can browse too or 'query' (Default: 2000-01-01)
	 *      - 'max_timestamp' : Maximum unix timestamp the user can browse too or 'query' (Default: now + 1 year )
	 */
	function Calendar( $m = '', $params = array() )
	{
		global $localtimenow;

		$this->dbtable = 'T_items__item';
		$this->dbprefix = 'post_';
		$this->dbIDname = 'post_ID';

		// OBJECT THAT WILL BE USED TO CONSTRUCT THE WHERE CLAUSE:
		$this->ItemQuery = new ItemQuery( $this->dbtable, $this->dbprefix, $this->dbIDname );	// COPY!!

		$localyearnow = date( 'Y', $localtimenow );
		$localmonthnow = date( 'm', $localtimenow );

		// Find out which month to display:
		if( empty($m) )
		{ // Current month (monthly)
			$this->year = $localyearnow;
			$this->month = $localmonthnow;
			$this->mode = 'month';

			$this->today_is_visible = true;
		}
		else
		{	// We have requested a specific date
			$this->year = substr($m, 0, 4);
			if (strlen($m) < 6)
			{ // no month provided
				$this->mode = 'year';

				if( $this->year == $localyearnow )
				{ // we display current year, month gets current
					$this->month = $localmonthnow;
				}
				else
				{ // highlight no month, when not current year
					$this->month = '';
				}
			}
			else
			{
				$this->month = substr($m, 4, 2);
				$this->mode = 'month';
			}

			$this->today_is_visible = ( $this->year == $localyearnow
				&& ( empty($this->month) || $this->month == $localmonthnow ) );
		}


		// Default styling:
		$this->displaycaption = 1;	// set this to 0 if you don't want to display the month name
		$this->monthformat = 'F Y';
		$this->linktomontharchive = true;  // month displayed as link to month' archive

		$this->tablestart = '<table class="bCalendarTable" cellspacing="0" summary="Monthly calendar with links to each day\'s posts">'."\n";
		$this->tableend = '</table>';

		$this->monthstart = '<caption>';
		$this->monthend = "</caption>\n";

		$this->rowstart = '<tr class="bCalendarRow">' . "\n";
		$this->rowend = "</tr>\n";

		$this->headerdisplay = 'D';	 // D => 'Fri'; e => 'F', l (lowercase l) => 'Friday'
		// These codes are twisted because they're the same as for date formats.
		// set this to 0 or '' if you don't want to display the "Mon Tue Wed..." header

		$this->headerrowstart = '<thead><tr class="bCalendarRow">' . "\n";
		$this->headerrowend = "</tr></thead>\n";
		$this->headercellstart = '<th class="bCalendarHeaderCell" abbr="[abbr]" scope="col" title="[abbr]">';	// please leave [abbr] there !
		$this->headercellend = "</th>\n";

		$this->cellstart = '<td class="bCalendarCell">';
		$this->cellend = "</td>\n";

		$this->emptycellstart = '<td class="bCalendarEmptyCell">';
		$this->emptycellend = "</td>\n";
		$this->emptycellcontent = '&nbsp;';

		$this->linkpostcellstart = '<td class="bCalendarLinkPost">';
		$this->linkposttodaycellstart = '<td class="bCalendarLinkPostToday">';
		$this->todaycellstart = '<td id="bCalendarToday">';
		$this->todaycellstartpost = '<td id="bCalendarToday" class="bCalendarLinkPost">';

		// Where do we want to have the navigation arrows? tfoot or caption
		$this->navigation = 'tfoot';

		// Do we want arrows to move one year at a time?
		$this->browseyears = true;

		/**#@+
		 * Display number of posts with days/months
		 *
		 * - set to '' (empty) to disable
		 * - %d gets replaced with the number of posts on that day/month
		 */
		$this->postcount_month_atitle = T_('%d posts'); 						// in archive links title tag
		$this->postcount_month_atitle_one = T_('1 post');  					//  -- " -- [for single post]
		$this->postcount_year_atitle = T_('%d posts'); 							// in archive links title tag
		$this->postcount_year_atitle_one = T_('1 post'); 						// in archive links title tag
		/**#@-*/

		// Link type:
		$this->link_type = 'canonic';
		$this->context_isolation = 'm,w,p,title,unit,dstart';

		// New style params:
		$this->params = $params;

		// Default values:
		if( empty( $this->params['min_timestamp'] ) )
		{	// 2000-01-01:
			$this->params['min_timestamp'] = mktime( 0, 0, 0, 1, 1, 2000 );
		}
		if( empty( $this->params['max_timestamp'] ) )
		{	// Now + 1 year:
			$this->params['max_timestamp'] = mktime( 23, 59, 59, date( 'm', $localtimenow  ),  date( 'd', $localtimenow ),  date( 'Y', $localtimenow )+1 );
		}

	}


	/*
	 * Calendar->set(-)
	 *
	 * set a variable
	 */
	function set( $var, $value )
	{
		$this->$var = $value;
	}


	/**
	 * Display the calendar.
	 *
	 * @todo If a specific day (mode == month) or month (mode == year) is selected, apply another class (default to some border)
	 */
	function display()
	{
		global $DB;
		global $weekday, $weekday_abbrev, $weekday_letter, $month, $month_abbrev;
		global $time_difference;

		if( $this->mode == 'month' )
		{
			$end_of_week = ((locale_startofweek() + 7) % 7);

			// fplanque>> note: I am removing the searchframe thing because 1) I don't think it's of any use
			// and 2) it's brutally inefficient! If someone needs this it should be implemented with A SINGLE
			// QUERY which gets the last available post (BTW, I think there is already a function for that somwhere)
			// walter>> As we are just counting items, the ORDER BY db_prefix . date_start doesn't matter. And a side effect
			// of that is make queries standart compatible (compatible with other databases than MySQL)

			$arc_sql = 'SELECT COUNT(DISTINCT '.$this->dbIDname.') AS item_count,
													EXTRACT(DAY FROM '.$this->dbprefix.'datestart) AS myday
									FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
										INNER JOIN T_categories ON postcat_cat_ID = cat_ID
									WHERE EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) = \''.$this->year.'\'
										AND EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) = \''.$this->month.'\'
										'.$this->ItemQuery->get_where( ' AND ' ).'
									GROUP BY myday '.$this->ItemQuery->get_group_by( ', ' );
			// echo $this->ItemQuery->where;
			$arc_result = $DB->get_results( $arc_sql, ARRAY_A );

			foreach( $arc_result as $arc_row )
			{
				if( !isset( $daysinmonthwithposts[ $arc_row['myday'] ] ) )
				{
					$daysinmonthwithposts[ $arc_row['myday'] ] = 0;
				}
				// The '+' situation actually only happens when we have a complex GROUP BY above
				// (multiple categories wcombined with "ALL")
				$daysinmonthwithposts[ $arc_row['myday'] ] += $arc_row['item_count'];
			}

			$daysinmonth = intval(date('t', mktime(0, 0, 0, $this->month, 1, $this->year)));
			// echo 'days in month=', $daysinmonth;


			// caution: offset bug inside (??)
			$datestartofmonth = mktime(0, 0, 0, $this->month, 1, $this->year );
			// echo date( locale_datefmt(), $datestartofmonth );
			$calendarblah = get_weekstartend( $datestartofmonth, locale_startofweek() );
			$calendarfirst = $calendarblah['start'];


			$dateendofmonth = mktime(0, 0, 0, $this->month, $daysinmonth, $this->year);
			// echo 'end of month: '.date( 'Y-m-d H:i:s', $dateendofmonth );
			$calendarblah = get_weekstartend( $dateendofmonth, locale_startofweek() );
			$calendarlast = $calendarblah['end'];
			// echo date( ' Y-m-d H:i:s', $calendarlast );



			// here the offset bug is corrected
			if( (intval(date('d', $calendarfirst)) > 1) && (intval(date('m', $calendarfirst)) == intval($this->month)) )
			{
				#pre_dump( 'with offset bug', date('Y-m-d', $calendarfirst) );
				$calendarfirst = $calendarfirst - 604800 /* 1 week */;
				#pre_dump( 'without offset bug', date('Y-m-d', $calendarfirst) );
			}
		}
		else
		{ // mode is 'year'
			// Find months with posts
			$arc_sql = 'SELECT COUNT(DISTINCT '.$this->dbIDname.') AS item_count, EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) AS mymonth
									FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
												INNER JOIN T_categories ON postcat_cat_ID = cat_ID
									WHERE EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) = \''.$this->year.'\' '
										.$this->ItemQuery->get_where( ' AND ' ).'
									GROUP BY mymonth '.$this->ItemQuery->get_group_by( ', ' );
			$arc_result = $DB->get_results( $arc_sql, ARRAY_A );

			if( $DB->num_rows > 0 )
			{ // OK we have a month with posts!
				foreach( $arc_result as $arc_row )
				{
					$monthswithposts[ $arc_row['mymonth'] ] = $arc_row['item_count'];
				}
			}
		}


		// ** display everything **

		echo $this->tablestart;

		// CAPTION :

		if( $this->displaycaption )
		{ // caption:
			echo $this->monthstart;

			if( $this->navigation == 'caption' )
			{
				echo implode( '&nbsp;', $this->getNavLinks( 'prev' ) ).'&nbsp;';
			}

			if( $this->mode == 'month' )
			{ // MONTH CAPTION:
				$text = date_i18n($this->monthformat, mktime(0, 0, 0, $this->month, 1, $this->year));

				if( $this->linktomontharchive )
				{ // chosen month with link to archives
					echo $this->archive_link( $text, T_('View monthly archive'), $this->year, $this->month );
				}
				else
				{
					echo $text;
				}
			}
			else
			{ // YEAR CAPTION:
				echo date_i18n('Y', mktime(0, 0, 0, 1, 1, $this->year)); // display year
			}

			if( $this->navigation == 'caption' )
			{
				echo '&nbsp;'.implode( '&nbsp;', $this->getNavLinks( 'next' ) );
			}

			echo $this->monthend;
		}

		// HEADER :

		if( !empty($this->headerdisplay) && ($this->mode == 'month') )
		{ // Weekdays:
			echo $this->headerrowstart;

			for( $i = locale_startofweek(), $j = $i + 7; $i < $j; $i = $i + 1)
			{
				echo str_replace('[abbr]', T_($weekday[($i % 7)]), $this->headercellstart);
				switch( $this->headerdisplay )
				{
					case 'e':
						// e => 'F'
						echo T_($weekday_letter[($i % 7)]);
						break;

					case 'l':
						// l (lowercase l) => 'Friday'
						echo T_($weekday[($i % 7)]);
						break;

					default:	// Backward compatibility: any non emty value will display this
						// D => 'Fri'
						echo T_($weekday_abbrev[($i % 7)]);
				}

				echo $this->headercellend;
			}

			echo $this->headerrowend;
		}

		// FOOTER :

		if( $this->navigation == 'tfoot' )
		{ // We want to display navigation in the table footer:
			echo "<tfoot>\n";
			echo "<tr>\n";
			echo '<td colspan="'.( ( $this->mode == 'month' ? 2 : 1 ) + (int)$this->today_is_visible ).'" id="prev">';
			echo implode( '&nbsp;', $this->getNavLinks( 'prev' ) );
			echo "</td>\n";

			if( $this->today_is_visible )
			{
				if( $this->mode == 'month' )
				{
					echo '<td class="pad">&nbsp;</td>'."\n";
				}
			}
			else
			{
				echo '<td colspan="'.( $this->mode == 'month' ? '3' : '2' ).'" class="center">'
							.$this->archive_link( T_('Current'), '', date('Y'), ( $this->mode == 'month' ? date('m') : NULL ) )
							.'</td>';
			}
			echo '<td colspan="'.( ( $this->mode == 'month' ? 2 : 1 ) + (int)$this->today_is_visible ).'" id="next">';
			echo implode( '&nbsp;', $this->getNavLinks( 'next' ) );
			echo "</td>\n";
			echo "</tr>\n";
			echo "</tfoot>\n";
		}

		// REAL TABLE DATA :

		echo $this->rowstart;

		if( $this->mode == 'year' )
		{	// DISPLAY MONTHS:

			for ($i = 1; $i < 13; $i = $i + 1)
			{	// For each month:
				if( isset($monthswithposts[ $i ]) )
				{
					if( $this->month == $i )
					{
						echo $this->todaycellstartpost;
					}
					else
					{
						echo $this->linkpostcellstart;
					}
					if( $monthswithposts[ $i ] > 1 && !empty($this->postcount_year_atitle) )
					{ // display postcount
						$title = sprintf($this->postcount_year_atitle, $monthswithposts[ $i ]);
					}
					elseif( !empty($this->postcount_year_atitle_one) )
					{ // display postcount for one post
						$title = sprintf($this->postcount_year_atitle_one, 1);
					}
					else
					{
						$title = '';
					}
					echo $this->archive_link( T_($month_abbrev[ zeroise($i, 2) ]), $title, $this->year, $i );
				}
				elseif( $this->month == $i )
				{ // current month
					echo $this->todaycellstart;
					echo T_($month_abbrev[ zeroise($i, 2) ]);
				}
				else
				{
					echo $this->cellstart;
					echo T_($month_abbrev[ zeroise($i, 2) ]);
				}
				echo $this->cellend;
				if( $i == 4 || $i == 8 )
				{ // new row
					echo $this->rowend.$this->rowstart;
				}
			}
		}
		else // mode == 'month'
		{	// DISPLAY DAYS of current month:
			$dow = 0;
			$last_day = -1;
			$dom_displayed = 0; // days of month displayed

			for( $i = $calendarfirst; $i <= $calendarlast; $i = $i + 86400 )
			{ // loop day by day (86400 seconds = 24 hours; but not on days where daylight saving changes!)
				if( $dow == 7 )
				{ // We need to start a new row:
					if( $dom_displayed >= $daysinmonth )
					{ // Last day already displayed!
						break;
					}
					echo $this->rowend;
					echo $this->rowstart;
					$dow = 0;
				}
				$dow++;

				// correct daylight saving ("last day"+86400 would lead to "last day at 23:00")
				// fp> TODO: use mkdate()
				while( date('j', $i) == $last_day )
				{
					$i += 3600;
				}
				$last_day = date('j', $i);


				if (date('m', $i) != $this->month)
				{ // empty cell
					echo $this->emptycellstart;
					echo $this->emptycellcontent;
					echo $this->emptycellend;
				}
				else
				{ // This day is in this month
					$dom_displayed++;
					$calendartoday = (date('Ymd',$i) == date('Ymd', (time() + $time_difference)));

					if( isset($daysinmonthwithposts[ date('j', $i) ]) )
					{
						if( $calendartoday )
						{
							echo $this->todaycellstartpost;
						}
						else
						{
							echo $this->linkpostcellstart;
						}
						if( $daysinmonthwithposts[ date('j', $i) ] > 1 && !empty($this->postcount_month_atitle) )
						{ // display postcount
							$title = sprintf($this->postcount_month_atitle, $daysinmonthwithposts[ date('j', $i) ]);
						}
						elseif( !empty($this->postcount_month_atitle_one) )
						{ // display postcount for one post
							$title = sprintf($this->postcount_month_atitle_one, 1);
						}
						else
						{
							$title = '';
						}
						echo $this->archive_link( date('j',$i), $title, $this->year, $this->month, date('d',$i) );
					}
					elseif ($calendartoday)
					{
						echo $this->todaycellstart;
						echo date('j',$i);
					}
					else
					{
						echo $this->cellstart;
						echo date('j',$i);
					}
					echo $this->cellend;
				}
			} // loop day by day
		} // mode == 'month'

		echo $this->rowend;

		echo $this->tableend;

	}  // display(-)


	/**
	 * Create a link to archive, using either URL params or extra path info.
	 *
	 * Can make contextual links.
	 *
	 * @param string
	 * @param string
	 * @param string year
	 * @param string month
	 * @param string day
	 */
	function archive_link( $text, $title, $year, $month = NULL, $day = NULL )
	{
    /**
		 * @var Blog
		 */
		global $Blog;

		if( $this->link_type == 'context' )
		{	// We want to preserve context:
			$url_params = 'm='.$year;
			if( !empty( $month ) )
			{
				$url_params .= zeroise($month,2);
				if( !empty( $day ) )
				{
					$url_params .= zeroise($day,2);
				}
			}
			return '<a rel="nofollow" href="'.regenerate_url( $this->context_isolation, $url_params ).'">'.format_to_output($text).'</a>';
		}
		else
		{	// We want a canonic link:
			return $Blog->gen_archive_link( $text, $title, $year, $month, $day );
		}
	}


	/**
	 * Get links to navigate between month / year.
	 *
	 * Unless min/max_timestamp='query' has been specified, this will not do any (time consuming!) queries to check where the posts are.
	 *
	 * @param string 'prev' / 'next'
	 * @return array
	 */
	function getNavLinks( $direction )
	{
		global $DB, $localtimenow;

		//pre_dump( 'get_nav_links', $direction );

		$r = array();

		if( $this->params['min_timestamp'] == 'query' || $this->params['max_timestamp'] == 'query' )
		{ // Do inits:
			// WE NEED SPECIAL QUERY PARAMS WHEN MOVING THOUGH MONTHS ( NO dstart especially! )
			$nav_ItemQuery = & new ItemQuery( $this->dbtable, $this->dbprefix, $this->dbIDname );	// TEMP object
			// Restrict to selected blog/categories:
			$nav_ItemQuery->where_chapter2( $this->ItemQuery->Blog, $this->ItemQuery->cat_array, $this->ItemQuery->cat_modifier );
			// Restrict to the statuses we want to show:
			$nav_ItemQuery->where_visibility( $this->ItemQuery->show_statuses );
			// Restrict to selected authors:
			$nav_ItemQuery->where_author( $this->ItemQuery->author );
			// if a month is specified in the querystring, load that month:
			$nav_ItemQuery->where_datestart( /* NO m */'', /* NO w */'', /* NO dstart */'', '', $this->ItemQuery->timestamp_min, $this->ItemQuery->timestamp_max );
			// Keyword search stuff:
			$nav_ItemQuery->where_keywords( $this->ItemQuery->keywords, $this->ItemQuery->phrase, $this->ItemQuery->exact );
			// Exclude pages and intros:
			$nav_ItemQuery->where_types( $this->ItemQuery->types );
		}

		switch( $direction )
		{
			case 'prev':
				//pre_dump( $this->params['min_timestamp'] );
				$r[] = '';

				if( empty($this->month) )
				{ // if $this->month is empty, we're in mode "year" with no selected month
					$use_range_month = 12;
					$use_range_day = 31;
				}
				else
				{
					$use_range_month = $this->month;
					$use_range_day = 1;	// Note: cannot use current day since all months do not have same number of days
				}

				/*
				 * << (PREV YEAR)
				 */
				if( $this->browseyears )
				{	// We want arrows to move one year at a time
					if( $this->params['min_timestamp'] == 'query' )
					{	// Let's query to find the correct year:
						if( $row = $DB->get_row(
								'SELECT EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) AS year,
												EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) AS month
									FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
												INNER JOIN T_categories ON postcat_cat_ID = cat_ID
									WHERE EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) < '.$this->year.'
												'.$nav_ItemQuery->get_where( ' AND ' )
												.$nav_ItemQuery->get_group_by( ' GROUP BY ' ).'
									ORDER BY EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) DESC, ABS( '.$use_range_month.' - EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) ) ASC
									LIMIT 1', OBJECT, 0, 'Calendar: find prev year with posts' )
							)
						{
							$prev_year_year = $row->year;
							$prev_year_month = $row->month;
						}
					}
					else
					{ // Let's see if the previous year is in the desired navigation range:
						$prev_year_ts = mktime( 0, 0, 0, $use_range_month, $use_range_day,  $this->year-1 );
						if( $prev_year_ts >= $this->params['min_timestamp'] )
						{
							$prev_year_year = date( 'Y', $prev_year_ts );
							$prev_year_month = date( 'm', $prev_year_ts );
						}
					}
				}

				if( !empty($prev_year_year) )
				{	// We have a link to display:
					$r[] = $this->archive_link( '&lt;&lt;', sprintf(
												( $this->mode == 'month'
														? /* Calendar link title to a month in a previous year */ T_('Previous year (%04d-%02d)')
														: /* Calendar link title to a previous year */ T_('Previous year (%04d)') ),
												$prev_year_year, $prev_year_month ), $prev_year_year, ($this->mode == 'month') ? $prev_year_month : NULL ) ;
				}


				/*
				 * < (PREV MONTH)
				 */
				if( $this->mode == 'month' )
				{ // We are browsing months, we'll display arrows to move one month at a time:
					if( $this->params['min_timestamp'] == 'query' )
					{	// Let's query to find the correct month:
						if( $row = $DB->get_row(
								'SELECT EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) AS month,
												EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) AS year
								FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
									INNER JOIN T_categories ON postcat_cat_ID = cat_ID
								WHERE
								(
									EXTACT(YEAR FROM '.$this->dbprefix.'datestart) < '.($this->year).'
									OR ( EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) = '.($this->year).'
												AND EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) < '.($this->month).'
											)
								)
								'.$nav_ItemQuery->get_where( ' AND ' )
								 .$nav_ItemQuery->get_group_by( ' GROUP BY ' ).'
								ORDER BY EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) DESC, EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) DESC
								LIMIT 1',
								OBJECT,
								0,
								'Calendar: Find prev month with posts' )
							)
						{
							$prev_month_year = $row->year;
							$prev_month_month = $row->month;
						}
					}
					else
					{ // Let's see if the previous month is in the desired navigation range:
						$prev_month_ts = mktime( 0, 0, 0, $this->month-1, 1, $this->year ); // Note: cannot use current day since all months do not have same number of days
						if( $prev_month_ts >= $this->params['min_timestamp'] )
						{
							$prev_month_year = date( 'Y', $prev_month_ts );
							$prev_month_month = date( 'm', $prev_month_ts );
						}
					}
				}

				if( !empty($prev_month_year) )
				{	// We have a link to display:
					$r[] = $this->archive_link( '&lt;', sprintf( T_('Previous month (%04d-%02d)'), $prev_month_year, $prev_month_month ), $prev_month_year, $prev_month_month );
				}
				break;


			case 'next':
				//pre_dump( $this->params['max_timestamp'] );

				/*
				 * > (NEXT MONTH)
				 */
				if( $this->mode == 'month' )
				{ // We are browsing months, we'll display arrows to move one month at a time:
					if( $this->params['max_timestamp'] == 'query' )
					{	// Let's query to find the correct month:
						if( $row = $DB->get_row(
								'SELECT EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) AS month,
												EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) AS year
								FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
									INNER JOIN T_categories ON postcat_cat_ID = cat_ID
								WHERE
								(
									EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) > '.($this->year).'
									OR ( EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) = '.($this->year).'
												AND EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) > '.($this->month).'
											)
								)
								'.$nav_ItemQuery->get_where( ' AND ' )
								 .$nav_ItemQuery->get_group_by( ' GROUP BY ' ).'
								ORDER BY EXTRACT(YEAR FROM '.$this->dbprefix.'datestart), EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) ASC
								LIMIT 1',
								OBJECT,
								0,
								'Calendar: Find next month with posts' )
							)
						{
							$next_month_year = $row->year;
							$next_month_month = $row->month;
						}
					}
					else
					{ // Let's see if the next month is in the desired navigation range:
						$next_month_ts = mktime( 0, 0, 0, $this->month+1, 1,  $this->year ); // Note: cannot use current day since all months do not have same number of days
						if( $next_month_ts <= $this->params['max_timestamp'] )
						{
							$next_month_year = date( 'Y', $next_month_ts );
							$next_month_month = date( 'm', $next_month_ts );
						}
					}
				}

				if( !empty($next_month_year) )
				{	// We have a link to display:
					$r[] = $this->archive_link( '&gt;', sprintf( T_('Next month (%04d-%02d)'), $next_month_year, $next_month_month ), $next_month_year, $next_month_month );
				}


				if( empty($this->month) )
				{ // if $this->month is empty, we're in mode "year" with no selected month
					$use_range_month = 12;
					$use_range_day = 31;
				}
				else
				{
					$use_range_month = $this->month;
					$use_range_day = 1;	// Note: cannot use current day since all months do not have same number of days
				}

				/*
				 * >> (NEXT YEAR)
				 */
				if( $this->browseyears )
				{ // We want arrows to move one year at a time
					if( $this->params['max_timestamp'] == 'query' )
					{	// Let's query to find the correct year:
					if( $row = $DB->get_row(
							'SELECT EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) AS year,
											EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) AS month
								FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
									INNER JOIN T_categories ON postcat_cat_ID = cat_ID
								WHERE EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) > '.$this->year.'
								 '.$nav_ItemQuery->get_where( ' AND ' )
								 .$nav_ItemQuery->get_group_by( ' GROUP BY ' ).'
								ORDER BY EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) ASC, ABS( '.$use_range_month.' - EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) ) ASC
								LIMIT 1', OBJECT, 0, 'Calendar: find next year with posts' )
						)
						{
							$next_year_year = $row->year;
							$next_year_month = $row->month;
						}
					}
					else
					{ // Let's see if the next year is in the desired navigation range:
						$next_year_ts = mktime( 0, 0, 0, $use_range_month, $use_range_day,  $this->year+1 );
						if( $next_year_ts <= $this->params['max_timestamp'] )
						{
							$next_year_year = date( 'Y', $next_year_ts );
							$next_year_month = date( 'm', $next_year_ts );
						}
					}

				if( !empty($next_year_year) )
				{	// We have a link to display:
						$r[] = $this->archive_link( '&gt;&gt;', sprintf(
																	( $this->mode == 'month'
																			? /* Calendar link title to a month in a following year */ T_('Next year (%04d-%02d)')
																			: /* Calendar link title to a following year */ T_('Next year (%04d)') ),
																	$next_year_year, $next_year_month ), $next_year_year, ($this->mode == 'month') ? $next_year_month : NULL );
					}
				}
				break;
		}

		return $r;
	}

}

/*
 * $Log$
 * Revision 1.52  2009/01/21 22:36:35  fplanque
 * Cleaner handling of pages and intros in calendar and archives plugins
 *
 * Revision 1.51  2008/03/31 00:27:11  blueyed
 * Nuke unused  global statements; use global  in loop instead of getter
 *
 * Revision 1.50  2008/03/30 14:56:50  fplanque
 * DST fix
 *
 * Revision 1.49  2008/01/21 09:35:41  fplanque
 * (c) 2008
 *
 * Revision 1.48  2007/11/29 21:52:22  fplanque
 * minor
 *
 * Revision 1.47  2007/11/25 18:20:38  fplanque
 * additional SEO settings
 *
 * Revision 1.46  2007/10/09 02:10:50  fplanque
 * URL fixes
 *
 * Revision 1.45  2007/07/01 03:58:08  fplanque
 * cat_array cleanup/debug
 *
 * Revision 1.44  2007/06/23 22:06:47  fplanque
 * CSS cleanup
 *
 * Revision 1.43  2007/06/20 21:42:15  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.42  2007/05/14 02:43:06  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.41  2007/04/26 00:11:04  fplanque
 * (c) 2007
 *
 * Revision 1.40  2007/03/29 10:35:40  fplanque
 * fix
 *
 * Revision 1.39  2007/03/25 10:20:02  fplanque
 * cleaned up archive urls
 *
 * Revision 1.38  2007/03/18 16:54:37  waltercruz
 * Changing the MySQL date functions to the standart (EXTRACT) ones and killing ORDER BY in monthly and year calendar.
 *
 * Revision 1.37  2007/02/06 12:49:39  waltercruz
 * Changing the date queries to the EXTRACT syntax
 *
 * Revision 1.36  2007/01/14 01:31:51  fplanque
 * do not display title by default (bloated)
 *
 * Revision 1.35  2007/01/13 18:36:24  fplanque
 * renamed "Skin Tag" plugins into "Widget" plugins
 * but otherwise they remain basically the same & compatible
 *
 * Revision 1.34  2006/12/26 03:19:12  fplanque
 * assigned a few significant plugin groups
 *
 * Revision 1.33  2006/12/07 23:13:14  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.32  2006/11/24 18:27:27  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.31  2006/11/03 18:22:26  fplanque
 * no message
 *
 * Revision 1.30  2006/10/29 14:52:56  blueyed
 * Fixed bug with daylight saving time, which displayed days twice
 *
 * Revision 1.29  2006/10/14 19:07:17  blueyed
 * Removed TODO
 *
 * Revision 1.28  2006/10/14 19:05:39  blueyed
 * Fixed "mktime() expects parameter 4 to be long, string given" warning; doc
 */
?>