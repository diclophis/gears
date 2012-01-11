<?php

/*
	Connects Smarty to a Controller or Mailer instance
*/

class HtmlView extends View {

	private $main_template = null;
	private $content_template = null;
	
	function __construct ($rendering_for_email = false)
	{
		$this->smarty = new smarty;
		$this->smarty->template_dir = $_SERVER['COMP_ROOT']."/app/views/";
		$this->smarty->compile_dir = "/tmp/templates_c/";
		$this->smarty->rendering_for_email = $rendering_for_email;

		foreach(array('UrlHelper', 'StringHelper', 'TagHelper') as $helper_class) {
			$this->register_helper_class($helper_class);
		}

	}

	public function register_helper_class ($helper_class)
	{
		$helper = new $helper_class;
		$class_reflection = new ReflectionClass($helper);
		$methods = $class_reflection->getMethods();
		foreach ($methods as $method) {
			$number_of_parameters = $method->getNumberOfParameters();
			$number_of_required_parameters = $method->getNumberOfRequiredParameters();
			$public = $method->isPublic();
			if ($public && $number_of_parameters == 4) {
				$this->smarty->register_block($method->name, array($helper, $method->name));
			} elseif($public && $number_of_parameters == 2 && $number_of_required_parameters == 2) {
				$this->smarty->register_function($method->name, array($helper, $method->name));
			} else {
				$this->smarty->register_modifier($method->name, array($helper, $method->name));
			}
		}
		return $helper;
	}

	public function display ($main_template, $content_template, $assignments)
	{
		if ($this->smarty->rendering_for_email) {
			$base_url = Config::settings()->gears['base_url'];
			$http_host = Config::settings()->gears['web_host'];			
			$images_root = sprintf("http://%s%s", $http_host, $base_url);
			$this->smarty->assign("images_root", $images_root);
		}

		foreach ($assignments as $key => $value) {
			$this->smarty->assign($key, $value);
		}

    if ($this->smarty->template_exists(sprintf("%s.tpl", $content_template))) {
      $this->smarty->assign("content", $this->smarty->fetch(sprintf("%s.tpl", $content_template)));
    } else {
      $this->smarty->assign("content", "<!-- this space intentionally left blank -->");
    }

		$result = $this->smarty->fetch(sprintf("%s.tpl", $main_template));

		return $result;

	}

	public function clear_all_assign ()
	{
		return $this->smarty->clear_all_assign();
	}
}

?>
