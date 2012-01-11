<?php

/*
	These are used to provide shortcuts for developing the templates
*/

class TagHelper extends Helper {
	/** 
		* Build an image tag for the specified image on the current base_url
		* @param string image file name
		* @param string optional value for image width
		* @param string optional value for image height
		* @param string optional text for the alt attribute
		* @returns string fully formed <img src="BASEURL/image_name" alt=$alt_text> tag
	 */

	public function image_tag ( $params, &$smarty) 
	{
		if (!array_key_exists('src', $params)) {
			throw new GearsException("Error! No image filename!");
		}

		$tag = "<img ";
		foreach ($params as $attr => $value)
		{
			if ($attr == 'src')
			{
				$base_url = Config::settings()->gears['base_url'];
				$http_host = Config::settings()->gears['web_host'];			
				$tag .= sprintf("src=\"http://%s%s/images/%s\"", $http_host, $base_url, $value);
			}
			else
			{
				$tag .= "$attr='$value' ";
			}
		}

		return $tag . "/>";
	}

	public function style_sheet_tag($params, &$smarty)
	{
		if(!array_key_exists('href', $params)) {
			throw new GearsException("href attribute is required");
		}

		if (!array_key_exists('media', $params)) {
			$params['media'] = "all";
		}

		$tag = "<link rel=\"stylesheet\" type=\"text/css\"";

		foreach ($params as $key => $value) {
			switch ($key) {
				case 'href':
					$tag .= sprintf(" href=\"%s%s/%s\"", Config::settings()->gears['base_url'], "style_sheets", $value);
				break;
				case 'media':
					$tag .= sprintf(" media=\"%s\"", $value);
				break;
				default:
					$tag .= sprintf("%s=\"%s\"", $key, $value);
			}
		}

		$tag .= " />";
		return $tag;
	}


	public function javascript_tag($params, &$smarty)
	{
		if(!array_key_exists('src', $params)) {
			throw new GearsException("src attribute is required");
		}

		$tag = "<script type=\"text/javascript\"";

		foreach ($params as $key => $value) {
			switch ($key) {
				case 'src':
					$tag .= sprintf(" src=\"%s%s/%s\"", Config::settings()->gears['base_url'], "client_scripts", $value);
				break;
				default:
					$tag .= sprintf("%s=\"%s\"", $key, $value);
				break;
			}
		}

		$tag .= "></script>";

		return $tag;
	}

	public function form_tag ($params, $content, &$smarty, &$repeat)
	{
		if (isset($content)) {
			if (isset($params['for'])) {
				unset($params['for']);
			}

			if (isset($params['method'])) {
				$method = $params['method'];
				unset($params['method']);
			} else {
				$method = "post";
			}

			/*
			if (isset($params['target'])) {
				$target = $params['target'];
				unset($params['target']);
			} else {
				$target = "_self";
			}
			*/

			if (isset($params['class'])) {
				$class = $params['class'];
				unset($params['class']);
			} else {
				if (isset($this->current_fieldset_action)) {
					$class = "mux";
				} else {
					$class = "admin";
				}
			}

			/*
			if (isset($params['name'])) {
				$name = $params['name'];
				unset($params['name']);
			} else {
				if (isset($this->current_fieldset_action)) {
					$name = sprintf("form_for_%s", $this->current_fieldset_action);
				} else {
					$name = "generic";
				}
			}
			*/

			if (isset($params['id'])) {
				$id = $params['id'];
				unset($params['id']);
			} else {
				if (isset($this->current_fieldset_action)) {
					$id = $this->current_fieldset_action;
				} else {
					$id = "form_".time();
				}
			}

			//if (isset($this->current_fieldset_action)) {
			//	$anchor = sprintf("#%s", $this->current_fieldset_action);
			//} else {
				$anchor = "";
			//}

			unset($params["name"]);

			$url = Dispatcher::get_url($params);
			//$tag = sprintf("<form enctype=\"multipart/form-data\" action=\"%s%s\" method=\"%s\" target=\"%s\" class=\"%s\" id=\"%s\" name=\"%s\">%s</form>", $url, $anchor, $method, $target, $class, $id, $name, $content);
			$tag = sprintf("<form enctype=\"multipart/form-data\" action=\"%s%s\" method=\"%s\" class=\"%s\" id=\"%s\">%s</form>", $url, $anchor, $method, $class, $id, $content);
			return $tag;
		} else {
			if (isset($params['for'])) {
				$this->model = $params['for'];
			} else {
				$this->model = null;
			}
		}
	}

