<?php

/* ---------------------------------------------------------------------------------- */
/*  OpenCart Factory (used by the the Override Engine)                                */
/*                                                                                    */
/*  Copyright © 2012-2017 by J.Neuhoff (www.mhccorp.com)                              */
/*                                                                                    */
/*  This file is part of the Override Engine for OpenCart.                            */
/*                                                                                    */
/*  OpenCart is free software: you can redistribute it and/or modify                  */
/*  it under the terms of the GNU General Public License as published by              */
/*  the Free Software Foundation, either version 3 of the License, or                 */
/*  (at your option) any later version.                                               */
/*                                                                                    */
/*  OpenCart is distributed in the hope that it will be useful,                       */
/*  but WITHOUT ANY WARRANTY; without even the implied warranty of                    */
/*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                     */
/*  GNU General Public License for more details.                                      */
/*                                                                                    */
/*  You should have received a copy of the GNU General Public License                 */
/*  along with OpenCart.  If not, see <http://www.gnu.org/licenses/>.                 */
/* ---------------------------------------------------------------------------------- */

final class Factory 
{
	protected $registry;
	protected $addon_dir;
	protected $addons;
	protected $is_admin;
	
	const CONTROLLER_CLASS = 1;
	const MODEL_CLASS = 2;
	const SYSTEM_CLASS = 3;


	public function __construct($registry) {
		$this->registry = $registry;

		// get the addon directory
		$this->addon_dir = realpath( DIR_SYSTEM.'library/override/' );
		if ($this->addon_dir===FALSE) {
			trigger_error( "Could not find the directory '". DIR_SYSTEM.'library/override/' ."'!" );
			exit;
		}
		if (!$this->endsWith( $this->addon_dir, '/' )) {
			$this->addon_dir .= '/';
		}

		// get all the names of the addons
		if (!$handle = opendir($this->addon_dir)) {
			trigger_error( "Could not open the directory '".$this->addon_dir."'!" );
			exit;
		}
		$this->addons = array();
		while (false !== ($addon = readdir($handle))) {
			if (!is_dir($this->addon_dir.$addon)) {
				continue;
			}
			if (($addon=='..') || ($addon=='.')) {
				continue;
			}
			$this->addons[] = $addon;
		}
		closedir($handle);
		sort( $this->addons );

		// find out whether we are on the OpenCart frontend or admin backend
		$this->is_admin = defined( 'DIR_CATALOG' );
	}


	public function modCheck( $file ) {
		// return a PHP file possibly modified by OpenCart's system/storage/modification,
		//   and then possibly modified by vqmod (see also https://github.com/vqmod/vqmod)

		// Use OpenCart's modified file is available
		$original_file = $file;
		if (defined('DIR_MODIFICATION')) {
			if ($this->startsWith($file,DIR_APPLICATION)) {
				if ($this->isAdmin()) {
					if (file_exists( DIR_MODIFICATION . 'admin/' . substr($file,strlen(DIR_APPLICATION)) )) {
						$file = DIR_MODIFICATION . 'admin/' . substr($file,strlen(DIR_APPLICATION));
					}
				} else {
					if (file_exists( DIR_MODIFICATION . 'catalog/' . substr($file,strlen(DIR_APPLICATION)) )) {
						$file = DIR_MODIFICATION . 'catalog/' . substr($file,strlen(DIR_APPLICATION));
					}
				}
			} else if ($this->startsWith($file,DIR_SYSTEM)) {
				if (file_exists( DIR_MODIFICATION . 'system/' . substr($file,strlen(DIR_SYSTEM)) )) {
					$file = DIR_MODIFICATION . 'system/' . substr($file,strlen(DIR_SYSTEM));
				}
			}
		}

		// Don't use VQmod 2.3.2 or earlier if available
		if (!empty($GLOBALS['vqmod'])) {
			trigger_error( "You are using an old VQMod version '2.3.2' or earlier, please upgrade your VQMod!" );
			exit;
		}

		// Use modification through VQmod 2.4.0 or later if available
		if (class_exists('VQMod',false)) {
			if (VQMod::$directorySeparator) {
				if (strpos($file,'vq2-')!==FALSE) {
					return $file;
				}
				if (version_compare(VQMod::$_vqversion,'2.5.0','<')) {
					trigger_error( "You are using an old VQMod version '".VQMod::$_vqversion."', please upgrade your VQMod!" );
					exit;
				}
				if ($original_file != $file) {
					return VQMod::modCheck($file,$original_file);
				}
				return VQMod::modCheck($original_file);
			}
		}

		// no VQmod
		return $file;
	}


