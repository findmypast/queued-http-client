queued-http-client
==================

## Summary
A queue-backed callback system is designed to add an extra layer of resiliency in communications between APIs on different systems. 

Traditionally signaling another API via a synchronous callback required the receiving system to be perpetually available to receive the message. As process flow may rely on the success of this callback, systems can get into inconsistent/out-of-sync states when a callback message fails to be received by the remote system.

Using a local queue to store callback messages allows a completely asynchronous process to be responsible for delivering the callback messages. This decoupling has two primary benefits- first, the original process is no longer responsible for assuring delivery of the callback message and handling communication errors, and second, the primary process is accelerated by not synchronously waiting for the callback to complete.

## Installation

Add `mocavo/queued-http-client` to `composer.json`.
```json
    "mocavo/queued-http-client": "dev-master"
```    
Run `composer update` to pull down QueuedHttpClient. 

Now open up `app/config/app.php` and add the service provider to your `providers` array.
```php
    'providers' => array(
        'Mocavo\QueuedHttpClient\QueuedHttpClientServiceProvider',
    )
```
and the alias:
```php
    'aliases' => array(
        'QueuedHttpClient'         => 'Mocavo\QueuedHttpClient\Facades\QueuedHttpClient',
    )
```

## Usage

_To be added_


## Processing the Callback Queue

php artisan queue:listen --queue="queued_http_requests"