	public function fieldset_tag ($params, $content, &$smarty, &$repeat)
	{
		if (isset($content)) {
			if ($this->current_fieldset_legend) { // if there is a legend
				$tag = sprintf("%s<div class='fieldset'><fieldset><legend><span>%s</span></legend>%s</fieldset></div>", $this->current_fieldset_anchor, $this->current_fieldset_legend, $content);
			} else { // there is no legend
				$tag = sprintf("%s<div class='fieldset nolegend'><fieldset>%s</fieldset></div>", $this->current_fieldset_anchor, $content);
			}
			return $tag;
		} else {
			if (isset($params['legend'])) {
				$this->current_fieldset_legend = $params['legend'];
				//unset($params['legend']);
				if (isset($params['controller']) && isset($params['action'])) {
					$this->current_fieldset_action = $params['action'];
					unset($params['legend']);
					$url = Dispatcher::get_url($params);
					//$this->current_fieldset_legend = sprintf("<a class=\"edit\" id=\"edit_for_%2\$s\" href=\"%s#%s\">%s</a>", $url, $this->current_fieldset_action, $this->current_fieldset_legend);
					$this->current_fieldset_legend = sprintf("<a class=\"edit\" href=\"%s\">%s</a>", $url, $this->current_fieldset_legend);
				}
				$this->current_fieldset_anchor = "";
			} else {
				// in the case where there is only one fieldset, we want the option to not have a legend,
				// and to instead use the page header.
				$this->current_fieldset_anchor = "";
				$this->current_fieldset_legend = "";
				//throw new GearsException("fieldset_tag requires a :legend attribute");
			}
		}
	}

	public function inputs ($params, &$smarty)
	{
		if (isset($params['for']) && $params['for'] instanceof ErrorAccess) {
			$this->model = $params['for'];
			$smarty->assign("model", $this->model);
			if (isset($params['errors']) && $params['errors'] == true) {
				return $smarty->fetch("shared/errors.tpl");
			}
		} else {
			throw new GearsException("you must specify a :for attribute, and it must be an instance of Model");
		}
	}

	function input_tag ($params, &$smarty)
	{
		$string = '';
		$value_for_checkbox = null;
		
		foreach($params as $key => $value) {
			switch ($key) {
				case 'for':
					if ($this->model) {
						$value_key = $value;
						if (isset($params['id'])) {
							if (($params['id'] == "")) {
								$string .= sprintf(" id=\"%s\" ", $value);
							}
						} else {
							$string .= sprintf(" id=\"%s_input\" ", $value);
						}
						$string .= sprintf(' name="%s" ', $value);
						if ($this->model->is_error($value)) {
							if (isset($params["class"])) {
								$passthru_class = $params["class"];
							} else {
								$passthru_class = "";
							}
							$string .= sprintf(" class=\"fieldWithError %s\"", $passthru_class);
						}
						$use_model_value = (!isset($params['value']));
						if ($use_model_value) {
							$string .= sprintf(' value="%s"', htmlspecialchars($this->model->$value));
						}
					}
				break;

				case 'type':
					switch ($value) {
						case 'checkbox':
							if ($this->model) {
								if (isset($params['value']) && ($params['value'] == $this->model->$value_key)) {
									$string .= " checked=\"checked\"";
								}
							}
						break;
					}
					
					$string .= ' ' . $key . '="'. $value . '"';
				break;

				default:
					if (strlen($value) > 0) {
						$string .= ' ' . $key . '="'. $value . '"';
					}
				break;
			}
		}
		
		$html = '<input' . $string . ' />';
		
		return $html;
	}

	function text_area_tag ($params, &$smarty)
	{
		$string = '';
		$value_value = '';
		
		foreach($params as $key => $value) {
			switch ($key) {
				case 'for':
					if ($this->model) {
						$value_key = $value;
						$string .= sprintf(' name="%s"', $value);
						if ($this->model->is_error($value)) {
							$string .= " class=\"fieldWithError\"";
						}
						$value_value = $this->model->$value_key;
					}
				break;

				case 'value':
					$value_value = $value;
				break;

				default:
					$string .= ' ' . $key . '="'. $value . '"';
				break;
			}
		}
		
		$html = sprintf("<textarea rows=\"10\" cols=\"20\" %s>%s</textarea>", $string, htmlspecialchars($value_value));
		
		return $html;
	}

	function submit_tag ($params, &$smarty)
	{
		if (isset($params['src'])) {
			$src = $params['src'];
			unset($params['src']);
		} else {
			$src = "/images/admin/btn_save.gif";
		}
		return $this->input_tag(array_merge($params, array('type' => 'image', 'src' => $src)), $smarty);
	}

	function cancel_tag ($params, &$smarty)
	{
		$return = "";
		if (isset($params['controller']) && isset($params['action'])) {
			if (isset($params['src'])) {
				$src = $params['src'];
				unset($params['src']);
			} else {
				$src = "/images/admin/btn_cancel.gif";
			}
			$href = Dispatcher::get_url($params);
			$return = sprintf("<a class=\"cancel\" href=\"%s\"><img src=\"%s\" alt=\"Cancel\"/></a>", $href, $src);
			return $return;
		} else {
			throw new GearsException("Cancel tag requires url_for parameters");
		}
	}

	function file_field_tag ($params, &$smarty)
	{
		return $this->input_tag(array_merge($params, array('type' => 'file')), $smarty);
	}

	function hidden_field_tag ($params, &$smarty)
	{
		return $this->input_tag(array_merge($params, array('type' => 'hidden')), $smarty);
	}

	function session_id_tag ($params, &$smarty)
	{
		return $this->hidden_field_tag(array_merge($params, array('value' => Session::id(), 'name' => Session::name())), $smarty);
	}