	public function __get($key) {
		return $this->registry->get($key);
	}


	public function __set($key, $value) {
		$this->registry->set($key, $value);
	}


	public function getAddonDir() {
		return $this->addon_dir;
	}


	public function getAddons() {
		return $this->addons;
	}


	protected function isAdmin() {
		return $this->is_admin;
	}


	private function pathToClassName( $prefix, $route ) {
		$class = $prefix;
		$isUpper = TRUE;
		for ($i=0; $i<strlen($route); $i++) {
			if ($route[$i]=='/') {
				$isUpper = TRUE;
				continue;
			}
			if ($route[$i]=='_') {
				$isUpper = TRUE;
				continue;
			}
			$class .= ($isUpper) ? strtoupper( $route[$i] ) : $route[$i];
			$isUpper = FALSE;
		}
		return $class;
	}


	public function actionProperties( $route ) {
		$route = str_replace('../', '', (string)$route);

		$file = DIR_APPLICATION . 'controller/' . $route . '.php';
		if (file_exists( $file ) && is_file( $file )) {
			$class = $this->pathToClassName( 'Controller', $route );
			return array( 'file'=>$file, 'class'=>$class, 'method'=>'index' );
		} 

		$i = strrpos( $route, '/' );
		if ($i===FALSE) {
			$this->log->write("Cannot find controller class file for route '$route'");
			return array( 'file'=>'', 'class'=>'', 'method'=>'index' );
		}
		$method = substr( $route, $i+1 );
		$filepath = substr( $route, 0, $i );
		if ($filepath===FALSE) {
			$this->log->write("Cannot find controller class file for route '$route'");
			return array( 'file'=>'', 'class'=>'', 'method'=>'index' );
		}

		$file = DIR_APPLICATION . 'controller/' . $filepath . '.php';
		if (file_exists( $file ) && is_file( $file )) {
			$class = $this->pathToClassName( 'Controller', $filepath );
			return array( 'file'=>$file, 'class'=>$class, 'method'=>$method );
		}

		$this->log->write("Cannot find controller class file for route '$route'");
		return array( 'file'=>'', 'class'=>'', 'method'=>'index' );
	}


	private function isLetterOrUnderscore( $ch ) {
		if (($ch>='a') && ($ch<='z') || ($ch>='A') && ($ch<='Z') || ($ch=='_') || ($ch>="\x7f") && ($ch<="\xff")) {
			return TRUE;
		}
		return FALSE;
	}


	private function isLetterOrNumberOrUnderscore( $ch ) {
		if (($ch>='0') && ($ch<='9') || ($ch>='a') && ($ch<='z') || ($ch>='A') && ($ch<='Z') || ($ch=='_') || ($ch>="\x7f") && ($ch<="\xff")) {
			return TRUE;
		}
		return FALSE;
	}


	private function isWhiteSpace( $ch ) {
		return (($ch==' ') || ($ch=="\n") || ($ch=="\r") || ($ch=="\t"));
	}


	private function nextToken( $buffer, $start ) {
		$j = strlen($buffer);
		$i = $start;
		while ($i<$j) {
			$ch = $buffer[$i];
			if ($this->isWhiteSpace( $ch )) {
				$i += 1;
				continue;
			}
			if ($this->isLetterOrUnderscore( $ch )) {
				$k = $i+1;
				while ($k < $j) {
					$ch = $buffer[$k];
					if ($this->isLetterOrNumberOrUnderscore( $ch )) {
						$k += 1;
						continue;
					}
					break;
				}
				return array( $i, substr( $buffer, $i, $k-$i ) );
			}
			if (($ch=='/') && ($i>0) && ($buffer[$i-1]=='/')) {
				// skip line comment until "\n"
				$i += 1;
				while ($i<$j) {
					$ch = $buffer[$i];
					if ($ch=="\n") {
						break;
					}
					$i += 1;
				}
			} else if (($ch=='*') && ($i>0) && ($buffer[$i-1]=='/')) {
				// skip comment until "*/"
				$i += 1;
				while ($i<$j) {
					$ch = $buffer[$i];
					if (($ch=='/') && ($buffer[$i-1]=='*')) {
						break;
					}
					$i += 1;
				}
			}
			$i += 1;
		}
		return array( NULL, '' );
	}


