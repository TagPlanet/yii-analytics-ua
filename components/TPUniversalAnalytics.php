<?php
/**
 * Universal Analytics Component
 *
 * @author Philip Lawrence <philip@misterphilip.com>
 * @link http://misterphilip.com
 * @link http://tagpla.net
 * @link https://github.com/TagPlanet/yii-analytics-ua
 * @copyright Copyright &copy; 2013 Philip Lawrence
 * @license http://tagpla.net/licenses/MIT.txt
 * @version 1.0.0-alpha
 */
class TPUniversalAnalytics extends CApplicationComponent
{
    /**
     * Property ID
     */
    public $property = '';
    
    /**
     * Debug
     */
    public $debug = false;
    
    /**
     * Auto Render
     */
    public $autoRender = false;
    
    /**
     * Allowable Settings
     * @protected
     */
    protected $_settings = array(
         'alwaysSendReferrer',
                'allowAnchor',
                   'clientId',
               'cookieDomain',
              'cookieExpires',
                 'cookiePath',
         'legacyCookieDomain',
                       'name',
                 'sampleRate',
        'siteSpeedSampleRate',
    );
    
    /**
     * Settings Data
     * @protected
     */
    protected $_settingsData = array();
    
    /**
     * Called data
     * @protected
     */
    protected $_calledData = array();
    
    /**
     * Have we rendered already?
     * @protected
     */
    protected $_hasRendered = false;
    
    /**
     * Render JS
     * @return mixed
     */
    public function render( )
    {
        $js = '';
        if(!$this->_hasRendered)
        {
            if($this->property == '')
            {
                $this->_debug('No property ID for Universal Analytics - cannot render', 'error');
                return;
            }
            
            // Setup code block
            $fileName = ($this->debug) ? 'analytics_debug.js' : 'analytics.js';
            
            $js.= <<<EOT

(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/{$fileName}','ga');

EOT;
            // Do we have data for settings? 
            if(count($this->_settingsData))
            {
                $js.= "ga('create', '{$this->property}', ";
                $js.= json_encode($this->_settingsData, JSON_UNESCAPED_SLASHES);
                $js.= ");" . PHP_EOL;
            }
            else
            {
                $js.= "ga('create', '{$this->property}');" . PHP_EOL;
            }
        
            // Append a period if we have an identifier (name)
            $this->name = ($this->name) ? $this->name . '.' : '';
        
            $this->_hasRendered = true;
        }
        
        foreach($this->_calledData as $call)
        {
            $js.= "ga('{$this->name}{$call['type']}'";
            $tempData = array();
            if(count($call['data']))
            {
                foreach($call['data'] as $data)
                {
                    // What to do with the types?
                    switch(gettype($data))
                    {
                        case 'array':
                        case 'object': 
                            $tempData[] = json_encode($data, JSON_UNESCAPED_SLASHES);
                        break;
                        case 'integer':
                        case 'double':
                            $tempData[] = $data;
                        break;
                        case 'boolean':
                        case 'null':
                            $tempData[] = ($data) ? 'true' : 'false';
                        break;
                        case 'string':
                        default:
                            $tempData[] = "'" . $data . "'";
                        break;
                    }
                }
                $js.= ', ' . implode(", ", $tempData);
            }
            $js.= ")" . PHP_EOL;            
        }
        
        // Clear our the current data, so we can continue to render new items
        // .. and not repeat ourselves!
        $this->_calledData = array();
        
        if($this->autoRender)
        {
            $this->_debug('Autorender enabled, adding Universal Analytics via clientScript', 'info');
            Yii::app()->clientScript
                    ->registerScript('TPUniversalAnalytics', $js, CClientScript::POS_HEAD);
            return;
        }
        
        return $js;
    }
    
    /**
     * Call a UA method
     * @param string $method
     * @param mixed $args
     * @return bool
     */
    public function __call($method, $args)
    {
        switch($method)
        {
            case 'send':
            case 'set':
                $this->_calledData[] = array(
                    'type' => $method,
                    'data' => $args,
                );
                return true;
            break;
            default:
                // No default, for now
                $this->_debug('Invalid Universal Analytics method call: ' . $method, 'warning');
                return false;
            break;
        }
    }
    
    /**
     * Set a property (UA setting)
     * @param string $property
     * @param mixed $value
     * @return mixed
     */
    public function __set($property, $value)
    {
        if(in_array($property, $this->_settings) && $value !== null && $value != '')
        {
            $this->_settingsData[$property] = $value;
            return;
        }
    }
    
    /**
     * Get a property (UA setting)
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        if(isset($this->_settingsData[$property]))
            return $this->_settingsData[$property];
            
        return null;
    }
    
    /**
     * Output debugging, if enabled
     * @param string $message
     * @param string $level
     * @protected
     */
    protected function _debug($message = '', $level = 'info')
    {
        if($this->debug)
        {
            Yii::log($message, $level, 'ext.TPUniversalAnalytics.components.TPUniversalAnalytics');
        }
    }
}