	function password_field_tag ($params, &$smarty)
	{
		return $this->input_tag(array_merge($params, array('type' => 'password')), $smarty);
	}

	function text_field_tag ($params, &$smarty)
	{
		return $this->input_tag(array_merge($params, array('type' => 'text')), $smarty);
	}

	function checkbox_field_tag ($params, &$smarty)
	{ 
		$return = "";
		$params_for_hidden = array_merge($params, array('type' => 'checkbox', 'value' => '0'));	
		$params_for_hidden['id'] = "";
		$params_for_checkbox = array_merge($params, array('type' => 'checkbox', 'value' => '1'));	
		//$params_for_checkbox['id'] = true;
		$return .= $this->hidden_field_tag($params_for_hidden, $smarty);
		$return .= $this->input_tag($params_for_checkbox, $smarty);
		return $return;
	}

	public function radio_field_set ($params, $content, &$smarty, &$repeat)
	{
		if (isset($content)) {
			return $content;
		} else {
			$this->radio_field_set_name = $for = $params["for"];
			$this->radio_field_set_value = $this->model->$for;
		}
	}

	function radio_field_tag ($params, &$smarty)
	{
		$params["name"] = $this->radio_field_set_name;
		if ($params["value"] == $this->radio_field_set_value) {
			$params["checked"] = "checked";
		}
		return $this->input_tag(array_merge($params, array('type' => 'radio')), $smarty);
	}

	public function random_things ($params, $content, &$smarty, &$repeat)
	{
		if (isset($content)) {
			return $this->random_things[rand(1, count($this->random_things)) - 1];
		} else {
			$this->random_things = array();
		}
	}

	public function random_thing ($params, $content, &$smarty, &$repeat)
	{
		if (isset($content)) {
			$this->random_things[] = $content;
		} else {
		}
	}

	public function box_tag ($params, $content, &$smarty, &$repeat)
	{
		$format = '<div class="%1$s_container" %2$s><div class="%1$s_nw"><div class="%1$s_ne">%3$s</div></div><div class="%1$s_w"><div class="%1$s_content">%4$s</div></div><div class="%1$s_sw"><div class="%1$s_se"></div></div></div>';
		if (isset($content)) {
			$tag = sprintf($format, $this->current_box_prefix, $this->current_box_id, $this->current_box_title, $content);
			return $tag;
		} else {
			if (isset($params['title'])) {
				$this->current_box_title = $params['title'];
				if (isset($params['prefix'])) {
					$this->current_box_prefix = $params['prefix'];
				} else {
					$this->current_box_prefix = "box";
				}
				if (isset($params['id'])) {
					$this->current_box_id = sprintf(' id="%s"', $params['id']);
				} else {
					$this->current_box_id = "";
				}
			} else {
				throw new GearsException("box_tag requires a title attribute");
			}
		}
	}
	
	/**
	* -------------------------------------------------------------
	* Name:     text_editor
	* Purpose:  Creates a FCKeditor, a very powerful textarea replacement.
	* -------------------------------------------------------------
	* @param id -the name of the form field
	* @param base_path -optional Path to the FCKeditor directory. Need only be set once on page. Default: /FCKeditor/
	* @param value -optional data that control will start with, default is taken from the javascript file
	* @param width -optional width (css units)
	* @param height -optional height (css units)
	* @param toolbar_set -optional what toolbar to use from configuration
	* @param browser_check -optional check the browser compatibility when rendering the editor
	* @param display_errors -optional show error messages on errors while rendering the editor
	*
	* Default values for optional parameters (except BasePath) are taken from fckeditor.js.
	* All other parameters used in the function will be put into the configuration section,
	* CustomConfigurationsPath is useful for example.
	* See http://wiki.fckeditor.net/Developer%27s_Guide/Configuration/Configurations_File for more configuration info.
	*/
	function text_editor($params, &$smarty)
	{
		if(!isset($params['id']) || empty($params['id']))
		{
		  $smarty->trigger_error('text_editor: required parameter "id" missing');
		}
		// create an instance of the text editor object
		$oFCKeditor = new FCKeditor($params['id']);
		
		
		// base file path can be specified manually, otherwise it defaults to the root
		if(isset($params['base_path']))
		{
			$oFCKeditor->BasePath = $params['base_path'];
		}
		else
		{
			$oFCKeditor->BasePath = '/fckeditor/';
		}
		
		// Starting value of the text editor can be specified as a param??
		if(isset($params['value']))
			$oFCKeditor->Value = $params['value'];
		else
			$oFCKeditor->Value = '';
		
		
		if(isset($params['width'])) $oFCKeditor->Width = $params['width'];
		if(isset($params['height'])) $oFCKeditor->Height = $params['height'];
		if(isset($params['toolbar_set'])) $oFCKeditor->ToolbarSet = $params['toolbar_set'];
		if(isset($params['browser_check'])) $oFCKeditor->CheckBrowser = $params['browser_check'];
		if(isset($params['display_errors'])) $oFCKeditor->DisplayErrors = $params['display_errors'];
		
		return $oFCKeditor->Create();
	}
	
	
}

?>
