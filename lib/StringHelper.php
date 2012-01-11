<?php

/**
 * This class contains only static methods and should not be instantiated. These methods provide functionality necessary for core the Picora classes to operate.
 * @introduction Support functionality for the Picora core.
 */

final class StringHelper extends Helper {
	/**
	 * Replaces properties in a given string (denoted with a colon) with properties in a given array. This function is used to format the route strings in the PicoraDispatcher and to format the where conditions in PicoraActiveRecord.
	 * 
	 * <pre class="highlighted"><code class="php">print format_property_string('one :two three',array('two'=>2));
	 * //one 2 three</code></pre>
	 *
	 * If the properties argument is an object is instead of an array, it must implement ArrayAccess. If the property does not exist in that object a camel cased getter method is searched for to get the result instead. If that method exists, it will be called, and the result used.
	 * 
	 * <pre class="highlighted"><code class="php">class Test extends ArrayObject {
	 * 	public $third_proprerty = 3;
	 * 	public function getSecondProperty(){
	 * 		return 2;
	 * 	}
	 * }
	 * $t = new Test();
	 * print format_property_string('one :second_property :third_proprerty',$t);
	 * //one 2 3</code></pre>
	 *
	 * If properties can't be resolved they are left in the string with thier colons.
	 *
	 * Any property named "id" that is set to false will be replaced with the string "new".
	 * @param string $property_string
	 * @param mixed $properties
	 * @return string
	 */
	public function formatPropertyString($property_string,$properties){
		preg_match_all('/(?<!\\\\)(\:([^\/0-9][\w\_\-]*))/e',$property_string,$matches);
		foreach($matches[2] as $match){
			//if($match == 'id' && isset($properties['id']) && $properties['id'] === false)
			//	$property_string = str_replace(':id','new',$property_string);
			//elseif(isset($properties[$match]) && !is_null($properties[$match]))
			if(isset($properties[$match]) && !is_null($properties[$match]))
				$property_string = str_replace(':'.$match,$properties[$match],$property_string);
			elseif(is_object($properties) && method_exists($properties,'get'.str_replace(' ','',ucwords(str_replace('_',' ',$match)))))
				$property_string = str_replace(':'.$match,$properties->{'get'.str_replace(' ','',ucwords(str_replace('_',' ',$match)))}(),$property_string);
		}
		return $property_string;
	}
	
	/**
	 * @param string $str word to get the singular form of.
	 * @return string singular form of given word.
	 */
	 /*
	public function singularize($str){
		//Singularize rules from Rails::ActiveSupport::inflections.rb
		//Copyright (c) 2005 David Heinemeier Hansson
		$uncountable = array('equipment','information','rice','money','species','series','fish','sheep');
		if(in_array(strtolower($str),$uncountable))
			return $str;
		$irregulars = array(
			'people'=>'person',
			'men'=>'man',
			'children'=>'child',
			'sexes'=>'sex',
			'moves'=>'move'
		);
		if(in_array(strtolower($str),array_keys($irregulars)))
			return $irregulars[$str];
		foreach(array(
			'/(quiz)zes$/i'=>'\1',
			'/(matr)ices$/i'=>'\1ix',
			'/(vert|ind)ices$/i'=>'\1ex',
			'/^(ox)en/i'=>'\1',
			'/(alias|status)es$/i'=>'\1',
			'/([octop|vir])i$/i'=>'\1us',
			'/(cris|ax|test)es$/i'=>'\1is',
			'/(shoe)s$/i'=>'\1',
			'/(o)es$/i'=>'\1',
			'/(bus)es$/i'=>'\1',
			'/([m|l])ice$/i'=>'\1ouse',
			'/(x|ch|ss|sh)es$/i'=>'\1',
			'/(m)ovies$/i'=>'\1ovie',
			'/(s)eries$/i'=>'\1eries',
			'/([^aeiouy]|qu)ies$/i'=>'\1y',
			'/([lr])ves$/i'=>'\1f',
			'/(tive)s$/i'=>'\1',
			'/(hive)s$/i'=>'\1',
			'/([^f])ves$/i'=>'\1fe',
			'/(^analy)ses$/i'=>'\1sis',
			'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i'=>'\1\2sis',
			'/([ti])a$/i'=>'\1um',
			'/(n)ews$/i'=>'\1ews',
			'/s$/i'=>''
		) as $match => $replace)
			if(preg_match($match,$str))
				return preg_replace($match,$replace,$str);
		return $str;
	}
	*/
	
	/**
	 * @param string $str word to get the plural form of.
	 * @return string plural form of given word.
	 */		
	 /*
	static public function pluralize($str){
		//Singularize rules from Rails::ActiveSupport::inflections.rb
		//Copyright (c) 2005 David Heinemeier Hansson
		$uncountable = array('equipment','information','rice','money','species','series','fish','sheep');
		if(in_array(strtolower($str),$uncountable))
			return $str;
		$irregulars = array(
			'person'=>'people',
			'man'=>'men',
			'child'=>'children',
			'sex'=>'sexes',
			'move'=>'moves'
		);
		if(in_array(strtolower($str),array_keys($irregulars)))
			return $irregulars[$str];
		foreach(array(
			'/(quiz)$/i'=>'\1zes',
			'/^(ox)$/i'=>'\1en',
			'/([m|l])ouse$/i'=>'\1ice',
			'/(matr|vert|ind)ix|ex$/i'=>'\1ices',
			'/(x|ch|ss|sh)$/i'=>'\1es',
			'/([^aeiouy]|qu)ies$/i'=>'\1y',
			'/([^aeiouy]|qu)y$/i'=>'\1ies',
			'/(hive)$/i'=>'\1s',
			'/(?:([^f])fe|([lr])f)$/i'=>'\1\2ves',
			'/sis$/i'=>'ses',
			'/([ti])um$/i'=>'\1a',
			'/(buffal|tomat)o$/i'=>'\1oes',
			'/(bu)s$/i'=>'\1ses',
			'/(alias|status)$/i'=>'\1es',
			'/(octop|vir)us$/i'=>'\1i',
			'/(ax|test)is$/i'=>'\1es',
			'/s$/i'=>'s',
			'/$/'=> 's'
		) as $match => $replace)
			if(preg_match($match,$str))
				return preg_replace($match,$replace,$str);
		return $str;
	}
	*/

	/*
	public function IsValidEmail ($email)
	{
		return preg_match('/^[a-z0-9\-\._]+@[a-z0-9]([0-9a-z\-]*[a-z0-9]\.){1,}[a-z]{1,4}$/i', $email);       
	}
	*/
}

?>
