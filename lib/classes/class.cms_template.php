<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
#CMS - CMS Made Simple
#(c)2004-2008 by Ted Kulp (ted@cmsmadesimple.org)
#This project's homepage is: http://cmsmadesimple.org
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#BUT withOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#$Id$

/**
 * Represents a template in the database.
 *
 * @author Ted Kulp
 * @since 2.0
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license GPL
 **/
class CmsTemplate extends CmsObjectRelationalMapping
{	
	var $params = array('id' => -1, 'name' => '', 'content' => '', 'stylesheet' => '', 'encoding' => '', 'active' => true, 'default' => false);
	var $field_maps = array('template_name' => 'name', 'default_template' => 'default', 'template_content' => 'content', 'template_id' => 'id');
	var $table = 'templates';
	var $id_field = 'template_id';
	//var $sequence = 'templates_seq';
	
	public $blocks = array();
	
	public function setup($first_time = false)
	{
		$this->create_has_many_association('stylesheet_associations', 'template_stylesheet_association', 'template_id', array('order' => 'order_num ASC'));
		$this->create_has_and_belongs_to_many_association('stylesheets', 'stylesheet', 'stylesheet_template_assoc', 'stylesheet_id', 'template_id', array('order' => 'order_num ASC'));
		$this->create_has_and_belongs_to_many_association('active_stylesheets', 'stylesheet', 'stylesheet_template_assoc', 'stylesheet_id', 'template_id', array('order' => 'order_num ASC', 'conditions' => 'stylesheets.active = 1'));
	}
	
	function process()
	{
		$smarty = cms_smarty();
		return $smarty->fetch('template:' . $this->id);
	}
	
	function usage_count()
	{
		$templateops = cmsms()->GetTemplateOperations();
		if ($this->id > -1)
			return $templateops->UsageCount($this->id);
		else
			return 0;
	}
	
	function UsageCount()
	{
		return $this->usage_count();
	}
	
	/**
	 * Gets a list of content blocks in the template.  It does this by
	 * temporarily overriding the definition of the content smarty plugin,
	 * having smarty parse it and callback to parse_block_callback.  It then
	 * sets the plugin definition back to what it was before.
	 *
	 * @return array
	 * @author Ted Kulp
	 */
	public function get_page_blocks()
	{
		$smarty = cms_smarty();
		
		$this->blocks = array();
		
		$old_function = $smarty->_plugins['function']['content'];
		$smarty->register_function('content', array($this, 'parse_block_callback'));
		
		$smarty->_compile_source('temporary template', $this->content, $_compiled);
		@ob_start();
		$smarty->_eval('?>' . $_compiled);
		$_contents = @ob_get_contents();
		@ob_end_clean();
		
		$smarty->unregister_function('content');
		$smarty->_plugins['function']['content'] = $old_function;
		
		return $this->blocks;
	}
	
	public function parse_block_callback($params, &$smarty)
	{
		$name = 'default';
		
		if (isset($params['name']))
		{
			$name = $params['name'];
			unset($params['name']);
		}
		else if (isset($params['block']))
		{
			$name = $params['block'];
			unset($params['block']);
		}
		
		if (!isset($params['type']))
		{
			$params['type'] = 'CmsHtmlContentType';
		}
		
		$this->blocks[$name] = $params;
	}
	
	function validate()
	{
		$this->validate_not_blank('name', lang('nofieldgiven',array(lang('title'))));
		$this->validate_not_blank('content', lang('nofieldgiven',array(lang('content'))));
		if ($this->name != '')
		{
			$result = cms_orm()->template->find_all_by_name($this->name);
			if (count($result) > 0)
			{
				if ($result[0]->id != $this->id)
				{
					$this->add_validation_error(lang('templateexists'));
				}
			}
		}
	}
	
	public function get_stylesheet_media_types($show_inactive = false)
	{
		$result = array();
		
		foreach ($this->active_stylesheets as $stylesheet)
		{
			foreach ($stylesheet->get_media_types_as_array() as $media_type)
			{
				if (!in_array($media_type, $result))
					$result[] = $media_type;
			}
		}

		return $result;
	}
	
	public function assign_stylesheet_by_id($stylesheet_id)
	{
		$exists = false;
		$cur_stylesheets = $this->stylesheets;
		foreach ($cur_stylesheets as $one_stylesheet)
		{
			if ($one_stylesheet->stylesheet_id == $stylesheet_id)
			{
				$exists = true;
				break;
			}
		}
		
		if (!$exists)
		{
			$new_assoc = new CmsTemplateStylesheetAssociation;
			$new_assoc->template_id = $this->id;
			$new_assoc->stylesheet_id = $stylesheet_id;
			$new_assoc->save();
		}
	}
	
	public function remove_assigned_stylesheet_by_id($stylesheet_id)
	{
		$cur_stylesheets = $this->stylesheet_associations;
		foreach ($cur_stylesheets as $one_stylesheet)
		{
			if ($one_stylesheet->stylesheet_id == $stylesheet_id)
			{
				$one_stylesheet->delete();
				break;
			}
		}
	}
	
	//Callback handlers
	function before_save()
	{
		//Make sure we have a default template set or we'll probably break stuff down the road
		if (cms_orm()->cms_template->find_count_by_default_template(1) == 0)
		{
			$this->default_template = 1;
		}
		CmsEvents::send_event( 'Core', ($this->id == -1 ? 'AddTemplatePre' : 'EditTemplatePre'), array('template' => &$this));
	}
	
	function after_save(&$result)
	{
		CmsEvents::send_event( 'Core', ($this->create_date == $this->modified_date ? 'AddTemplatePost' : 'EditTemplatePost'), array('template' => &$this));
		CmsCache::clear();
		CmsContentOperations::clear_cache();
	}
	
	function before_delete()
	{
		CmsEvents::send_event('Core', 'DeleteTemplatePre', array('template' => &$this));
	}
	
	function after_delete()
	{
		CmsEvents::send_event('Core', 'DeleteTemplatePost', array('template' => &$this));
		CmsCache::clear();
		CmsContentOperations::clear_cache();
	}
}

/*
class Template extends CmsTemplate {}
*/

# vim:ts=4 sw=4 noet
?>