	private function classWithModifiedParentName( $modFile, $parent ) {
		// load class file into a string buffer
		$buffer = file_get_contents( $modFile );

		// find the position of the parent class name
		$pos = 0;
		$token = '';
		while (($pos!==NULL) && (strtolower($token)!='class')) {
			$pos += strlen($token);
			list( $pos, $token ) = $this->nextToken( $buffer, $pos );
		}
		if ($pos===NULL) {
			trigger_error( "The contents in file '$modFile' does not appear to be a valid class" );
			exit;
		}
		$pos += strlen($token);
		list( $pos, $token ) = $this->nextToken( $buffer, $pos );
		if ($pos===NULL) {
			trigger_error( "The contents in file '$modFile' does not appear to be a valid class" );
			exit;
		}
		$pos += strlen($token);
		list( $pos, $token ) = $this->nextToken( $buffer, $pos );
		if ($pos===NULL) {
			trigger_error( "The contents in file '$modFile' does not appear to be a valid class" );
			exit;
		}
		if ($token != 'extends') {
			trigger_error( "The contents in file '$modFile' does not appear to be a valid child class" );
			exit;
		}
		$pos += strlen($token);
		list( $pos, $token ) = $this->nextToken( $buffer, $pos );
		if ($pos===NULL) {
			trigger_error( "The contents in file '$modFile' does not appear to be a valid class" );
			exit;
		}
		
		// replace the parent class name in the string buffer with the new one 
		$oldParent = $token;
		$result = substr( $buffer, 0, $pos ) . $parent . substr( $buffer, $pos+strlen($oldParent) );
		return $result;
	}


	private function checkDir($path) {
		if (!is_dir($path)) {
			if(!mkdir($path)) {
				trigger_error( "Error: Cannot create the directory '$path'!" );
				exit;
			}
		}
	}


	private function findLatestFileDate( $file, $modFiles ) {
		$date = filemtime( $file );
		if ($date) {
			foreach ($modFiles as $modFile) {
				$modDate = filemtime( $modFile['file'] );
				if ($modDate) {
					if ($modDate > $date) {
						$date = $modDate;
					}
				} else {
					$date = time();
					break;
				}
			}
		} else {
			$date = time();
		}
		return $date;
	}


	private function unlinkFiles( $files ) {
		foreach ($files as $file) {
			unlink( $file );
		}
	}


	private function findNameSpace( $file ) {
		if ($this->startsWith( $file, DIR_SYSTEM.'library/' )) {
			if ($this->startsWith( $file, DIR_SYSTEM.'library/override/' )) {
				return '';
			}
			$sub_path = substr( $file, strlen(DIR_SYSTEM.'library/') );
			$parts = explode( '/', $sub_path );
			array_pop( $parts );
			$namespace = '';
			foreach ($parts as $part) {
				$namespace .= ucfirst($part);
				$namespace .= '\\';
			}
			return $namespace;
		} else {
			return '';
		}
	}


