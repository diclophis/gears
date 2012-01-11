<?php

class MacroEngine {

	private $container = null;

	public function __construct ()
	{
		/*
		$this->container = bbcode_create();
		$this->set_flag(BBCODE_DEFAULT_SMILEYS_ON);

		//base tag, which allows for simple smiley processing
		$this->add_tag('', array('type' => BBCODE_TYPE_ROOT));

		//setup some basic tags
		$this->add_tag('b', array('type' => BBCODE_TYPE_NOARG, 'open_tag' => '<b>', 'close_tag' => '</b>'));
		$this->add_tag('i', array('type' => BBCODE_TYPE_NOARG, 'open_tag' => '<i>', 'close_tag' => '</i>'));
		$this->add_tag('bold', array('type' => BBCODE_TYPE_NOARG, 'open_tag' => "<b>", 'close_tag' => "</b>"));
		$this->add_tag('italic', array('type' => BBCODE_TYPE_NOARG, 'open_tag' => '<i>', 'close_tag' => '</i>'));
		foreach(array('red', 'green', 'blue') as $color) {
			$this->add_tag($color, array('type' => BBCODE_TYPE_NOARG, 'open_tag' => sprintf("<span style=\"color: %s\">", $color), 'close_tag' => sprintf("</span>")));
		}

		//set codes to be case insensitive
		$this->set_flag(BBCODE_SMILEYS_CASE_INSENSITIVE, BBCODE_SET_FLAGS_ADD);
		*/
		$this->searches = array();
		$this->replaces = array();
	}

	/*
	 keys for $rules array()
    * flags optional - a flag set based on the BBCODE_FLAGS_* constants.
    * type required - an int indicating the type of tag. Use the BBCODE_TYPE_* constants.
    * open_tag required - the HTML replacement string for the open tag.
    * close_tag required - the HTML replacement string for the close tag.
    * default_arg optional - use this value as the default argument if none is provided and tag_type is of type OPTARG.
    * content_handling optional - Gives the callback used for modification of the content. Object Oriented Notation supported only since 0.10.1 callback prototype is string name(string $content, string $argument)
    * param_handling optional - Gives the callback used for modification of the argument. Object Oriented Notation supported only since 0.10.1 callback prototype is string name(string $content, string $argument)
    * childs optional - List of accepted child for the tag. The format of the list is a comma separated string. If the list starts with ! it will be the list of rejected child for the tag.
    * parent optional - List of accepted child for the tag. The format of the list is a comma separated string.
	*/
	public function add_tag ($name, $rules)
	{
		//return bbcode_add_element($this->container, $name, $rules);
	}

	public function add_code ($key, $value)
	{
		/*
		return bbcode_add_smiley($this->container, sprintf("{%s}", $key), $value);
		*/
    $this->searches[] = sprintf("{%s}", $key);
    $this->replaces[] = $value;
	}

	public function parse ($text)
	{
	  return nl2br(str_ireplace($this->searches, $this->replaces, $text));
	}

	public function set_flag ($flag, $mode = BBCODE_SET_FLAGS_SET)
	{
		//return bbcode_set_flags($this->container, $flag, $mode); 
	}

	public function import_model ($model)
	{
		foreach ($model->macro_codes() as $code) {
			$this->add_code($code, $model->macro_value_for($code));
		}
	}

	public static function parse_macros_in_php($string_with_macros, $models)
	{
		$macro_engine = new MacroEngine(); 
		foreach($models as $model)
		{
			$macro_engine->import_model($model); 
		}
		return $macro_engine->parse($string_with_macros);
	}
}

/*

FLAGS for tags (added with add_tag)

	BBCODE_TYPE_NOARG  (integer)
		 This BBCode tag does not accept any arguments. 
	BBCODE_TYPE_SINGLE (integer)
		 This BBCode tag does not have a corresponding close tag. 
	BBCODE_TYPE_ARG (integer)
		 This BBCode tag need an argument. 
	BBCODE_TYPE_OPTARG (integer)
		 This BBCode tag accept an optional argument. 
	BBCODE_TYPE_ROOT (integer)
		 This BBCode tag is the special tag root (nesting level 0). 

	BBCODE_FLAGS_ARG_PARSING (integer)
		 This BBCode tag require argument sub-parsing (the argument is also parsed by the BBCode extension). As Of 0.10.2 another parser can be used as argument parser. 
	BBCODE_FLAGS_CDATA_NOT_ALLOWED (integer)
		 This BBCode Tag does not accept content (it voids it automatically). 
	BBCODE_FLAGS_SMILEYS_ON (integer) - since 0.10.2
		 This BBCode Tag accepts smileys. 
	BBCODE_FLAGS_SMILEYS_OFF (integer) - since 0.10.2
		 This BBCode Tag does not accept smileys. 
	BBCODE_FLAGS_ONE_OPEN_PER_LEVEL (integer) - since 0.10.2
		 This BBCode Tag automatically closes if another tag of the same type is found at the same nesting level. 
	BBCODE_FLAGS_REMOVE_IF_EMPTY (integer) - since 0.10.2
		 This BBCode Tag is automatically removed if content is empty it allows to produce ligther HTML. 
	BBCODE_FLAGS_DENY_REOPEN_CHILD (integer) - since 0.10.3
		 This BBCode Tag does not allow unclosed childs to reopen when automatically closed. 
	BBCODE_ARG_DOUBLE_QUOTE (integer) - since 0.10.2
    This is a parser option allowing argument quoting with double quotes (") 

FLAGS for container (set in constructor)

	BBCODE_ARG_SINGLE_QUOTE (integer) - since 0.10.2
		 This is a parser option allowing argument quoting with single quotes (') 
	BBCODE_ARG_HTML_QUOTE (integer) - since 0.10.2
		 This is a parser option allowing argument quoting with HTML version of double quotes (&quot;) 
	BBCODE_AUTO_CORRECT (integer) - since 0.10.2
		 This is a parser option changing the way errors are treated. It automatically closes tag in the order they are opened. And treat tags with only an open tag as if there were a close tag present. 
	BBCODE_CORRECT_REOPEN_TAGS (integer) - since 0.10.2
		 This is a parser option changing the way errors are treated. It automatically reopens tag if close tags are not in the good order. 
	BBCODE_DISABLE_TREE_BUILD (integer) - since 0.10.2
		 This is a parser option disabling the BBCode parsing it can be useful if only the "smiley" replacement must be used. 
	BBCODE_DEFAULT_SMILEYS_ON (integer) - since 0.10.2
		 This is a parser option setting smileys to ON if no flag is given at tag level. 
	BBCODE_DEFAULT_SMILEYS_OFF (integer) - since 0.10.2
		 This is a parser option setting smileys to OFF if no flag is given at tag level. 
	BBCODE_FORCE_SMILEYS_OFF (integer) - since 0.10.2
		 This is a parser option disabling completely the smileys parsing. 
	BBCODE_SMILEYS_CASE_INSENSITIVE (integer) - since 0.10.3
		 Use a case insensitive Detection for smileys instead of a simple binary search. 

FLAGS for setting other flags (wtf?)

	BBCODE_SET_FLAGS_SET (integer) - since 0.10.2
		 This permits to SET the complete flag set on a parser. 
	BBCODE_SET_FLAGS_ADD (integer) - since 0.10.2
		 This permits to switch a flag set ON on a parser. 
	BBCODE_SET_FLAGS_REMOVE (integer) - since 0.10.2
		 This permits to switch a flag set OFF on a parser.
*/

?>
