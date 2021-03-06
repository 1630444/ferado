<?php
/**
* @package		%PACKAGE%
* @subpackge	%SUBPACKAGE%
* @copyright	Copyright (C) 2010 - 2012 Stack Ideas Sdn Bhd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined( '_JEXEC' ) or die( 'Unauthorized Access' );

Foundry::import( 'admin:/includes/apps/apps' );

/**
 * Friends application for EasySocial.
 *
 * @since	1.0
 * @author	Mark Lee <mark@stackideas.com>
 */
class SocialUserAppBlog extends SocialAppItem
{
	/**
	 * Class constructor.
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function __construct()
	{
		parent::__construct();
	}

	public function exists()
	{
		$file 	= JPATH_ROOT . '/components/com_easyblog/helpers/helper.php';

		if( !JFile::exists( $file ) )
		{
			return false;
		}

		require_once( $file );

		return true;
	}

	/**
	 * Prepares the stream item
	 *
	 * @since	1.0
	 * @access	public
	 * @param	SocialStreamItem	The stream object.
	 * @param	bool				Determines if we should respect the privacy
	 */
	public function onPrepareStream( SocialStreamItem &$item, $includePrivacy = true )
	{
		if( $item->context != 'blog' )
		{
			return;
		}

		if( $this->exists() === false )
		{
			return;
		}

		$element	= $item->context;
		$uid     	= $item->contextId;

		// Get current logged in user.
		$my         = Foundry::user();

		// Get user's privacy.
		$privacy 	= Foundry::privacy( $my->id );

		if( $includePrivacy )
		{
			// Determine if the user can view this current context
			if( !$privacy->validate( 'easyblog.blog.view' , $uid, $element , $item->actor->id ) )
			{
				return;
			}
		}

		// Define standard stream looks
		$item->display 	= SOCIAL_STREAM_DISPLAY_FULL;
		$item->color 	= '#e9db66';

		// New blog post
		if( $item->verb == 'create' )
		{
			$this->prepareNewBlogStream( $item );
		}

		// New comment
		if( $item->verb == 'create.comment' )
		{
			$this->prepareNewCommentStream( $item );
		}

		// Featured posts
		if( $item->verb == 'featured' )
		{
			$this->prepareFeaturedBlogStream( $item );
		}


		if( $includePrivacy )
		{
			$item->privacy 	= $privacy->form( $uid, $element, $item->actor->id, 'easyblog.blog.view' );
		}

	}

	private function prepareFeaturedBlogStream( &$item )
	{
		$blog 	= EasyBlogHelper::getTable( 'Blog' );
		$blog->load( $item->contextId );

		// Format the likes for the stream
		$likes 			= Foundry::likes();
		$likes->get( $item->contextId , 'blog' );
		$item->likes	= $likes;

		//$url 			= EasyBlogRouter::_( 'index.php?option=com_easyblog&view=entry&id=' . $blog->id );
		$url 			= EasyBlogRouter::_( 'index.php?option=com_easyblog&view=entry&id=' . $blog->id, true, null, false, true );

		// Apply comments on the stream
		$this->prepareCommentForStream( $item );

		$comments			= Foundry::comments( $item->contextId , 'blogfeatured' , SOCIAL_APPS_GROUP_USER , array( 'url' => $url ) );
		$item->comments 	= $comments;

		$date 	= EasyBlogHelper::getDate( $blog->created );

		$config 	= EasyBlogHelper::getConfig();
		$source 	= $config->get( 'integrations_easysocial_stream_newpost_source' , 'intro' );

		$content 	= isset( $blog->$source ) && !empty( $blog->$source ) ? $blog->$source : $blog->intro;

		$this->set( 'date'		, $date );
		$this->set( 'permalink' , $url );
		$this->set( 'blog'		, $blog );
		$this->set( 'actor' 	, $item->actor );
		$this->set( 'content'	, $content );

		$catUrl = EasyBlogRouter::_( 'index.php?option=com_easyblog&view=categories&layout=listings&id=' . $blog->category_id, true, null, false, true );
		$this->set( 'categorypermalink'	, $catUrl );

		$item->title	= parent::display( 'streams/' . $item->verb . '.title' );
		$item->content	= parent::display( 'streams/' . $item->verb . '.content' );
	}

	private function prepareNewBlogStream( &$item )
	{
		$blog 	= EasyBlogHelper::getTable( 'Blog' );
		$blog->load( $item->contextId );

		// Format the likes for the stream
		$likes 			= Foundry::likes();
		$likes->get( $item->contextId , 'blog' );
		$item->likes	= $likes;


		$url = EasyBlogRouter::_( 'index.php?option=com_easyblog&view=entry&id=' . $blog->id, true, null, false, true );

		// Apply comments on the stream
		$comments 	= Foundry::comments( $item->contextId , 'blog' , SOCIAL_APPS_GROUP_USER , $url );

		$item->comments 	= $comments;

		// We might want to use some javascript codes.
		EasyBlogHelper::loadHeaders();

		$date 		= EasyBlogHelper::getDate( $blog->created );

		$config 	= EasyBlogHelper::getConfig();
		$source 	= $config->get( 'integrations_easysocial_stream_newpost_source' , 'intro' );

		// See if there's any audio files to process.
		$audios 	= EasyBlogHelper::getHelper( 'Audio' )->getHTMLArray( $blog->intro . $blog->content );

		// Remove the audio and video codes
		$blog->intro	= EasyBlogHelper::getHelper( 'Audio' )->strip( $blog->intro );
		$blog->content	= EasyBlogHelper::getHelper( 'Audio' )->strip( $blog->content );

		$content 	= isset( $blog->$source ) && !empty( $blog->$source ) ? $blog->$source : $blog->intro;

		$this->set( 'audios'	, $audios );
		$this->set( 'date'		, $date );
		$this->set( 'permalink' , $url );
		$this->set( 'blog'		, $blog );
		$this->set( 'actor' 	, $item->actor );
		$this->set( 'content'	, $content );

		$catUrl = EasyBlogRouter::_( 'index.php?option=com_easyblog&view=categories&layout=listings&id=' . $blog->category_id, true, null, false, true );
		$this->set( 'categorypermalink'	, $catUrl );


		$item->title	= parent::display( 'streams/' . $item->verb . '.title' );
		$item->content	= parent::display( 'streams/' . $item->verb . '.content' );
	}