	private function newInstance( $type, $file, $class, $args=array() ) {
		// get the relative class file path 
		//   '/controller/<...>/<filename>.php' or '/model/<...>/<filename>.php' or '/system/<...>/<filename>.php'
		$i=FALSE;
		switch ($type) {
			case self::CONTROLLER_CLASS:
				$i = strrpos( $file, '/controller/' );
				if ($i===FALSE) {
					trigger_error("Invalid file path '$file' for controller class '$class'");
					exit;
				}
				break;
			case self::MODEL_CLASS:
				$i = strrpos( $file, '/model/' );
				if ($i===FALSE) {
					trigger_error("Invalid file path '$file' for model class '$class'");
					exit;
				}
				break;
			case self::SYSTEM_CLASS:
				$i = strrpos( $file, '/system/' );
				if ($i===FALSE) {
					trigger_error("Invalid file path '$file' for system class '$class'");
					exit;
				}
				break;
			default:
				trigger_error("Invalid class type for file path '$file' and class '$class'");
				exit;
		}
		$filepath = substr( $file, $i );

		// find all similar class files from addons which extend the original class
		$modFiles = array();
		if ($type==self::SYSTEM_CLASS) {
			$prefix = '';
		} else {
			$prefix = ($this->isAdmin()) ? '/admin' : '/catalog';
		}
		foreach ($this->addons as $addon) {
			$modFile = $this->addon_dir . $addon . $prefix . $filepath;
			$modCache = DIR_CACHE . 'override/' . str_replace( array('/','-'),'_',$addon . $prefix . $filepath );
			if (file_exists( $modFile ) && is_file( $modFile )) {
				$modFiles[$addon] = array('file'=>$modFile,'cache'=>$modCache);
			}
		}
		//var_dump($file);
		// find namespace for class
		$namespace = $this->findNameSpace( $file );
		
		// load original class file (possibly modified by ocmod or vqmod)
		$file = $this->modCheck( $file );
		require_once( $file );

		// get latest file date of original and override files
		$date = $this->findLatestFileDate( $file, $modFiles );

		// load possibly modified child classes into memory buffers
		$isFinal = FALSE;
		$parent = $class;
		foreach ($modFiles as $addon => $modFile) {
			if ($parent == $class) {
				// first child class file can be loaded without dynamic modifications
				$modFiles[$addon]['buffer'] = NULL;
			} else {
				// Parent name of this child class must be dynamically set to the previous child class.
				$modFiles[$addon]['buffer'] = $this->classWithModifiedParentName( $modFile['file'], $parent );
			}
			$parent = str_replace('-','_',$addon).'_'.$class;
		}

		// check cached files
		$this->checkDir(DIR_CACHE . 'override');
		$cacheFiles = glob( DIR_CACHE . 'override/*'.str_replace( array('/','-'),'_',$prefix.$filepath ) );
		if ($cacheFiles) {
			if (count($cacheFiles) != (count($modFiles)-1)) {
				$this->unlinkFiles( $cacheFiles );
			} else {
				foreach ($cacheFiles as $cacheFile) {
					$cacheDate = filemtime( $cacheFile );
					if ($cacheDate === FALSE) {
						$this->unlinkFiles($cacheFiles);
						break;
					}
					if ($date > $cacheDate) {
						$this->unlinkFiles($cacheFiles);
						break;
					}
				}
			}
		}

		// load (possibly cached) child classes for PHP
		$isFinal = FALSE;
		$lastChild = NULL;
		foreach ($modFiles as $addon => $modFile) {
			if ($modFile['buffer'] == NULL) {
				if (file_exists( $modFile['cache'] )) {
					unlink( $modFile['cache'] );
				}
				require_once( $modFile['file'] );
			} else {
				if (is_file( $modFile['cache'] )) {
					if (file_get_contents($modFile['cache']) != $modFile['buffer']) {
						unlink($modFile['cache']);
						file_put_contents( $modFile['cache'], $modFile['buffer'], LOCK_EX );
					}
				} else {
					if (file_exists( $modFile['cache'] )) {
						unlink($modFile['cache']);
					}
					file_put_contents( $modFile['cache'], $modFile['buffer'], LOCK_EX );
				} 
				require_once( $modFile['cache'] );
			}
			$child = str_replace('-','_',$addon).'_'.$class;
			$reflectionClass = new ReflectionClass( $namespace.$child );
			if ($reflectionClass->isFinal()) {
				if (count($modFiles) > 1) {
					trigger_error( "'$child' is a final child class, no other addon can extend class '$class'" );
				}
			}
			$lastChild = $child;
		}

		// create a new instance of the last child class, or the original class if there were no child classes
		$class = ($lastChild) ? $lastChild : $class;

		if (empty($args)) {
			$class = $namespace.$class;
			return new $class();                                                                                                                                                         
		} else {
			$ref = new ReflectionClass($namespace.$class);
			return $ref->newInstanceArgs($args);
		}
	}


	public function newController( $file, $class ) {
		return $this->newInstance( self::CONTROLLER_CLASS, $file, $class, array( $this->registry ) );
	}


	public function newModel( $path ) {
		// find the original class file
		$file = DIR_APPLICATION . 'model/' . $path . '.php';
		if (file_exists( $file ) && is_file( $file )) {
			$class = $this->pathToClassName( 'Model', $path );
		} else {
			trigger_error("Cannot find model class file for path '$path'");
			exit;
		}

		// get a new instance of the class
		return $this->newInstance( self::MODEL_CLASS, $file, $class, array( $this->registry ) );
	}


