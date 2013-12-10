yii-analytics-ua
================

An Universal Analytics component for the Yii framework. This is for the newer version of Google Analytics (analytics.js).
Below will give you an overview of how to install and use the basics of this extension.

## Installation

#### Step 1: Upload the files
The first step is straightforward; simply unzip the files from the [latest download](https://github.com/TagPlanet/yii-analytics-ua/archive/development.zip) 
into a directory under extensions called TPUniversalAnlaytics. You should now be able to navigate to `protected/extensions/TPUniversalAnlaytics/components` and see a file called `TPUniversalAnalytics.php`.

#### Optional: Git Submodule
You can also install this as a [Git submodule](http://git-scm.com/book/en/Git-Tools-Submodules). This can be done with running the following 
command in the root of your project, where the path to the extensions folder (`protected/extensions`) might need updating.
```Shell
git submodule add git://github.com/TagPlanet/yii-analytics-ua.git protected/extensions/TPUniversalAnalytics
```
By using a submodule, this will guarantee you'll have access to the latest version at all times.

#### Step 2: Add in configuration
Within your configuration files (usually found under `/protected/config/`) there is the "components" section. Just like your db and cache 
components, we'll need to add in our own configuration for this. Add in the following code within the components section:
```php
'universalAnalytics' => array(
    'class' =>'ext.TPUniversalAnalytics.components.TPUniversalAnalytics',
    'property' => 'UA-########-#',
),
```

#### Step 3: (Optional) Add in auto-render
In order for the Universal Analytics component to automatically render the code in the header, you must have the following two items configured:
 1.  *Configuration file* - within the universalAnalytics configuration, you must include:
```php
'universalAnalytics' => array(
    'class' =>'ext.TPUniversalAnalytics.components.TPUniversalAnalytics',
    'property' => 'UA-########-#',
    'autoRender' => true,
),
```
 1.  *Controllers* - your controllers must have the following code:
```php
protected function afterRender($view, &$output)
{
    parent::afterRender($view, $output);
    Yii::app()->universalAnalytics->render();
}
```
You can place this either within `protected/components/Controller.php` (or whichever Controller you are overloading) _or_ within 
every single one of your controllers. In the event that you already have the method `afterRender` within your controllers, simple 
add in the following line to it, before the return statement:
```php
Yii::app()->universalAnalytics->render();
```

If you opt to not automatically render the JavaScript code, `Yii::app()->universalAnalytics->render()` will return JavaScript code 
(you'll need to wrap `<script> ... </script>` around it) for you to print on page. More on this later.

## Configuration Options
This component allows for some flexibility within the configuration section. Below are all of the allowed configuration variables:
  * **class** - The TPUniversalAnalytics class location 
    * Required: yes
    * Type: string
    * Default: `ext.TPUniversalAnalytics.components.TPUniversalAnalytics`
  * **property** - Your Universal Analytics property ID
    * Required: yes
    * Type: string
    * Format:  `UA-########-#`
    * Default: (none)
  * **autoRender** - Automatically render the Universal Analytics code in the head. If you do set this to true, you will need to update your controller's `afterRender` method
    * Required: no
    * Type: boolean
    * Recommend Setting: true
    * Default: false   
  * **debug** - Changes Google's JS to their [analytics_debug.js file](https://developers.google.com/analytics/resources/articles/gaTrackingTroubleshooting#gaDebug) and includes Yii debugging
    * Required: no
    * Type: boolean
    * Recommend Setting: false in production, true in development
    * Default: false

Also allowed within the configuration options are any of the [create-only properties](https://developers.google.com/analytics/devguides/collection/analyticsjs/field-reference#create) 
that Universal Analytics allows within the create call. Simply use the field name, as specified within Google's documentation. Below is a configuration example with some of the create-only properties used:
```php
'universalAnalytics' => array(
    'class' =>'ext.TPUniversalAnalytics.components.TPUniversalAnalytics',
    'property' => 'UA-########-#',
    'autoRender' => true,
    'cookieDomain' => 'none',
    'legacyCookieDomain' => 'none',
    'sampleRate' => 80,
),
```

The above example would render the following on-page code:
```javascript
ga('create', 'UA-########-#', {
    "cookieDomain": "none",
    "legacyCookieDomain": "none",
    "sampleRate": 80
});
```
 
## Usage

#### Accessing Universal Analytics in Yii
Since the Universal Analytics extension is setup as a component, you can simply use the following call to access the extension:
```php
Yii::app()->universalAnalytics
```

#### Calling a Universal Analytics Method
In Universal Analytics, you call various [methods](https://developers.google.com/analytics/devguides/collection/analyticsjs/method-reference) 
to change the settings and values that are passed to Google's severs. For the Yii extension, you use a similar setup. 
All you need to do is call the name of the method, and pass in the parameters (not as an array!)

##### A simple example
A normal call to set a custom variable in JavaScript:
```javascript
ga('send', 'event', 'foobar category', 'ze action!');
```

Within a controller or view, you can do the same as above via the extension:
```php
Yii::app()->universalAnalytics->send('event', 'foobar category', 'ze action!');
```

##### A more complex example
Sometimes you need to push quite a bit of data into Universal Analytics. With this extension, that is fairly easy.

For an example, let's push in a transaction when the user completes a checkout via the `checkout` action within the 
`cart` controller. You can see within this example that Yii's relational records can be used (see: `$order->Store->Name`)

_`protected/controllers/cart.php`_:
```
<?php

// ...

protected function afterRender($view, &$output)
{
    parent::afterRender($view, $output);
    Yii::app()->universalAnalytics->render();
}

public function actionCheckout( )
{
    // Do some processing here (let's say $order has information about the order)
    if($order->isComplete)
    {
        // Include the ecommerce plugin (newly required by Google!)
        Yii::app()->universalAnalytics->require('ecommerce', 'ecommerce.js');
        
        // Start the transaction using $order's information
        Yii::app()->universalAnalytics->ecommerce_addTransaction(array( 
                        'id' => $order->OrderID,
               'affiliation' => $order->Store->Name,
                   'revenue' => $order->Total,
                  'shipping' => $order->ShippingAmount,
                       'tax' => $order->Tax,
        ));
        
        // Loop through each item that the order had
        foreach($order->Items as $item)
        {
            // And add in the item to pass to Universal Analytics
            Yii::app()->universalAnalytics->ecommerce_addItem(array( 
                        'id' => $order->OrderID,
                       'sku' => $item->SKU,
                      'name' => $item->Name,
                  'category' => $item->Category->Name,
                  'quantity' => $item->Quantity,
            ));
        }
        
        // Finally, call _trackTrans to finalize the order.
        Yii::app()->googleAnalytics->ecommerce_send();
    }
}
```

Do note that Universal Analyics JavaScript uses `ecommerce:send` and this component uses `ecommerce_send`. 
Any method that has a colon within the name should have the colon replaced with an underscore.

#### Disallowed methods
Since Universal Analytics uses less [methods](https://developers.google.com/analytics/devguides/collection/analyticsjs/method-reference) than Google Analytics, 
most are available via this component. The exceptions are those that start with `get`.

#### About the methods
It should be noted that methods are output in a FIFO (First In, First Out) method. This is important because some methods 
such as [set](https://developers.google.com/analytics/devguides/collection/analyticsjs/method-reference#set) need to be pushed before 
a [send event](https://developers.google.com/analytics/devguides/collection/analyticsjs/method-reference#send) in order for the data to be sent in properly

#### Rendering the Universal Analytics Output
Rendering within the component depends on the way you configured it.

##### If Auto Rendering is enabled
If auto rendering is enabled and you followed the configuration steps (adding `afterRender` call to your controllers) 
then there is nothing else for you to do to render the JavaScript code.

##### If Auto Rendering is disabled
If you have auto rendering disabled (which it is by default), then you can call the `render()` method within your views 
which will return the rendered Universal Analytics JavaScript code. In almost all cases, you should use this in your main layout views (e.g. `protected/views/layouts/main.php`)
```html
<script type="text/JavaScript">
<?php echo Yii::app()->universalAnalytics->render(); ?>
</script>
```

*Note*: The `render` method does not wrap `<script></script>` tags around the output. If auto-rendering is enabled, 
[`CClientScript::registerScript`](http://www.yiiframework.com/doc/api/1.1/CClientScript#registerScript-detail) is utilized, otherwise JavaScript code is returned.
