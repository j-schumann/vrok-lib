# Notifications
The notification system is used to generate messages for the user, primarily
in async processes where errors/results cannot be directly displayed.
The notifications are stored in the database and can be displayed on the next
page hit / after login. Also they could be pushed to an external device, e.g.
using HTTP push, or pulled from an API to be displayed on a smartphone etc.

## Notification Entity
The notification has a type to determine how its messages are rendered and to
allow clients to filter by the type.

The notification has different text fields fields that contain a message:
* "textShort" (max. 255 chars) is required
* "textLong" is optional and the entity will fallback and return "textShort" if
  it is empty
* "html" is optional and the entity will fallback and return "textLong" if
  it is empty, which in turn will return "textShort" if it is empty too
* "title" (max. 50 chars) is optional and can contain a title to display in
  popups etc. The application should not expect the title to be set, but the
  DefaultFormatter will set one anyways.

A notification has different flags determining how it is handled:
* "dismissed" determines if this notification will be displayed to the user on
  the next occasion or if he has confirmed reading the message
* "mailable" determines if this notification can be sent to the user (if he has
  email notifications enabled), e.g. notifications containing errors that
  occurred when sending mails should not use that channel, default=false
* "pushable" determines if this notification can be pushed to the user, e.g. by
  using HTTP push (if the user has push notification configured), e.g. push
  error should not be pushed, default=false
* "pullable" determines if this notification can be pulled from the API,
  default=false
* "mailForced" determines if this notification should be sent anyways, even if
  the user has mail notifications disabled, e.g. for very important message,
  default=false

A notification belongs to an user, to allow being rendered in his preferred
locale. A parameter field can contain additional parameters the formatter
requires for rendering the message(s).

## Formatters
As notifications can contain very different content, e.g. displays names, dates
and numbers, the rendering can be customized to allow formatting of dates etc.
This taks is done by "formatters" that are fetched via an event in the
NotificationService depending on the notification type.
The formatters return rendered messages for each text field and also subject,
text body and HTML body for an email. The mail subject/body are NOT stored
in the database, this requires the job which sends the mail to be run before
any entities referenced in the parameters used for the formatter are deleted.

### Default formatter
The module attaches a DefaultFormatter to the "getNotificationFormatter" event
that is used for every type that has no custom renderer. The default formatter
sets the translation string "message.system.notification" as title and only sets
the notifications "textShort".
It expects to find a parameter named "message" and will try to translate it,
applying any further parameters to placeholders in the translated message.
As mail subject the default formatter returns the "title", as mail text body
"textLong" and as html body the value of "html", which in turn will fallback to
"textShort".

### Custom formatters
To implement a new formatter for a notification type inherit from the
Vrok\Notifications\DefaultFormatter or implement
Vrok\Notifications\FormatterInterface. Then create an event listener that checks
the notification type and returns the new formatter if it matches:

```
$events->getSharedManager()->attach(
    'Vrok\Service\NotificationService',
    'getNotificationFormatter',
    function($e) use ($serviceLocator) {
        $type = $e->getParam('type');
        if ($type === 'customType') {
            return $serviceLocator->get(CustomFormatter::class);
        }
    }
);
```

## NotificationService
Vrok\Service\NotificatioService listens on the "notification.prePersist" event
fired by the notification entity (and its Doctrine listener). It then fills the
text fields with the values returned by the formatter corresponding to the
notification type. It also pushes a job to the default queue which sends the
notification as email to the user (see "mailForced" and "mailable") and/or
pushes it to an URL if the user has configured & enabled HTTP push.