	private function newSystemClass( $filepath, $args=array() ) {
		// find the original class file
		$basename = basename( $filepath, '.php' );
		$file = DIR_SYSTEM . $filepath;
		if (file_exists( $file ) && is_file( $file )) {
			$class = $this->pathToClassName( '', $basename );
		} else {
			trigger_error("Cannot find system class file for '$filepath'");
			exit;
		}
		
		// get a new instance of the class
		return $this->newInstance( self::SYSTEM_CLASS, $file, $class, $args );
	}


	public function loadLanguage( $path, $default, $directory ) {
		$directories = array( 'english', $default, $directory );
		$data = array();
		foreach ($directories as $directory) {

			// load original language file (possibly modified on the fly by VQmod)
			$filepath = $directory . '/' . $path . '.php';
			$file = DIR_LANGUAGE . $filepath;
			if (file_exists( $file ) && is_file( $file )) {
				$_ = array();
				require( $this->modCheck( $file ) );
				$data = array_merge($data, $_);
			} else {
				// Compatibility code for old extension folders
				$filepath = $directory . '/' . str_replace( 'extension/', '', $path ) . '.php';
				$file = DIR_LANGUAGE . $filepath;
				if (file_exists( $file ) && is_file( $file )) {
					$_ = array();
					require( $this->modCheck( $file ) );
					$data = array_merge($data, $_);
				}
			}

			// overload with language file modifications from addons
			$modFiles = array();
			$prefix = ($this->isAdmin()) ? '/admin/language/' : '/catalog/language/';
			foreach ($this->addons as $addon) {
				$modFile = $this->addon_dir . $addon . $prefix . $filepath;
				if (file_exists( $modFile ) && is_file( $modFile )) {
					$_ = array();
					require( $modFile );
					$data = array_merge($data, $_);
				}
			}
		}

		return $data;
	}


	// helper function, see http://php.net/manual/en/function.file-put-contents.php#84180
	protected function file_force_contents($dir, $contents, $flags) {
		$parts = explode('/', substr($dir,1));
		$file = array_pop($parts);
		$dir = '';
		foreach ($parts as $part) {
			$dir .= "/$part";
			if (!is_dir($dir)) { 
				mkdir($dir);
			}
		}
		file_put_contents( "$dir/$file", $contents, $flags );
	}

	
	protected function findTemplateEngine( $route ) {
		if ($this->isAdmin()) {
			$dir_template = DIR_TEMPLATE;
		} else {
			if ($this->config->get('config_theme') == 'default') {
				$theme = $this->config->get('theme_default_directory');
			} else {
				$theme = $this->config->get('config_theme');
			}
			$dir_template = DIR_TEMPLATE . $theme . '/template/';
		}
		$template_engine_files = glob(DIR_SYSTEM . 'library/template/*.php');
		if ($template_engine_files) {
			foreach ($template_engine_files as $template_engine_file) {
				$basename = basename($template_engine_file,'.php');
				$ext = ($basename=='template') ? '.tpl' : '.'.$basename;
//				if (file_exists($dir_template.$route.$ext) && is_file($dir_template.$route.$ext)) {
				if (is_file($dir_template.$route.$ext)) {
					return $basename;
				}
			}
			if ($this->isAdmin()) {
				trigger_error("Cannot find template file for route '$route'");
				exit;
			}
			$dir_template = DIR_TEMPLATE . 'default/template/';
			foreach ($template_engine_files as $template_engine_file) {
				$basename = basename($template_engine_file,'.php');
				$ext = ($basename=='template') ? '.tpl' : '.'.$basename;
//				if (file_exists($dir_template.$route.$ext) && is_file($dir_template.$route.$ext)) {
				if (is_file($dir_template.$route.$ext)) {
					return $basename;
				}
			}
		}
		trigger_error("Cannot find template file for route '$route'");
		exit;
	}