	private function prepareNewCommentStream( &$item )
	{
		$comment 	= EasyBlogHelper::getTable( 'Comment' );
		$comment->load( $item->contextId );

		// Format the likes for the stream
		$likes 			= Foundry::likes();
		$likes->get( $comment->id , 'blog.comments' );
		$item->likes	= $likes;

		//$url 			= EasyBlogRouter::_( 'index.php?option=com_easyblog&view=entry&id=' . $comment->post_id );
		$url 			= EasyBlogRouter::_( 'index.php?option=com_easyblog&view=entry&id=' . $comment->post_id, true, null, false, true );


		// Apply comments on the stream
		$comments			= Foundry::comments( $item->contextId , 'blogcomment' , SOCIAL_APPS_GROUP_USER , array( 'url' => $url ) );
		$item->comments 	= $comments;

		$blog 	= EasyBlogHelper::getTable( 'Blog' );
		$blog->load( $comment->post_id );

		$date 	= EasyBlogHelper::getDate( $blog->created );

		// Parse the bbcode from EasyBlog
		$comment->comment 	= EasyBlogHelper::getHelper( 'Comment' )->parseBBCode( $comment->comment );

		$this->set( 'comment'	, $comment );
		$this->set( 'date'		, $date );
		$this->set( 'permalink' , $url );
		$this->set( 'blog'	, $blog );
		$this->set( 'actor' , $item->actor );

		$item->title	= parent::display( 'streams/' . $item->verb . '.title' );
		$item->content	= parent::display( 'streams/' . $item->verb . '.content' );
	}

	/**
	 * Triggered before comments notify subscribers
	 *
	 * @since	1.0
	 * @access	public
	 * @param	SocialTableComments	The comment object
	 * @return
	 */
	public function onAfterCommentSave( &$comment )
	{
		if( $comment->element != 'blogpost.user' )
		{
			return;
		}

		$element 	= explode( '.' , $comment->element );
		$element 	= $element[ 0 ];

		if( $element == 'blogpost' )
		{
			if( $this->exists() === false )
			{
				return;
			}

			// When a comment is posted in the stream, we also want to move it to EasyBlog's comment table.
			$ebComment 	= EasyBlogHelper::getTable( 'Comment' );
			$ebComment->post_id 	= $comment->uid;
			$ebComment->comment 	= $comment->comment;
			$ebComment->created_by 	= $comment->created_by;
			$ebComment->created 	= $comment->created;
			$ebComment->modified 	= $comment->created;
			$ebComment->published	= true;

			// Save the comment
			$state 		= $ebComment->store();
		}

	}

	/**
	 * event onLiked on story
	 *
	 * @since	1.0
	 * @access	public
	 * @param	object	$params		A standard object with key / value binding.
	 *
	 * @return	none
	 */
	public function onAfterLikeSave( &$likes )
	{
		if( !$likes->type )
		{
			return;
		}

		// Set the default element.
		$element 	= $likes->type;
		$uid 		= $likes->uid;

		if( strpos( $element , '.' ) !== false )
		{
			$data		= explode( '.', $element );
			$group		= $data[1];
			$element	= $data[0];
		}

		if( $likes->type != 'blog.user' || $this->exists() === false )
		{
			return;
		}

		// Get the owner of the blog post
		$blog 			= EasyBlogHelper::getTable( 'Blog' );
		$blog->load( $likes->uid );

		$recipients 	= array( $blog->created_by );

		// Get the author of the item
		$poster 	= Foundry::user( $likes->created_by );

		$title 		= JText::sprintf( 'APP_BLOG_NOTIFICATIONS_LIKE_BLOG' , $blog->title );
		$permalink	= EasyBlogRouter::_( 'index.php?option=com_easyblog&view=entry&id=' . $blog->id , false );

		// Add new notification item
		Foundry::notify( 'blog.likes' , $recipients , false , array( 'title' => $title , 'context_type' => 'blog.likes' , 'url' => $permalink , 'actor_id' => $likes->created_by , 'uid' => $blog->id , 'aggregate' => false ) );
	}

	/**
	 * Prepares the activity log
	 *
	 * @since	1.0
	 * @access	public
	 * @param	SocialStreamItem	The stream object.
	 * @param	bool				Determines if we should respect the privacy
	 */
	public function onPrepareActivityLog( SocialStreamItem &$item, $includePrivacy = true )
	{
	}

	public function onPrivacyChange( $data )
	{

		if( !$data )
		{
			return;
		}

		if( $data->utype != 'blog' || !$data->uid )
			return;

		if( $this->exists() === false )
		{
			return;
		}


		$db 	= Foundry::db();
		$sql 	= $db->sql();

		$query = 'update `#__easyblog_post` set `private` = ' . $db->Quote( $data->value );
		$query .= ' where `id` = ' . $db->Quote( $data->uid );

		$sql->clear();
		$sql->raw( $query );
		$db->setQuery( $sql );
		$db->query();

		return true;
	}


}
