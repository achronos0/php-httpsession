# ErrorHander

Handle PHP errors.

## Example

```php

// Install PHP error handler and fatal error handler
\Useful\ErrorHandler::getErrorHandler()->install();

// Set a PSR-3 compatible logger
$oErrorHandler = \Useful\ErrorHandler::getErrorHandler();
$oErrorHandler->setLogger($oPsrLogger);

// Set a custom action to run any time there is a PHP error
$oCustomHandlers = $oErrorHandler->getHandlerSequence();
$oCustomHandlers->add('my_action', 'my_action_funcname');

// Or, convert PHP errors into exceptions
$oErrorHandler->setThrow();
```
