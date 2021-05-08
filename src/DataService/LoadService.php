<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 */
declare(strict_types=1);


namespace EcomDev\ProductDataPreLoader\DataService;

/**
 * Load service
 */
class LoadService
{
    /**
     * @var array
     */
    private $storage = [];

    /**
     * @var array
     */
    private $skuToId = [];

    /**
     * @var DataLoader[]
     */
    private $loaders;

    /**
     * PreloadStorage constructor.
     *
     * @param DataLoader[] $loaders
     */
    public function __construct(array $loaders)
    {
        $this->loaders = array_filter($loaders, function ($loader) {
            return $loader instanceof DataLoader;
        });
    }

    /**
     * Checks if SKU is assigned
     *
     * @param string $sku
     * @return int|null
     */
    public function skuToId(string $sku): ?int
    {
        return $this->skuToId[$sku] ?? null;
    }

    /**
     * Check if there is data available for specified product
     * and type of it
     *
     * @param int $productId
     * @param string $type
     * @return bool
     */
    public function has(int $productId, string $type): bool
    {
        return isset($this->storage[$type][$productId]);
    }

    /**
     * Retrieves data for a product that was pre-loaded
     *
     * @param int $productId
     * @param string $type
     * @return array
     */
    public function get(int $productId, string $type): array
    {
        return $this->storage[$type][$productId] ?? [];
    }

    /**
     * Preload data types
     *
     * @param string $type
     * @param ScopeFilter $filter
     * @param ProductWrapper[] $products
     */
    public function load(string $type, ScopeFilter $filter, array $products)
    {
        foreach ($this->loaders as $code => $loader) {
            if (!$loader->isApplicable($type)) {
                continue;
            }

            if (!isset($this->storage[$code])) {
                $this->storage[$code] = [];
            }

            $productsToLoad = array_diff_key($products, $this->storage[$code]);
            if (!$productsToLoad) {
                continue;
            }

            $defaultData = array_fill_keys(array_keys($productsToLoad), []);

            foreach ($productsToLoad as $productId => $adapter) {
                $this->skuToId[$adapter->getSku()] = $productId;
            }

            $this->storage[$code] += $defaultData + $loader->load($filter, $productsToLoad);
        }
    }
}