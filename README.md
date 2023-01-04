<div align="center">
    <br>
    <br>
    <br>
    <br>
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="art/logo_dark.svg">
        <source media="(prefers-color-scheme: light)" srcset="art/logo_light.svg">
        <img
            width="60%"
            alt="The logo for laravel-magellan, which shows a small blue paper ship with the package name right beside it." src="art/logo_light.png"
        >
    </picture>
    <br>
    <br>
    <p style="font-size: 1.75rem;">A modern PostGIS toolbox for Laravel</p>
    <br>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/clickbar/laravel-magellan.svg?style=flat-square)](https://packagist.org/packages/clickbar/laravel-magellan)
[![Total Downloads](https://img.shields.io/packagist/dt/clickbar/laravel-magellan.svg?style=flat-square)](https://packagist.org/packages/clickbar/laravel-magellan)
[![GitHub Tests Action Status](https://github.com/clickbar/laravel-magellan/actions/workflows/run-tests.yml/badge.svg)](https://github.com/clickbar/laravel-magellan/actions/workflows/run-tests.yml)
[![GitHub Code Style Action Status](https://github.com/clickbar/laravel-magellan/actions/workflows/fix-styling.yml/badge.svg)](https://github.com/clickbar/laravel-magellan/actions/workflows/fix-styling.yml)
<br>
<br>
</div>

## Introduction

Every sailor needs a nice ship to travel the seven seas ⛵️

This package will have you prepared for accessing [PostGIS](https://postgis.net) related functionality within Laravel.
It was heavily inspired by [mstaack/laravel-postgis](https://github.com/mstaack/laravel-postgis), but has evolved into
much more since. Other than some slight changes, you should be familiar with Magellan very quickly.

Magellan comes with batteries included and also provides parsers/generators for GeoJSON, WKB & WKT out of the box.
Easily use all PostGIS datatypes in your migrations and avoid raw SQL to access PostGIS functions by using our Builder
functions.

Additionally `laravel-magellan` provides extensions to the Schema, Query Builder and Postgres Grammar for easy access of
PostGIS database functions like `ST_EXTENT`. It does all this without breaking compatibility to other packages,
like [tpetry/laravel-postgresql-enhanced](https://github.com/tpetry/laravel-postgresql-enhanced), which has to extend
the Grammar and Connection.

## Installation

You can install the package via composer:

```bash
composer require clickbar/laravel-magellan
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="magellan-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="magellan-config"
```

You may find the contents of the published config file here:
[config/magellan.php](config/magellan.php)

## What's included

- [x] Migration Schema Blueprints
- [x] Geometry Data Classes
- [x] WKT Generator & Parser
- [x] WKB Generator & Parser
- [x] Geojson Generator & Parser
- [x] Eloquent Model Trait
- [x] Command for automatically adding postgis trait to models
- [x] Auto Transform on insert with different projection
- [x] Geojson Request Validation Rule
- [x] Transforms Geometry for Form Requests
- [x] Most of Postgis functions as typed functions that can be used in select, where, orderBy, groupBy, having, from
- [x] Geometry and Bbox Caster
- [x] Auto Cast when using functions that return geometry or bbox
- [x] Empty Geometry Support
- [ ] Automatic Postgis Function Doc Generator
- [ ] Bbox also with postgisColumns trait (currently with cast only)
- [ ] Custom Geometry Factories & Models
- [ ] More tests
- ...

## Before you start

We highly recommend using the [laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper) from barryvdh to be
able to see everything included in the IDEs auto completion.

## Creating Tables with Postgis Columns

Laravel-magellan extends the default Schema Blueprint with all postgis functions. Since laravel has introduced basic
geometry support, all methods are prefixed with `magellan`. e.g.

```php
$table->magellanPoint('location', 4326);
```

![List of all schema methods](art/magellan_schema.png)

## Preparing the Model

In order to properly integrate everything with the model you need to to perform the following 2 steps:

1. Add the `HasPostgisColumns` trait to your model
2. Add the `$postgisColumns` array to the model

```php
protected array $postgisColumns = [
        'location' => [
            'type' => 'geometry',
            'srid' => 4326,
        ],
    ];
```

Both steps can be automated by using the following command:

```bash 
php artisan magellan:update-postgis-columns
```

The command automatically scans the database and adds the trait and the array as well.

## Using the geometry data classes

We've included data classes for the following common geometries:

- Point
- LineString
- Polygon
- MultiPoint
- MultiLineString
- MultiPolygon
- GeometryCollection

To create a geometry object manually use the suited `<GeometryClass>::make` method. e.g.

```php
$point = Point::make(51.087, 8.76);
```

You will notice, that there are 3 different make methods for the point class with different parameters:

1. make
2. makeGeodectic
3. makeEmpty

Let's take a closer look to the first two:

This is the default factory method, that can be used to fill all possible values. This method is considered the "plain"
way. You should consider using this method when you use a different projection than WGS84 (srid=4326).

```php
function make(float $x, float $y, ?float $z = null, ?float $m = null, ?int $srid = null): self
```

Most of the common web use cases use the WGS84 projection. Therefore, most of the time the terms that are used will be
latitude, longitute and altitude instead of x, y and z. To provide more comfort we have included a factory method, that
accepts those terms and automatically sets the srid to 4326.

```php
function makeGeodetic(float $latitude, float $longitude, ?float $altitude = null, ?float $m = null): self
```

When using a point class that uses the WGS84 projection, you can access the latitude, longitude and altitude with
properly named getters and setters:

- `function getLatitude(): float`
- `function setLatitude(float $latitude): void`
- `function getLongitude(): float`
- `function setLongitude(float $longitude): void`
- `function getAltitude(): ?float`
- `function setAltitude(float $altitude): void`

An exception will be thrown if you try to use this functions on a point without srid === 4326. Use the default x,y,z,m
getters and setters instead.


## Generators & Parsers
We currently provide parsers & generators for the following formats:

- EWKB
- EWKT
- GeoJSON

These are also used to format our data classes to strings, convert the return value from the database (which comes in EWKB format) and output our data to the frontend as GeoJSON for example.

> **Note**
> In the following we will use EWKB & WBK or EWKT & WKT interchangeably, even though we always use the extended version of each.

The config file allows you to customize which representation you would like to be used eg. for default string conversion on our data classes, where GeoJSON is otherwise the default.

```php
$point = Point::makeGeodetic(51.087, 8.76);

(string) $pointA;
// "{"type":"Point","coordinates":[8.76,51.087]}"
```

You can always use instances of each parser / generator and parse / generate on your own behalf.  
While Generators have to be created on demand, Parsers are already instanciated in the app container as singletons and you can use them as follows:

```php
$parser = app(WKTParser::class);

$point = $parser->parse('SRID=4326;POINT (2, 2)');

$generator = new WKBGenerator();

$generator->generate($point);
// "0101000020E610000000000000000000400000000000000040"
```

In this example we obtain an instance of the `WKTParser` and convert the string to one of our data classes. `$point` is then a valid `Point` instance and we can use any other generator eg. the `WKBGenerator` to output the `$point` in hexadecimal WKB format.

## Request Validation and Transformation

When a form request contains a geometry in Geojson format, you can use the `GeometryGeojsonRule` for validation. You can
even limit the types of allowed geometries by passing an array with the classes.

In order to properly continue working with the received geometry you can use the `TransformsGeojsonGeometry` trait to
use automatic transformation of the geojson to the proper geometry object. Therefore, return the keys in
the `geometries(): array` function.

> **Note**
> Currently nested transformation is not supported

```php
class StorePortRequest extends FormRequest
{
    use TransformsGeojsonGeometry;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'country' => ['required', 'string'],
            'location' => ['required', new GeometryGeojsonRule([Point::class])],
        ];
    }

    public function geometries(): array
    {
        return ['location'];
    }
}
```

## Interaction with the database


### Example Setup
For demo purpose we consider the following fictional scenario:
> We are a sails person with a lovely boat and a database of several ports all over the world.  
> For each port we store the name, the country and the location.

Here is the migration we use to create the ports table:
```php
Schema::create('ports', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('country');
    $table->magellanPoint('location');
    $table->timestamps();
});
```
and the model implementation:
```php
class Port extends Model
{
    use HasFactory;
    use HasPostgisColumns;

    protected $guarded = [];

    protected array $postgisColumns = [
        'location' => [
            'type' => 'geometry',
            'srid' => 4326,
        ],
    ];
}
```

### Insert/Update
Magellan geometry objects can be inserted directly as long as they are specified in the `$postgisColumns` of the affected model.
In our case, we can insert a new Port like this:

```php
Port::create([
    'name' => 'Magellan Home Port',
    'country' => 'Germany',
    'location' => Point::makeGeodetic(49.87108851299202, 8.625026485851762),
]);
```

When you want to update a geometry you can either assign the new location to the model and call `save()` or use the `update()` method on the query builder:
```php
$port->location = Point::makeGeodetic(55, 11);
$port->save();

// -- or --

Port::where('name', 'Magellan Home Port')
    ->update(['location' => Point::makeGeodetic(55, 11)]);
```

### Insert/Update with different SRID
When getting Geometries from external systems you might receive them in another projection than the one in the database.
Consider we want to insert or update a geometry with a different SRID:

```php
Port::create([
    'name' => 'Magellan Home Port',
    'country' => 'Germany',
    'location' => Point::make(473054.9891044726, 5524365.310057224, srid: 25832),
]);

// -- or --

$port = Port::find(1);
$port->location = Point::make(473054.9891044726, 5524365.310057224, srid: 25832);
$port->save();
```

Since our port table uses a point with SRID=4326, magellan will raise an error:  

> _  SRID mismatch: database has SRID 4326, geometry has SRID 25832. Consider enabling `magellan.eloquent.transform_to_database_projection` in order to apply automatic transformation_

We included an auto transform option, that directly apply ST_Transform(geometry, databaseSRID) for you.

> **Note**
> This option will only be applied when inserting/updating directly on an eloquent model.  

> **Note**
> This option will not be applied on geography columns.

### Select

When selecting data from a model that uses the `HasPostgisColumns` trait, all attributes will directly be parsed to the internal data classes:
```php
$port = Port::first();
dd($port->location);
```
```bash
Clickbar\Magellan\Data\Geometries\Point {#1732
  #srid: 4326
  #dimension: Clickbar\Magellan\Data\Geometries\Dimension {#740
    +name: "DIMENSION_2D"
    +value: "2D"
  }
  #x: 8.6250264858452
  #y: 49.87108851299
  #z: null
  #m: null
}
```
There might be cases where you also use box2d or box3d as column types. Currently, we don't support boxes within the `$postgisColumns`.
Please use the `BBoxCast` instead.

### Using Postgis functions in queries
A big part of laravel-magallan is its query feature. To provide a seamless and ready use of postgis functions, we have
included a wide scope of the typically ST-prefixed functions that can directly be used with Laravels query builder.

Whenever you want to use a postis function on a query builder, you have to use one of our builder methods. All of them are
prefixed with an `st`.  
Currently, there are the following 7:

- stSelect
- stWhere
- stOrWhere
- stOrderBy
- stGroupBy
- stHaving
- stFrom

Each of those builder methods expect to receive a _MagellanExpression_.  
A _MagellanExpression_ is a wrapper around a ST-prefixed from postgis. When sailing with magellan, you should never write 'ST_xxx' in raw sql yourself. Therefore, we have included some paddles.

Most of the ST-prefixed functions can be accessed using the static functions on the `ST` class. But enough talk, let's start
sailing (with some examples):

Assuming we have our ships current position and want to query all ports with their distance:
```php
$currentShipPosition = Point::makeGeodetic(50.107471773560114, 8.679861151457937);
$portsWithDistance = Port::select()
    ->stSelect(ST::distanceSphere($currentShipPosition, 'location'), 'distance_to_ship')
    ->get();
```

Since we cannot sail over the whole world, let's limit the distance to max. 50.000 meters:
```php
$currentShipPosition = Point::makeGeodetic(50.107471773560114, 8.679861151457937);
$portsWithDistance = Port::select()
    ->stSelect(ST::distanceSphere($currentShipPosition, 'location'), 'distance_to_ship')
    ->stWhere(ST::distanceSphere($currentShipPosition, 'location'), '<=', 50000)
    ->get();
```
Now let us order them based on the distance:
```php
$currentShipPosition = Point::makeGeodetic(50.107471773560114, 8.679861151457937);
$portsWithDistance = Port::select()
    ->stSelect(ST::distanceSphere($currentShipPosition, 'location'), as: 'distance_to_ship')
    ->stWhere(ST::distanceSphere($currentShipPosition, 'location'), '<=', 50000)
    ->stOrderBy(ST::distanceSphere($currentShipPosition, 'location'))
    ->get();
```

As you can see, using the `st`-Builder functions is as easy as using the default laravel ones. 
But what about more complex queries?
What about the convex hull of all ports grouped by the country including the area of the hull?
No problem:
```php
$hullsWithArea = Port::select('country')
    ->stSelect(ST::convexHull(ST::collect('location')), 'hull')
    ->stSelect(ST::area(ST::convexHull(ST::collect('location'))))
    ->groupBy('country')
    ->get();
```

### Autocast for bbox or geometries
In the previous section we used some postgis functions. In the first examples, the return types only consist out of scalar values. 
But in the more complex example we received a geometry as return value. 

Since "hull" is not present in our `$postgisColumns` array, we might intentionally add a cast to the query:
```php
$hullWithArea = Port::select('country')
    ->stSelect(ST::convexHull(ST::collect('location')), 'hull')
    ->stSelect(ST::area(ST::convexHull(ST::collect('location'))))
    ->groupBy('country')
    ->withCasts(['hull' => GeometryWKBCast::class]) /* <======= */
    ->first();
```
But that's **not necessary!**  
Magellan will automatically add the cast for all functions that return geometry, box2d or box3d.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover any security related issues, please email [security@clickbar.dev](mailto:security@clickbar.dev) instead
of using the issue tracker.

## Thanks

- [geo-io](https://github.com/geo-io)
- [mstaack/laravel-postgis](https://github.com/mstaack/laravel-postgis)
- [jmikola/geojson](https://github.com/jmikola/geojson)
- [jsor/doctrine-postgis](https://github.com/jsor/doctrine-postgis)

## Credits

- [Adrian](https://github.com/ahawlitschek)
- [saibotk](https://github.com/saibotk)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
