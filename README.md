# Ceus Media Cache

[PSR-6: Cache Interface][psr6]
[PSR-16: Simple Cache][psr16]


This library is a storage abstraction layer, which can be used as a cache client.

It provides <acronym title="Create, Read, Update, Delete">CRUD</acronym> access to several storage backends.

[PSR-6: Cache Interface][psr6]
[PSR-16: Simple Cache][psr16]


This library is a storage abstraction layer, which can be used as a cache client.

It provides <acronym title="Create, Read, Update, Delete">CRUD</acronym> access to several storage backends.

[![Package version](http://img.shields.io/packagist/v/ceus-media/cache.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)
[![Monthly downloads](http://img.shields.io/packagist/dt/ceus-media/cache.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)
[![PHP version](http://img.shields.io/packagist/php-v/ceus-media/cache.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)
[![PHPStan level](https://img.shields.io/badge/PHPStan-level%207-brightgreen.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)
[![License](https://img.shields.io/packagist/l/ceus-media/cache.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)
[![Release date](https://img.shields.io/github/release-date/CeusMedia/Cache.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)
[![Commit date](https://img.shields.io/github/last-commit/CeusMedia/Cache.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)

## About

This library is a storage abstraction layer, which can be used as a cache client.

It provides <acronym title="Create, Read, Update, Delete">CRUD</acronym> access to several storage backends.

### Backends

You can use this layer to store and read information using these backends:

- **Database:** any database supported by PDO
- **Folder:** local files
- **IniFile:** pairs within a <acronym title="aka property or config file">INI file</acronym>
- **JsonFile:** pairs within a <acronym title="JavaScript Object Notation">JSON</acronym> file
- **Memcache:** pairs within local or remote Memcache server
- **Memory:** pairs within local memory, not persistent
- **Noop:** dummy cache without any function, fallback if no other cache backend is available, yet
- **SerialFile:** pairs within a local PHP serial file
- **SerialFolder:** PHP serial files within a local folder
- **Session:** pairs within the HTTP session


## Installation
This library is available as composer package:
```
composer require ceus-media/cache
```

Of cource, you will need to use composer autoloader:
```
<?php
require_once 'vendor/autoload.php';
```

## Usage

### PSR-16 - Simple cache

#### Create cache
```
use CeusMedia\Cache\Factory as CacheFactory;

$cache	= CacheFactory::createStorage( 'Folder', __DIR__.'/cache' );
```
This would create a new folder <code>cache</code> in the current working directory, if allowed.

#### Write to cache

```
$cache->set( 'aRandomDigit', rand( 0, 9 ) );
```

Within the folder there would be file <code>aRandomDigit</code>, holding a digit between 0 and 9.

#### Reading from cache

You can later read this information, again:
```
$digit = $cache->get( 'aRandomDigit' );
if( NULL !== $digit ){
	//  cache hit
} else {
	//  cache miss
}
```
As you can see, the result is <code>NULL</code>, if the requested information is not cached (cache miss).

You can check if an information is cached:
```
if( $cache->has( 'aRandomDigit' ){
	//  cache hit
} else {
	//  cache miss
}
```

### PSR-6 - Cache Pool

As defined in [PHP-Fig][phpfig]s [PSR-6][psr6] there is a cache pool with items.

#### Create cache
```
use CeusMedia\Cache\CachePoolFactory;

$pool	= CachePoolFactory::createPool( 'Folder', __DIR__.'/cache' );
```
This would create a new folder <code>cache</code> in the current working directory, if allowed.

#### Write to cache
```
// get an existing or empty item
$item	= $pool->getItem( 'datetime' );

// set the new (or first) value
$item->set( date( DATE_ATOM ) );

// persist item in pool
$pool->save( $item );
```

Within the folder there would be file <code>datetime</code>, holding a timestamp.

#### Reading from cache

You can later read this information, again:
```
$item	= $pool->getItem( 'datetime' );
if( $item->isHit() ){
	$date	= $item->get();
	// ...
} else {
	// ...
}
```


## History

### Past
In the past, this library was called *Ceus Media Modules: Storage Engine Abstraction*, in short *CMM_SEA*.

We used it for caching, mostly.

During migration via different <acronym title="Version Control System">VCS</acronym>s and due to corporate wide product renaming, it became *CeusMedia/Cache* on GitHub.

Since a migration to implement [PHP-Fig][phpfig]s cache releated PSRs, there are now two ways to use this library.

Slow storages have been removed to keep an eye on performance.

#### Ideas

**Connector Factory**

Replace the current resource strings, used on connecting a cache backend, by connector objects.
For each backend there will be a connector class.
A factory will ease the creation of these connectors.

**Layered Caches**

A cache client instance can have several backends, which are used in a defined order.
This way, a slow cache backend (like a database or file based cache) can be wrapped by a faster cache backend (like a local memcache server).
There are many interesting use cases.

**Flattening Strategy**

Since some cache backends cannot store structured data, a flattening strategy is needed.
At the moment, each backend implements its own strategy.
A better way is to select a stategy.

**Cache Manager**

Several cache client instances can be registered on a cache manager, which is a single resource for larger projects, like an application framework.
During actions within the framework, the developer can select between several caches for different purposes.

**More backends**

- Redis
- No-SQL databases: MongoDB, CouchDB

**Custom backends**

Allow to register own cache backends.

[psr6]: https://www.php-fig.org/psr/psr-6/
[psr16]: https://www.php-fig.org/psr/psr-16/
