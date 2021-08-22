# Ceus Media Cache

This library is a storage abstraction layer, which can be used as a cache client.

It provides <acronym title="Create, Read, Update, Delete">CRUD</acronym> access to several storage backends.

[![Package version](http://img.shields.io/packagist/v/ceus-media/cache.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)
[![Monthly downloads](http://img.shields.io/packagist/dt/ceus-media/cache.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)
[![PHP version](http://img.shields.io/packagist/php-v/ceus-media/cache.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)
[![PHPStan level](https://img.shields.io/badge/PHPStan-level%207-brightgreen.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)
[![License](https://img.shields.io/packagist/l/ceus-media/cache.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)
[![Release date](https://img.shields.io/github/release-date/ceus-media/cache.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)
[![Commit date](https://img.shields.io/github/last-commit/ceus-media/cache.svg?style=flat-square)](https://packagist.org/packages/ceus-media/cache)

## Backends

You can use this layer to store and read information using these backends:

- **Database:** any database supported by PDO
- **Folder:** local files
- **FTP:** remote files via <acronym title="File Transfer Protocol">FTP</acronym>
- **IniFile:** pairs within a <acronym title="aka property or config file">INI file</acronym>
- **JsonFile:** pairs within a <acronym title="JavaScript Object Notation">JSON</acronym> file
- **Memcache:** pairs within local or remote Memcache server
- **Noop:** dummy cache without any function, fallback if no other cache backend is available, yet
- **SerialFile:** pairs within a local PHP serial file
- **SerialFolder:** PHP serial files within a local folder
- **Session:** pairs within the HTTP session
- **SSH:** remote files via <acronym title="Secure SHell">SSH</acronym>

## Usage

### Installation
This library is available as composer package:
```
composer require ceus-media/cache
```

Of cource, you will need to use composer autoloader:
```
<?php
require_once 'vendor/autoload.php';
```

### Client instance
Afterwards you can create a cache client instance:
```
use CeusMedia\Cache\Factory as CacheFactory;

$cache	= CacheFactory::createStorage( 'Folder', 'cache' );
```
This would create a new folder <code>cache</code> in the current working directory, if allowed.

### Write to cache

```
$cache->set( 'aRandomDigit', rand( 0, 9 ) );
```

Within the folder there would be file <code>aRandomDigit</code>, holding a digit between 0 and 9.

### Reading from cache

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

### Dealing with structures

To store a structure (in this case an array), you will need to apply a serialization or flattening strategy.

```
$cache->set( 'aSimpleArray', serialize( [1, 2, 3] ) );

if( $cache->has( 'aSimpleArray' ){
	$aSimpleArray = unserialize( $cache->get( 'aSimpleArray' ) );
}
```

## History

### Past
In the past, this library was called *Ceus Media Modules: Storage Engine Abstraction*, in short *CMM_SEA*.

We used and use it mostly for caching.

During migration via different <acronym title="Version Control System">VCS</acronym>s and due to corporate wide product renaming, it became *CeusMedia/Cache* on GitHub.

### Future

In the future the remote connection aspect will be extracted to another library, called CeusMedia/Storage. This library will support cloud storage as well.

The the caching aspect then will be more in focus.
There will be a new connector factory to ease connecting to cache backends.
The backend adapters will use the storage library if needed.

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
A better way is to select a stategy

**Cache Manager**

Several cache client instances can be registered on a cache manager, which is a single resource for larger projects, like an application framework.
During actions within the framework, the developer can select between several caches for different purposes.

**More backends**

- Redis
- No-SQL databases: MongoDB, CouchDB

**Custom backends**

Allow to register own cache backends.
