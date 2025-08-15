<?php

/**
 * Class PMGI_Input
 */
class PMGI_Input {

    /**
     * @var string[]
     */
    protected $filters = ['stripslashes'];

    /**
     * @param $inputArray
     * @param $paramName
     * @param null $default
     * @return array|mixed|null
     * @throws Exception
     */
    public function read($inputArray, $paramName, $default = NULL) {
		if (is_array($paramName) and ! is_null($default)) {
			throw new Exception('Either array of parameter names with default values as the only argument or param name and default value as separate arguments are expected.');
		}
		if (is_array($paramName)) {
			foreach ($paramName as $param => $def) {
				if (isset($inputArray[$param])) {
					$paramName[$param] = $this->applyFilters($inputArray[$param]);
				}
			}
			return $paramName;
		} else {
			return isset($inputArray[$paramName]) ? $this->applyFilters($inputArray[$paramName]) : $default;
		}
	}

    /**
     * @param $paramName
     * @param null $default
     * @return array|mixed|null
     * @throws Exception
     */
    public function get($paramName, $default = NULL) {
		$this->addFilter('strip_tags');
		$this->addFilter('htmlspecialchars');		
		$this->addFilter('esc_sql');		
		$result = $this->read($_GET, $paramName, $default);
		$this->removeFilter('strip_tags');		
		$this->removeFilter('htmlspecialchars');		
		$this->removeFilter('esc_sql');
		return $result;
	}

    /**
     * @param $paramName
     * @param null $default
     * @return array|mixed|null
     * @throws Exception
     */
    public function post($paramName, $default = NULL) {
		return $this->read($_POST, $paramName, $default);
	}

    /**
     * @param $paramName
     * @param null $default
     * @return array|mixed|null
     * @throws Exception
     */
    public function cookie($paramName, $default = NULL) {
		return $this->read($_COOKIE, $paramName, $default);
	}

    /**
     * @param $paramName
     * @param null $default
     * @return array|mixed|null
     * @throws Exception
     */
    public function request($paramName, $default = NULL) {
		return $this->read($_GET + $_POST + $_COOKIE, $paramName, $default);
	}

    /**
     * @param $paramName
     * @param null $default
     * @return array|mixed|null
     * @throws Exception
     */
    public function getpost($paramName, $default = NULL) {
		return $this->read($_GET + $_POST, $paramName, $default);
	}

    /**
     * @param $paramName
     * @param null $default
     * @return array|mixed|null
     * @throws Exception
     */
    public function server($paramName, $default = NULL) {
		return $this->read($_SERVER, $paramName, $default);
	}

    /**
     * @param $callback
     * @return $this
     * @throws Exception
     */
    public function addFilter($callback) {
		if ( ! is_callable($callback)) {
			throw new Exception(get_class($this) . '::' . __METHOD__ . ' parameter must be a proper callback function reference.');
		}
		if ( ! in_array($callback, $this->filters)) {
			$this->filters[] = $callback;
		}
		return $this;
	}

    /**
     * @param $callback
     * @return $this
     */
    public function removeFilter($callback) {
		$this->filters = array_diff($this->filters, [$callback]);
		return $this;
	}

    /**
     * @param $val
     * @return array|mixed
     */
    protected function applyFilters($val) {
		if (is_array($val)) {
			foreach ($val as $k => $v) {
				$val[$k] = $this->applyFilters($v);
			}
		} else {
			foreach ($this->filters as $filter) {
				$val = call_user_func($filter, $val);
			}
		}
		return $val;
	}
}