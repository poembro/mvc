<?php  
/**
 * @Copyright (C), 
 * @Author poembro 
 * @Date: 2017-11-08 12:37:46
 * @Description 框架核心  
 */
namespace Nig;
  
use \Nig\Tree; 
use \Nig\Request;
use \Nig\Response;
use \Nig\Config;

class Nig
{ 
    public static $req = NULL;
    public static $res = NULL;
    public static $tree = NULL;
    public static $view = NULL; 
    public static $conf = NULL;
    private static $_instance = NULL;
    
    public function __construct($confPath = NULL)
    { 
        Import::addLibrary(FRAMEWORK_PATH, 'Nig');
        Import::addLibrary(APPLICATION_PATH, 'App');
         
        self::$conf = Config::getInstance($confPath);
        self::$tree = Tree::getInstance();
        self::$req = Request::getInstance(); 
        self::$res = Response::getInstance();  
    }
    
    public static function getInstance($confPath = NULL)
    {
        if (! self::$_instance)
        { 
            self::$_instance = new self($confPath);
        }
        
        return self::$_instance;
    }

    private static function _parseURL($url)
    {  
        if ($url && $url != '/')
        {
            $original = $url = strtolower($url);
            $url = str_replace("/",' ', $url);
            $segments = explode(' ', trim($url));
            
            if (count($segments) > 100)
            {
                return trigger_error('nig: url parse error ', E_USER_ERROR); 
            }
            return ['url' => $original, 'segments' => $segments];
        }
        
        $config= Config::get('ext'); 
        $url = $config['defaultUrl'];
        return self::_parseURL($url);
    }
    
    private static function addEvent($url, $frags, $event)
    {
    	$node = Tree::addNode(Tree::$root, $frags);
    	if (strcasecmp($url, $node->original) === 0)
    	{
    		return false;
    	}
    	 
    	$node->handlers[] = $event;
    	$node->original = $url;
    }
    
    public function useNode($url, $event)
    {
        $frags = self::_parseURL($url);  
        self::addEvent($frags['url'], $frags['segments'], $event); 
        return $this;
    }
    
    public function autoNode($url) 
    {
        $frags = self::_parseURL($url);  
        $segments = $frags['segments'];
        
        if (count($segments) < 2)
        {
            return $this;
        }
        
        $method = array_pop($segments);
        
        $tmp = implode("\\", array_map("ucfirst", $segments));
        $className = Config::get('ext')['namespace'] . $tmp;
        
        if (!class_exists($className, true)  
            || !method_exists($className, $method))
        {
             return trigger_error("nig: controllers or methods not found !<br /> \r\n",
                 E_USER_ERROR); 
        }
        
        self::addEvent($frags['url'], $frags['segments'], [$className, $method]);
        return $this;
    }

    private static function _handle(array $stack, $url)
    {     
        foreach ($stack as $k => $node)
        {
            if (empty($node->handlers) || strcasecmp($url, 
                $node->original) !== 0)
            {
                continue;
            }
    
            foreach ($node->handlers as $func)
            {
            	self::$req->set('request_uri', $url);
            	
            	if (!is_array($func))
            	{
            	    return 	$func(self::$req, self::$res);
            	}
            	
                $class = array_shift($func);
                $method =  array_shift($func);
            	$object = new $class(self::$req, self::$res); 
            	
                return call_user_func_array([$object, $method], 
                    [self::$req, self::$res]);
            }
        }
    }
    
    public function run($current) 
    {  
        $frags = self::_parseURL($current);
        $stack = Tree::getNode($frags['segments']); 
        return self::_handle($stack, $frags['url']);   
    }
}


  
/**
 * @Copyright (C),
 * @Author poembro
 * @Date: 2017-11-08 12:37:46
 * @Description 自动加载类
 */
class Import
{
    private static $_libs = array();
    private static $_isInit = false;

    public static function addLibrary($path, $libPre = null)
    {
        if (!self::$_isInit)
        {
            self::_init();
        }
        if (!$libPre)
        {
            $libPre = basename($path);
        }
        elseif ('*' == $libPre)
        {
            return set_include_path(get_include_path() . PATH_SEPARATOR . $path);
        }
        self::$_libs[$libPre] = $path;
    }

    public static function load($className)
    {
        if (!strpos($className, '\\'))
        {
            return include_once($className . '.php');
        }
        $params = explode('\\', $className);
        $libName = array_shift($params);

        if (!self::$_libs[$libName])
        {
            return false;
        }
        $path = self::$_libs[$libName] . implode(DS, $params) . '.php';
        if (! is_file($path))
        {
            $path = dirname($path);
            $path = $path . DS . basename($path) . '.php';
            if (is_file($path))
            {
                include_once($path);
            }
        }
        else
        {
            include_once($path);
        }
    }

    private static function _init()
    {
        spl_autoload_register(array(__CLASS__, 'load'));
        self::$_isInit = true;
    }
}