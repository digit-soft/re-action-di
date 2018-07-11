# Re-action DI
### Dependency injection container for re-action framework
####Usage
```php
//Creation
$container = new \Reaction\DI\Container([
    'definitions' => [
        'componentId' => [
            'class' => 'Component\Class\Path',
            'property_1' => 1,
            'property_2' => InstanceOf('Component\Class\Path2'),
        ],
    ],
    'singletons' => [
        'componentId2' => 'componentId2',
    ],
]);
//Set entry
$container->set('component', ['class' => '...'], [...]);
//Get entry
$cmp  = $container->get('componentId');
//Singletons
$cmp2 = $container->get('componentId2');
$cmp3 = $container->get('componentId2');
echo $cmp2 === $cmp3; //TRUE
```