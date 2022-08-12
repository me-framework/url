<?php
namespace me\url;
use me\core\Component;
use me\core\Container;
use me\helpers\ArrayHelper;
use me\exceptions\Exception;
class UrlManager extends Component {
    /**
     * @var \me\url\UrlRule[] Url Rules
     */
    public $rules      = [];
    /**
     * @var array Rule Config such as class, pattern, verb, route
     */
    public $ruleConfig = ['class' => UrlRule::class];
    /**
     * 
     */
    protected function init() {
        parent::init();
        $this->rules = $this->buildRules($this->rules);
    }
    /**
     * Build Rules
     * @param array $ruleDeclarations Rule Declarations
     * @return \me\url\UrlRule[] Built Rules
     */
    private function buildRules($ruleDeclarations) {
        $builtRules = [];
        foreach ($ruleDeclarations as $key => $rule) {
            $builtRules[] = $this->buildRule($key, $rule);
        }
        return $builtRules;
    }
    /**
     * Build Rule
     * @param string $pattern Pattern
     * @param string|array|\me\url\UrlRule $rule Rule
     */
    private function buildRule($pattern, $rule) {
        if (is_string($rule)) {
            $verbs   = 'GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS';
            $rule    = ['route' => $rule];
            $matches = [];
            if (preg_match("/^((?:($verbs),)*($verbs))\\s+(.*)$/", $pattern, $matches)) {
                $rule['verb'] = explode(',', $matches[1]);
                $pattern      = $matches[4];
            }
            $rule['pattern'] = $pattern;
        }
        if (is_array($rule)) {
            $config = array_merge($this->ruleConfig, $rule);
            $class  = ArrayHelper::Remove($config, 'class');
            $rule   = Container::build($class, $config);
        }
        if (!($rule instanceof UrlRule)) {
            throw new Exception('Url rule class must implement UrlRule.');
        }
        return $rule;
    }
    /**
     * @param string $pathInfo Path Info
     * @param string $method Request Method
     * @return array [$route, $params]
     */
    public function parseRequest($pathInfo, $method) {
        foreach ($this->rules as $rule) {
            $result = $rule->parseRequest($pathInfo, $method);
            if ($result !== false) {
                return $result;
            }
        }
        return [$pathInfo, []];
    }
}