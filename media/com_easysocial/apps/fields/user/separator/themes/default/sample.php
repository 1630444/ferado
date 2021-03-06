<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2013 Stack Ideas Sdn Bhd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined( '_JEXEC' ) or die( 'Unauthorized Access' );
?>
<div class="control-group">

	<hr data-separator-type data-separator-hr style="<?php echo ( $params->get( 'type' , 'hr' ) == 'hr' ) ? '' : 'display: none;';?>" />

	<div data-separator-type data-separator-space style="margin-top: 10px;margin-bottom: 10px;text-align:center;<?php echo ( $params->get( 'type' , 'hr' ) == 'space' ) ? '' : 'display: none;';?>"><?php echo JText::_( 'PLG_FIELDS_SEPARATOR_SAMPLE_TEXT' );?></div>

</div>
