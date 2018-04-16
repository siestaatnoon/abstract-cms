<?php

namespace App\Html\ListPage;

use App\Exception\AppException;


/**
 * AdminListPage class
 * 
 * Generates the page template and module list data for the admin list pages and according to the
 * current admin logged-in user's permissions. Subclasses the ListPage class for use for the admin area.
 *
 * TODO: Remove HTML and put into helper functions
 * 
 * @author      Johnny Spence <info@projectabstractcms.org>
 * @copyright   2014 Johnny Spence
 * @link        http://www.projectabstractcms.org
 * @version     0.1.0
 * @package		App\Html\AdminListPage
 */
class AdminListPage extends \App\Html\ListPage\ListPage {
	
	/**
     * @var \App\User\Permission Object containing CMS user permission for page
     */	
	protected $permission;


    /**
     * __construct
     *
     * Initializes the AdminListPage.
     *
     * @access public
     * @param mixed $mixed The \App\Module\Module object or module name to load module
     * @param bool $is_archive True if list page for items marked as archived
     * @param @param \App\User\Permission $permission The current CMS user Permission object
     * @throws \App\Exception\AppException if an error occurs while loading module, handled by \App\App class
     */
	public function __construct($mixed, $is_archive, $permission) {
		if ($permission instanceof \App\User\Permission === false ) {
            $msg_part = error_str('error.param.type', array('$permission', '\\App\\User\\Permission') );
            $message = error_str('error.type.param.invalid', array($msg_part) );
			throw new AppException($message, AppException::ERROR_FATAL);
		}

		parent::__construct($mixed, $is_archive);
		$this->permission = $permission;
        $this->pagination['per_page'] = $this->App->config('admin_list_per_page');
	}


    /**
     * bulk_update_params
     *
     * Returns the module boolean values to activate/deactivate bulk update features within
     * the admin list pages.
     *
     * @access public
     * @return array Assoc array of boolean values
     */
	public function bulk_update_params() {
		$data = $this->module->get_module_data();
		return array(
			'use_active' 	=> empty($data['use_active']) ? false : $this->permission->has_update(),
			'use_archive' 	=> empty($data['use_archive']) ? false : $this->permission->has_update(),
			'use_delete' 	=> empty($data['use_delete']) ? false : $this->permission->has_delete(),
            'is_archive' 	=> $this->is_archive
		);
	}


    /**
     * template
     *
     * Returns the list page template HTML and associated data blocks.
     *
     * @access public
     * @return array Assoc array of template data
     */
	public function template() {
		$data = $this->module->get_module_data();
		$block = array(
			array(
				'selector' 	=> $this->App->config('page_content_id'),
				'pos_func' 	=> 'appendTo',
				'html' 		=> $this->selector_popup()
			)
		);

		return array(
			'pk_field' 	=> $data['pk_field'],
			'blocks' 	=> $block,
			'template'	=> $this->template_html()
		);
	}


    /**
     * selector_popup
     *
     * Returns popup selector HTML with options to view/edit and/or delete.
     *
     * @access protected
     * @return string The popup selector HTML
     */
	protected function selector_popup() {
		$data = $this->module->get_module_data();
		$module_name = $data['name'];
        $title_field = $data['title_field'];
		$edit_text = $this->permission->has_update() ? 'Edit' : 'View';
		
		$html = <<<HTML
<div data-role="popup" id="list-action-popup" class="ui-corner-all ui-popup ui-body-a ui-overlay-shadow" data-title-field="{$title_field}">
  <a href="#" data-rel="back" data-role="button" data-icon="delete" data-iconpos="notext" class="ui-btn-right">Close</a>
  <div data-role="header" class="ui-corner-top ui-header ui-bar-a">
    <h3>Select an Action</h3>
  </div>
  <div role="main" class="ui-content">
    <div class="model-title"></div>
    <div>
      <a href="#" data-fragment="admin/{$module_name}/edit" data-transition="fade" class="list-action-edit ui-btn ui-corner-all ui-shadow ui-mini ui-icon-edit ui-btn-icon-right">{$edit_text}</a>
    </div>

HTML;
		if ( $this->permission->has_delete() ) {
			$html .= <<<HTML
    <div>
      <a href="#" data-transition="fade" class="list-action-delete ui-btn ui-corner-all ui-shadow ui-mini ui-icon-delete ui-btn-icon-right">Delete</a>
    </div>

HTML;
		}
		
		$html .= <<<HTML
  </div>
</div>
		
HTML;
		
		return $html;
	}