	protected function readTemplate( $route, $event_template_buffer ) {
		// if there already is a modified template from view/*/before events use that one
		if ($event_template_buffer) {
			$template_engine = $this->findTemplateEngine( $route );
			$ext = ($template_engine=='template') ? '.tpl' : '.'.$template_engine;
			if ($this->isAdmin()) {
				if (!is_file(DIR_TEMPLATE.$route.$ext)) {
					trigger_error("Cannot find template file for route '$route'");
					exit;
				}
				$template = $route.$ext;
			} else {
				if ($this->config->get('config_theme') == 'default') {
					$theme = $this->config->get('theme_default_directory');
				} else {
					$theme = $this->config->get('config_theme');
				}
				if (is_file(DIR_TEMPLATE.$theme.'/template/'.$route.$ext)) {
					$template = $theme.'/template/'.$route.$ext;
				} else if (is_file(DIR_TEMPLATE.'default/template/'.$route.$ext)) {
					$template = 'default/template/'.$route.$ext;
				} else {
					trigger_error("Cannot find template file for route '$route'");
					exit;
				}
			}
			$template_file = DIR_CACHE.'override/event/'.$template;
			if (is_file($template_file)) {
				if ($event_template_buffer != file_get_contents($template_file)) {
					file_put_contents($template_file,$event_template_buffer,LOCK_EX);
				}
			} else {
				$this->file_force_contents($template_file,$event_template_buffer,LOCK_EX);
			}
			return array($event_template_buffer,$template_file,$template_engine);
		}

		// load the template file (possibly modified by vqmod) into a string buffer
		if ($this->isAdmin()) {
			$dir_template = DIR_TEMPLATE;
		} else {
			if ($this->config->get('config_theme') == 'default') {
				$theme = $this->config->get('theme_default_directory');
			} else {
				$theme = $this->config->get('config_theme');
			}
			$dir_template = DIR_TEMPLATE . $theme . '/template/';
		}
		$template_engine_files = glob(DIR_SYSTEM . 'library/template/*.php');
		if ($template_engine_files) {
			foreach ($template_engine_files as $template_engine_file) {
				$template_engine = basename($template_engine_file,'.php');
				if ($template_engine=='template') {
					$template_file = $dir_template . $route . '.tpl';
				} else {
					$template_file = $dir_template . $route . '.' . $template_engine;
				}
				if (file_exists( $template_file ) && is_file( $template_file )) {
					$template_file = $this->modCheck( $template_file );
					return array( file_get_contents( $template_file ), $template_file, $template_engine );
				}
			}
		}
		if ($this->isAdmin()) {
			trigger_error("Cannot find template file for route '$route'");
			exit;
		}
		$dir_template = DIR_TEMPLATE . 'default/template/';
		if ($template_engine_files) {
			foreach ($template_engine_files as $template_engine_file) {
				$template_engine = basename($template_engine_file,'.php');
				if ($template_engine=='template') {
					$template_file = $dir_template . $route . '.tpl';
				} else {
					$template_file = $dir_template . $route . '.' . $template_engine;
				}
				if (file_exists( $template_file ) && is_file( $template_file )) {
					$template_file = $this->modCheck( $template_file );
					return array( file_get_contents( $template_file ), $template_file, $template_engine );
				}
			}
		}
		trigger_error("Cannot find template file for route '$route'");
		exit;
	}


	public function loadView( $route, &$data, $event_template_buffer=null ) {
		// find the calling controller instance
		$controller = null;
		$backtrace = debug_backtrace();
		if (!empty($backtrace[2]['class'])) {
			$class = $backtrace[2]['class'];
			if ($this->startsWith($class,'Controller')) {
				if (!empty($backtrace[2]['object'])) {
					$controller = $backtrace[2]['object'];
				}
			}
		}

		// load the template buffer
		list($template_buffer,$template_file,$template_engine) = $this->readTemplate( $route, $event_template_buffer );

		if ($controller) {
			// give controller (which might be an extended class) a last chance to modify the template
			$saved_template_buffer = $template_buffer;
			if ($template_engine=='template') {
				$ext = '.tpl';
			} else {
				$ext = '.'.$template_engine;
			}
			if ($this->isAdmin()) {
				$template = $route.$ext;
			} else {
				if ($this->config->get('config_theme') == 'default') {
					$theme = $this->config->get('theme_default_directory');
				} else {
					$theme = $this->config->get('config_theme');
				}
				$template = $theme.'/template/'.$route.$ext;
			}
			$template_buffer = $controller->preRender( $template_buffer, $template, $data );
			$this->checkDir( DIR_CACHE . 'override' );
			if ($saved_template_buffer != $template_buffer) {
				$prefix = $this->isAdmin() ? 'admin_view_template_' : 'catalog_view_theme_';
				$template_file = DIR_CACHE . 'override/' . $prefix.str_replace( '/','_',$template );
				if (is_file($template_file)) {
					if (file_get_contents($template_file) != $template_buffer) {
						file_put_contents( $template_file, $template_buffer, LOCK_EX );
					}
				} else {
					file_put_contents( $template_file, $template_buffer, LOCK_EX );
				}
				$route = 'override/'.$route;
			} else {
				$prefix = $this->isAdmin() ? 'admin_view_template_' : 'catalog_view_theme_';
				$old_file = DIR_CACHE . 'override/' . $prefix.str_replace( '/','_',$template );
				if (is_file( $old_file )) {
					unlink( $old_file );
				}
			}
		}

		// render
		if ($template_engine=='twig') {
			include_once(DIR_SYSTEM . 'library/template/Twig/Autoloader.php');
			Twig_Autoloader::register();
			$loader = new \Twig_Loader_Filesystem(DIR_TEMPLATE);		
			$config = array('autoescape' => false, 'cache'=>false);
			$twig = new \Twig_Environment($loader, $config);
			$output = $twig->createTemplate($template_buffer)->render($data);
		} else {
			$template_object = $this->newTemplate($template_engine);
			foreach ($data as $key => $value) {
				$template_object->set($key, $value);
			}
			$template_file = $this->getRelativePath( DIR_TEMPLATE, realpath($template_file) );
			$template_file_without_ext = preg_replace("/\.[^.]+$/", "", $template_file);
			$output = $template_object->render($template_file_without_ext);
		}
		return $output;
	}


