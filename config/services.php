<?php

declare(strict_types=1);

/**
 * Service container configuration
 *
 * @param \App\Core\Container $container
 * @return void
 */
return function (\App\Core\Container $container): void {
    // Example bindings (customize for your app):
    //
    // $container->bind(CacheInterface::class, fn ($c) => new ApcuCache());
    // $container->singleton(DataProvider::class, fn ($c) => new DataProvider(
    //     $c->get('fixtures_path')
    // ));
    // $container->addTag(DataProvider::class, \App\Core\ContainerInterface::TAG_BOOTABLE);
};
