# EntityLink

To simplify controllers and work with polymorphic associations we want to have
a general interface for displaying, linking to and searching for entities
regardless of their class. E.g. for polymorphic associations.

The ```Vrok\EntityLink\Helper``` provides functions to retrieve the URL to
search for entities of a given class, retrieve the URL to edit a given entity
or display identity information for a given entity. To do this the helper
relies on the strategy pattern to fetch implemented strategies from the
service manager.

The strategies implement the ```Vrok\EntityLink\StrategyInterface``` and are
responsible for one or more entity classes, the return the URLs or information
for one concrete class or entity.

So for example if we have log entries which reference different sources (users,
subscriptions, forum posts, ...) we can implement a strategy for each of those
possible sources, register them in the ```EntityLink\Helper``` via config and
then display links to those entities:

```php
use Vrok\EntityLink\Helper;

$helper = $serviceLocator->get(Helper::class);

foreach($logEntries as $logEntry) {
    $url = $helper->getEditUrl($logEntry->getSource());
    $text = $helper->getPresentation($logEntry->getSource());
    echo "<a href=\"$url\">$text</a>";
}
```

Helper config:
´´´php
[
    'entity_link' => [
        'strategies' => [
            // this is a service name
            'Vrok\EntityLink\UserStrategy' => [
                // one or more entity classes this strategy supports
                'Vrok\Entity\User',
            ],
        ],
    ],
],
```