	// see http://stackoverflow.com/questions/2637945/getting-relative-path-from-absolute-path-in-php
	private function getRelativePath($from, $to) {
		// some compatibility fixes for Windows paths
		$from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
		$to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
		$from = str_replace('\\', '/', $from);
		$to   = str_replace('\\', '/', $to);

		$from     = explode('/', $from);
		$to       = explode('/', $to);
		$relPath  = $to;

		foreach($from as $depth => $dir) {
			// find first non-matching dir
			if($dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}
		return implode('/', $relPath);
	}


	private function startsWith( $haystack, $needle ) {
		if (strlen( $haystack ) < strlen( $needle )) {
			return FALSE;
		}
		return (substr( $haystack, 0, strlen($needle) ) == $needle);
	}


	private function endsWith( $haystack, $needle ) {
		if (strlen( $haystack ) < strlen( $needle )) {
			return FALSE;
		}
		return (substr( $haystack, strlen($haystack)-strlen($needle), strlen($needle) ) == $needle);
	}


	public function newLibrary( $route, $registry ) {
		return $this->newSystemClass( 'library/'.$route.'.php', array($registry) );
	}


	public function newLanguage( $languageDirectory ) {
		return $this->newSystemClass( 'library/language.php', array( $languageDirectory, $this ) );
	}


	public function newMediator( $registry ) {
		return $this->newSystemClass( 'engine/event.php', array($registry) );
	}


	private function classNameToFileName( $class ) {
		$file_name = '';
		$j = strlen($class);
		for ($i=0; $i<$j; $i++) {
			if ($i > 0) {
				if (ctype_upper( $class[$i] )) {
					if (!ctype_upper( $class[$i-1] )) {
						$file_name .= '_';
					}
				}
			}
			$file_name .= strtolower( $class[$i] );
		}
		return $file_name . '.php';
	}


	private function nameToFilePath( $name ) {
		$parts = explode( '_', $name );
		$j = count($parts);
		$parts[$j-1] = $this->classNameToFileName( $parts[$j-1] );
		for ($i=0; $i<$j-1; $i++) {
			$parts[$i] = strtolower($parts[$i]);
		}
		$path = 'library/' . implode( '/', $parts );
		return $path;
	}


	public function __call( $name, $arguments ) {
		// Used to detect calls which attempt to create instances of system library classes.
		//
		//   $factory->newDocument()
		//   $factory->newUrl($config->get('site_ssl'))
		//   $factory->newCart_Customer($registry)
		//
		// The method name always has to start with 'new'.
		//

		
		if (($name != 'newSystemClass') && ($name != 'newInstance')) {
			if (strlen($name) >= 4) {
				if ($this->startsWith( $name, 'new' )) {
					$class_name = substr( $name, strlen('new') );
					return $this->newSystemClass( $this->nameToFilePath($class_name), $arguments );
				}
			}
		}
		trigger_error("Cannot find public method '$name' in Factory class");
		exit;
	}
}
?>