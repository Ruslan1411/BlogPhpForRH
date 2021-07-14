<?php

namespace app\core; //указываем namespace, по которому будет доступен данный класс

use app\core\View; // подключаем класс View для последующего его использования в контроллере

abstract class Controller {
	// декларируем переменные (свойства) route, view и acl, которые будут доступны публично.
	public $route;
	public $view;
	public $acl;

	 /**
    * Функция __construct с модификатором доступа public, означает что данный метод доступен глобально.
    * Получает в качестве входных параметров: 
    * $route - получаем в качестве входных параметров controller и action (и если id), переданных в объект при инициализации в методе run класса Router ($this->params)
    * кладем полученные входящие параметры в свойства данного класса $this->route
    * Проводим проверку посредствам if и метода класса(функции)  checkAcl.
	* Если же checkAcl возвращает false, то пользователя переносит на страницу 403 при помощи View::errorCode(403)
	* Если же checkAcl возвращает true, то создается экземпляр класса View с параметрами $route
	* Также включаем метод loadModel, в качестве параметров данный метод принимает значение массива $route с ключом controller
     */  
	public function __construct($route) {
		$this->route = $route;
		if (!$this->checkAcl()) {
			View::errorCode(403);
		}
		$this->view = new View($route);
		$this->model = $this->loadModel($route['controller']);
	}

	
    /**
    * Функция loadModel с модификатором доступа public, означает что данный метод доступен глобально.
    * Получает в качестве входных параметров: 
    * $name - значение которого является значение массива $route с ключом controller, полученным из конструктора класса.
    * Приводим первую букву, полученного значения в верхний регистр, при помощи функции ucfirst
    * добавляем в конец к получившемуся значение слово Model, тем самым мы получаем название файла модели 
    * перед названием файла добавляем путь к нему (папки, в которых он лежит).
    * Получившийся путь кладем в переменную $path. 
	* Осуществляем проверку на существования такого класса при помощи функции class_exists, если есть то возвращаем экземпляр класса
     */  
	public function loadModel($name) {
		$path = 'app\models\\'.ucfirst($name).'Model';
		if (class_exists($path)) {
			return new $path;
		}
	}

	/**
    * Функция checkAcl с модификатором доступа public, означает что данный метод доступен глобально. 
    * Получаем значение массива $this->route по ключу controller
    * добавляем в конец к получившемуся значение рассширение php, тем самым мы получаем название файла 
    * перед названием файла добавляем путь к нему (папки, в которых он лежит).
    * Получившийся путь подключаем посредством функции require.
	* Полученное значение кладем в $this->acl.
	* Далее осуществляем проверку на доступ и зависимости от ответа открываем либо запрещаем перемещение на страницы.
     */  
	public function checkAcl() {
		$this->acl = require 'app/acl/'.$this->route['controller'].'.php';
		if ($this->isAcl('all')) {
			return true;
		}
		elseif (isset($_SESSION['authorize']['id']) and $this->isAcl('authorize')) {
			return true;
		}
		elseif (!isset($_SESSION['authorize']['id']) and $this->isAcl('guest')) {
			return true;
		}
		elseif (isset($_SESSION['admin']) and $this->isAcl('admin')) {
			return true;
		}
		return false;
	}

	/**
    * Функция isAcl с модификатором доступа public, означает что данный метод доступен глобально. 
    * $key - входящий параметр, который в дальнейшем в подставляется в качестве ключа для массива $this->acl
	* При помощи функции in_array проверяется совпадение значений $this->route['action'] с $this->acl[$key], 
	* если находим совпадение, то возвращаем true
	* если нет - false. 
     */  
	public function isAcl($key) {
		return in_array($this->route['action'], $this->acl[$key]);
	}

}