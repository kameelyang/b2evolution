<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_post_list_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_post_list_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_post_list' );
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_( 'Title to display in your skin.' ),
					'size' => 60,
					'defaultvalue' => T_('Contents'),
				),
				'disp_order' => array(
					'label' => T_('Order'),
					'note' => T_('Order to display items'),
					'type' => 'select',
					'options' => array( 'DESC' => T_( 'Newest to oldest' ), 'ASC' => T_( 'Oldest to newest' ), 'RANDOM' => T_( 'Random selection' ) ),
					'defaultvalue' => 'DESC',
				),
				'limit' => array(
					'label' => T_( 'Display' ),
					'note' => T_( 'Max items to display.' ),
					'size' => 4,
					'defaultvalue' => 20,
				),
				'blog_ID' => array(
					'label' => T_( 'Blog' ),
					'note' => T_( 'ID of the blog to use, leave empty for the current blog.' ),
					'size' => 4,
				),
			), parent::get_param_definitions( $params )	);

		return $r;
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Post list');
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('List of all posts; click goes to post.');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		$this->init_display( $params );

		// List of posts:
		$this->disp_item_list( 'posts' );

		return true;
	}
}


/*
 * $Log$
 * Revision 1.5  2007/12/24 12:05:31  yabs
 * bugfix "order" is a reserved name, used by wi_order
 *
 * Revision 1.4  2007/12/24 11:01:22  yabs
 * adding random order
 *
 * Revision 1.3  2007/12/23 16:16:18  fplanque
 * Wording improvements
 *
 * Revision 1.2  2007/12/23 15:44:39  yabs
 * adding params
 *
 * Revision 1.1  2007/06/25 11:02:18  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.2  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.1  2007/06/18 21:25:47  fplanque
 * one class per core widget
 *
 */
?>