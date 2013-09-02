<?php
/**
* Hull Utilities
*/
class Hull_Utils {
  
/**
   * Translates a camel case string into a string with underscores (e.g. firstName -&gt; first_name)
   * @param    string   $str    String in camel case format
   * @return    string            $str Translated into underscore format
   */
  public static function underscore($str) {
    $str[0] = strtolower($str[0]);
    $func = create_function('$c', 'return "_" . strtolower($c[1]);');
    return preg_replace_callback('/([A-Z])/', $func, $str);
  }
 
  /**
   * Translates a string with underscores into camel case (e.g. first_name -&gt; firstName)
   * @param    string   $str                     String in underscore format
   * @param    bool     $capitalise_first_char   If true, capitalise the first char in $str
   * @return   string                              $str translated into camel caps
   */
  public static function camelize($str, $capitalise_first_char = false) {
    if($capitalise_first_char) {
      $str[0] = strtoupper($str[0]);
    }
    $func = create_function('$c', 'return strtoupper($c[1]);');
    return preg_replace_callback('/_([a-z])/', $func, $str);
  }

  public static function lowercase($str){
    return strtolower($str);
  }

  public static function camelize_lower($str){
    return self::camelize(strtolower($str));
  }

  public static function environment_var_name($pfx, $str){
    return strtoupper($pfx.'_'.self::underscore($str));
  }

  public static function unprefix_variable_name($pfx, $str){
    return self::camelize_lower(preg_replace("/".$pfx."_/", '', $str, 1));
  }
}
?>