    /**
     * template_html
     *
     * Returns the admin list page tenplate HTML.
     *
     * @access protected
     * @return string The list page HTML
     */
	protected function template_html() {
		$data = $this->module->get_module_data();
		$title = $data['label_plural'];
		$module_name = $data['name'];
        $archive = $this->is_archive ? '' : '/archive';
        $archive_text = $this->is_archive ? 'Return to List' : 'View Archived';
		$class = array('module-filter-field', 'filter-select');
        if ($this->is_archive) {
            $title .= ' <span class="header-archived">[ Archived ]</span>';
        }
		
		$html = <<<HTML
<div id="tpl-list-view" class="tpl-list-{$module_name}">

HTML;
        if ( ! empty($data['use_archive']) ) {
            $html .= <<<HTML
  <a href="admin/{$module_name}/list{$archive}" class="module-view-archive module-top-button btn btn-primary ui-btn ui-icon-bullets ui-btn-icon-right ui-corner-all ui-mini">{$archive_text}</a>

HTML;
        }

		if ( $this->permission->has_add() ) {
			$html .= <<<HTML
  <a href="admin/{$module_name}/add" class="module-add-new module-top-button btn btn-primary ui-btn ui-icon-plus ui-btn-icon-right ui-corner-all ui-mini">Add New Item</a>

HTML;
		}
		
		$html .= <<<HTML
  <h1>{$title}</h1>
  <div id="{$module_name}-filter" class="module-filter ui-corner-all ui-shadow ui-mini">
    <a href="#" class="module-filter-clear btn btn-primary ui-btn ui-icon-delete ui-btn-icon-right ui-corner-all ui-mini">Clear Filters &nbsp;</a>
    <h3>Filter Results</h3>
    <div class="module-filter-group">
      <div class="module-filter-item">
        <input type="text" name="search" class="module-filter-field filter-input" placeholder="Search..." />
      </div>

HTML;
		foreach ($this->fields as $field) {
			 if ( $field->get_data('name') === self::$ACTIVE_FIELD ) {
				continue;
			}
			if ( $field->is_select_filter() ) {
				$html .= '<div class="module-filter-item">'."\n";
				$html .= $field->filter($class);
				$html .= '</div>'."\n";
			}
		}
		
		//if module uses "active" field, then last filter
		if ($data['use_active']) {
			$html .= '<div class="module-filter-item">'."\n";
			$html .= '<select name="is_active" class="'.implode(' ', $class).'">'."\n";
			$html .= '  <option value="">Active?</option>'."\n";
			$html .= '  <option value="1">Active: Yes</option>'."\n";
			$html .= '  <option value="0">Active: No</option>'."\n";
			$html .= '</select>'."\n";
			$html .= '</div>'."\n";
		}

		$html .= <<<HTML
      <div class="module-filter-button">
        <button id="module-filter-submit" name="submit-filter" class="btn btn-primary" disabled="disabled">Filter</button>
      </div>
    </div><!--close module-filter-->
  </div>
  <div id="{$module_name}-list" class="module-list"></div><!--close module-list-->
</div><!--close #tpl-list-view-->

HTML;
		
		return $html;
	}
}

/* End of file AdminListPage.php */
/* Location: ./App/Html/ListPage/AdminListPage.php */