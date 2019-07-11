<?php

namespace App\Model;

class FetchedPimData
{
    /**
     * @var array
     */
    private $channels;

    /**
     * @var array
     */
    private $families;

    /**
     * @var array
     */
    private $familyVariants;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var array
     */
    private $attributeOptions;

    /**
     * @var array
     */
    private $attributeGroups;

    /**
     * @var array
     */
    private $productModels;

    /**
     * @var array
     */
    private $productModelAssets;

    /**
     * @var array
     */
    private $products;

    /**
     * @var array
     */
    private $productAssets;

    /**
     */
    public function __construct(
        array $channels,
        array $families,
        array $familyVariants,
        array $attributes,
        array $attributeOptions,
        array $attributeGroups,
        array $productModels,
        array $productModelAssets,
        array $products,
        array $productAssets
    ) {
        $this->channels = $channels;
        $this->families = $families;
        $this->familyVariants = $familyVariants;
        $this->attributes = $attributes;
        $this->attributeOptions = $attributeOptions;
        $this->attributeGroups = $attributeGroups;
        $this->productModels = $productModels;
        $this->productModelAssets = $productModelAssets;
        $this->products = $products;
        $this->productAssets = $productAssets;
    }


    public function getChannels(): array
    {
        return $this->channels;
    }

    public function getFamilies(): array
    {
        return $this->families;
    }

    public function getFamilyVariants(): array
    {
        return $this->familyVariants;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttributeOptions(): array
    {
        return $this->attributeOptions;
    }

    public function getAttributeGroups(): array
    {
        return $this->attributeGroups;
    }

    public function getProductModels(): array
    {
        return $this->productModels;
    }

    public function getProductModelAssets(): array
    {
        return $this->productModelAssets;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getProductAssets(): array
    {
        return $this->productAssets;
    }
}
