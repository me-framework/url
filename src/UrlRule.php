<?php
namespace me\url;
use me\core\Component;
class UrlRule extends Component {
    /**
     * @var array Verbs: GET, POST, ...
     */
    public $verb;
    /**
     * @var string Pattern
     */
    public $pattern;
    /**
     * @var string Route
     */
    public $route;
    /**
     * @var array Placeholders
     */
    private $placeholders = [];
    /**
     * @var array Routes Variables
     */
    private $routesV      = [];
    /**
     * @var array Parameters Variables
     */
    private $paramsV      = [];
    /**
     * 
     */
    public function init() {
        parent::init();
        if (!is_null($this->verb)) {
            if (is_array($this->verb)) {
                foreach ($this->verb as $i => $verb) {
                    $this->verb[$i] = strtoupper($verb);
                }
            }
            else {
                $this->verb = [strtoupper($this->verb)];
            }
        }
        $this->pattern = trim($this->pattern, '/');
        $this->route   = trim($this->route, '/');
        if ($this->pattern === '') {
            $this->pattern = '#^$#u';
            return;
        }
        $this->pattern = '/' . $this->pattern . '/';
        $this->preparePattern();
    }
    /**
     * 
     */
    private function preparePattern() {
        if (strpos($this->route, '<') !== false && preg_match_all('/<([\w._-]+)>/', $this->route, $matches)) {
            foreach ($matches[1] as $name) {
                $this->routesV[] = $name;
            }
        }
        $tr = ['.' => '\\.', '*' => '\\*', '$' => '\\$', '[' => '\\[', ']' => '\\]', '(' => '\\(', ')' => '\\)'];
        if (preg_match_all('/<([\w._-]+):?([^>]+)?>/', $this->pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = $match[1][0];
                $type = isset($match[2][0]) ? $match[2][0] : '[^\/]+';

                $placeholder   = 'a' . hash('crc32b', $name);
                $tr["<$name>"] = "(?P<$placeholder>$type)";

                $this->placeholders[$placeholder] = $name;
                if (array_search($name, $this->routesV, true) === false) {
                    $this->paramsV[] = $name;
                }
            }
        }
        $template      = preg_replace('/<([\w._-]+):?([^>]+)?>/', '<$1>', $this->pattern);
        $this->pattern = '#^' . trim(strtr($template, $tr), '/') . '$#u';
    }
    /**
     * @param string $pathInfo Path Info
     * @param string $method Request Method
     * @return array|false [string $route, array $params]
     */
    public function parseRequest($pathInfo, $method) {
        if (!empty($this->verb) && !in_array($method, $this->verb, true)) {
            return false;
        }
        if (!preg_match($this->pattern, $pathInfo, $matches)) {
            return false;
        }
        foreach ($this->placeholders as $placeholder => $name) {
            if (isset($matches[$placeholder])) {
                $matches[$name] = $matches[$placeholder];
                unset($matches[$placeholder]);
            }
        }
        $translates = [];
        foreach ($this->routesV as $name) {
            if (isset($matches[$name])) {
                $translates["<$name>"] = $matches[$name];
            }
        }
        $route  = $translates ? strtr($this->route, $translates) : $this->route;
        $params = [];
        foreach ($this->paramsV as $name) {
            $params[$name] = $matches[$name];
        }
        return [$route, $params];
    }
}