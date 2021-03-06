# Cycle ORM
[![Latest Stable Version](https://poser.pugx.org/cycle/orm/version)](https://packagist.org/packages/cycle/orm)
[![Build Status](https://travis-ci.org/cycle/orm.svg?branch=master)](https://travis-ci.org/cycle/orm)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cycle/orm/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/cycle/orm/?branch=master)
[![Codecov](https://codecov.io/gh/cycle/orm/graph/badge.svg)](https://codecov.io/gh/cycle/orm)

Features:
---------
- ORM with many-to-many, many-thought-many and polymorphic relations
- bare PHP objects, ActiveRecord-like objects, [same object type for all entities](tests/ORM/Classless)
- query builder with automatic relation resolution
- eager and lazy loading, proxies support, references support
- runtime configuration with/without code-generation
- column-to-field mapping, value objects support
- single table inheritance
- works with directed graphs and cyclic graphs using IDDFS over command chains
- designed to work in long-running applications, immutable core
- dirty state, sync exceptions do not break entity map state
- supports MySQL, MariaDB, PostgresSQL, SQLServer, SQLite (full mock capability)
- supports global query constrains, UUIDs as PK, soft deletes, auto timestamps
- compatible with Doctrine Collections and Zend Hydrator
