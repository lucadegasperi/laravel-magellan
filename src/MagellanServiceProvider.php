<?php

namespace Clickbar\Magellan;

use Clickbar\Magellan\Commands\UpdatePostgisColumns;
use Clickbar\Magellan\Data\Geometries\Geometry;
use Clickbar\Magellan\Data\Geometries\GeometryFactory;
use Clickbar\Magellan\Database\Builder\BuilderMacros;
use Clickbar\Magellan\IO\Generator\WKT\WKTGenerator;
use Clickbar\Magellan\IO\GeometryModelFactory;
use Clickbar\Magellan\IO\Parser\Geojson\GeojsonParser;
use Clickbar\Magellan\IO\Parser\WKB\WKBParser;
use Clickbar\Magellan\IO\Parser\WKT\WKTParser;
use Clickbar\Magellan\Schema\Grammars\MagellanGrammar;
use Clickbar\Magellan\Schema\MagellanBlueprint;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MagellanServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-magellan')
            ->hasConfigFile()
            ->hasMigration('enable_postgis_extension')
            ->hasCommand(UpdatePostgisColumns::class);
    }

    public function registeringPackage()
    {
        PostgresGrammar::mixin(new MagellanGrammar());
        Blueprint::mixin(new MagellanBlueprint());

        $this->registerBuilderMixin(new BuilderMacros());

        $this->app->singleton(GeometryModelFactory::class, function ($app) {
            return new GeometryFactory();
        });

        $this->app->singleton(GeojsonParser::class, function ($app) {
            return new GeojsonParser($app->make(GeometryModelFactory::class));
        });

        $this->app->singleton(WKTParser::class, function ($app) {
            return new WKTParser($app->make(GeometryModelFactory::class));
        });

        $this->app->singleton(WKBParser::class, function ($app) {
            return new WKBParser($app->make(GeometryModelFactory::class));
        });

        // Register custom Doctrine types for PostGIS
        if (class_exists('Doctrine\DBAL\Connection')) {
            DB::registerDoctrineType(\Clickbar\Magellan\DBAL\Types\GeometryType::class, 'geometry', 'geometry');
            DB::registerDoctrineType(\Clickbar\Magellan\DBAL\Types\GeographyType::class, 'geography', 'geography');
        }

        /**
         * We use the beforeExecuting event to convert Geometry objects that are passed as bindings
         * to the sql string representation using the configured generator.
         *
         * This will allow us to explicitly use another generator than the string generator, because
         * Laravel would otherwise convert the Geometry object to a string using the __toString() method.
         */
        DB::beforeExecuting(function ($query, &$bindings, $connection) {
            $postgisSchema = Config::get('magellan.schema', 'public');
            $generatorClass = Config::get('magellan.sql_generator', WKTGenerator::class);

            /** @var \Clickbar\Magellan\IO\Generator\BaseGenerator */
            $generator = new $generatorClass();

            foreach ($bindings as $key => $value) {
                if ($value instanceof Geometry) {
                    $bindings[$key] = $generator->toPostgisGeometrySql($value, $postgisSchema);
                }
            }
        });
    }

    private function registerBuilderMixin($mixin)
    {
        // See https://github.com/laravel/framework/issues/21950#issuecomment-437887175
        Builder::mixin($mixin);
        EloquentBuilder::mixin($mixin);
    }
}
