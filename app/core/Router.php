<?php

namespace app\core; //указываем namespace, по которому будет доступен данный класс

use app\core\View; // подключаем класс View для последующего его использования в маршрутезаторе

class Router {

    // декларируем пустые массивы routes и params, которые будут доступны только по наследству.
    protected $routes = []; 
    protected $params = []; 
    
    // в конструкторе класса подключаем файл app/config/routes.php и его содержимое кладем в переменную $arr в качестве массива. Далее по средствам цикла foreach расскалдываем содержимое на ключ и значение, которые в свою очередь передаем в качестве параметров функции add
    public function __construct() {
        $arr = require 'app/config/routes.php';
        foreach ($arr as $key => $val) {
            $this->add($key, $val);
        }
    }

    /**
    * Функция add с модификатором доступа public, означает что данный метод доступен глобально.
    * Получает в качестве входных параметров: 
    * $route - ключ массива $arr из конструктора (controller и action);
    * $params - значение массива $arr из конструктора (main и index и другие);
    * preg_replace по средствам данной функции, в случае main/index/{page:\d+}, происходит преобразование, которое позволяет совместно с классом Pagination отображать страницы main/index с определенными новостями. 
     */  
    public function add($route, $params) {
        $route = preg_replace('/{([a-z]+):([^\}]+)}/', '(?P<\1>\2)', $route);
        $route = '#^'.$route.'$#';
        $this->routes[$route] = $params;
    }

    /**
    * Функция match с модификатором доступа public, означает что данный метод доступен глобально.
    * в переменную url кладем значение адреса при этом отсекая "/" при помощи функции trim
    * далее по средствам цикла foreach проходимся по массиву $this->routes, в котором ищем совпадения значений лежащих в переменной $url и ключе массива и кладем их в массив $matches
    * после проходимся по массиву $matches циклом foreach при этом проходим две проверки, первая: является ли ключь строкой, если да, то переходим ко второй проверки: является ли значение числом либо строкой, содержащее число, если да, то приводим его к типу число
    * далее кладем в массив $params ключ и значение
    * кладем в $this->params массив $params
    * если же после всех операций с циклами foreach и проверками на if значение в переменной url совпадает со значением $this->routes то возвращаем true, если нет, то false
     */  
    
     public function match() {
        $url = trim($_SERVER['REQUEST_URI'], '/blog/'); // имя папки указывается в случае развертывания на сервере в папку с данным именем "/blog/", если же сайт развертывается в корне, то необходимо заменить на "/"
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        if (is_numeric($match)) {
                            $match = (int) $match;
                        }
                        $params[$key] = $match;
                    }
                }
                
                $this->params = $params;
                return true;
            }
        }
        return false;
    }

    /**
    * Функция match с модификатором доступа public, означает что данный метод доступен глобально.
    * Первым шагом осуществляем проверку, если функция match() вернуло true, 
    * то получаем значение из массива $this->params по ключу controller,
    * приводим первую букву, полученного значения в верхний регистр, при помощи функции ucfirst
    * добавляем в конец к получившемуся значение слово Controller, тем самым мы получаем название файла контроллера 
    * перед названием файла добавляем путь к нему (папки, в которых он лежит).
    * Получившийся путь кладем в переменную $path.
    * Вторым шагом осуществляем проверку на существования такого класса при помощи функции class_exists, если есть то
    * получаем значение из массива $this->params по ключу action
    * к полученному значение конкатенируем слово Action
    * получившиеся выражение кладем в переменную $action.
    * Третий шаг, проверяем существует ли полученный action внутри файла $path,
    * если существует, то создаем экземпляр класса Controller и передаем ему параметры: $this->params
    * также вызывает у уже созданного объекта метод $action() (непосредственно тот метод, который лежит в переменной $action) 
    * В случае не прохождения любой из проверок на if пользователь попадет на страницу 404, по средствам View::errorCode(404)
     */  
    public function run(){
        if ($this->match()) {
            $path = 'app\controllers\\'.ucfirst($this->params['controller']).'Controller';
            if (class_exists($path)) {
                $action = $this->params['action'].'Action';
                if (method_exists($path, $action)) {
                    $controller = new $path($this->params);
                    $controller->$action();
                } else {
                    View::errorCode(404);
                }
            } else {
                View::errorCode(404);
            }
        } else {
            View::errorCode(404);
        }
    }

}