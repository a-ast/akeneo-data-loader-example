<?php

namespace App\PimData;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;

class PimDataRepository
{
    /**
     * @var AkeneoPimClientInterface
     */
    private $pimClient;

    public function __construct(AkeneoPimClientInterface $pimClient)
    {
        $this->pimClient = $pimClient;
    }

    public function getByFamiliyCodes(array $familyCodes): PimData
    {
        $channels = $this->getChannels();
        $associationTypes = $this->getAssociationTypes();
        $families = $this->getFamilies($familyCodes);
        $familyVariants = $this->getFamilyVariants($familyCodes);

        $attributeCodes = $this->getAttributeCodes($families);
        $attributes = $this->getAttributes($attributeCodes);
        $attributeOptions = $this->getAttributeOptions($attributes);
        $attributeGroups = $this->getAttributeGroups($attributeCodes);

        $productModels = $this->getProductModels($familyCodes);
        $productModelAssets = $this->extractAssets($productModels);
        $this->replaceAssets($productModels, $productModelAssets);

        $products = $this->getProducts($familyCodes);
        $productAssets = $this->extractAssets($products);
        $this->replaceAssets($products, $productAssets);

        $categoryCodes = $this->getCategoryCodesFromProducts($products);
        $categories = $this->getCategories($categoryCodes);


        return new PimData(
            $channels,
            $families,
            $familyVariants,
            $attributes,
            $attributeOptions,
            $attributeGroups,
            $productModels,
            $productModelAssets,
            $products,
            $productAssets,
            $categories,
            $associationTypes
        );
    }

    private function getChannels(): array
    {
        $api = $this->pimClient->getChannelApi();
        $data = $api->all(100, []);

        return iterator_to_array($data);
    }

    private function getFamilies(array $familyCodes): array
    {
        $api = $this->pimClient->getFamilyApi();
        $data = iterator_to_array($api->all(100, []));

        return array_values(
            array_filter(
                $data,
                function ($value) use ($familyCodes) {

                    return in_array($value['code'], $familyCodes);

                }
            )
        );
    }

    private function getFamilyVariants(array $familyCodes): array
    {
        $api = $this->pimClient->getFamilyVariantApi();

        $data = [];

        foreach ($familyCodes as $familyCode) {

            $familyVariants = iterator_to_array(
                $api->all($familyCode, 100, [])
            );

            foreach ($familyVariants as &$familyVariant) {
                $familyVariant['family'] = $familyCode;
            }

            $data = array_merge($data, $familyVariants);
        }

        return $data;
    }

    private function getAttributeCodes(array $families): array
    {
        $attributeCodes = [];

        foreach ($families as $family) {
            $attributeCodes = array_merge(
                $attributeCodes,
                $family['attributes']
            );
        }

        return array_unique($attributeCodes);
    }

    private function getAttributes(array $attributeCodes)
    {
        $api = $this->pimClient->getAttributeApi();
        $data = iterator_to_array($api->all(100, []));

        return array_values(
            array_filter(
                $data,
                function ($value) use ($attributeCodes) {

                    return in_array($value['code'], $attributeCodes);

                }
            )
        );
    }

    private function getAttributeOptions(array $attributes)
    {
        $api = $this->pimClient->getAttributeOptionApi();

        $data = [];

        foreach ($attributes as $attribute) {

            if (false === in_array(
                    $attribute['type'],
                    ['pim_catalog_simpleselect', 'pim_catalog_multiselect']
                )) {
                continue;
            }

            $options = iterator_to_array(
                $api->all($attribute['code'], 100, [])
            );

            foreach ($options as &$option) {
                $option['attribute'] = $attribute['code'];
            }

            $data = array_merge($data, $options);
        }

        return $data;
    }

    private function getAttributeGroups(array $attributeCodes): array
    {
        $api = $this->pimClient->getAttributeGroupApi();
        $data = iterator_to_array($api->all(100, []));

        foreach ($data as $index => $item) {

            $existingAttributes = array_values(array_intersect(
                $item['attributes'],
                $attributeCodes
            ));

            $data[$index]['attributes'] = $existingAttributes;

            if (0 === count($existingAttributes)) {
                unset($data[$index]);
            }
        }

        return array_values($data);
    }

    private function getProductModels(array $familyCodes): array
    {
        $searchFilters = $this->getInFamilyFilter($familyCodes);

        $api = $this->pimClient->getProductModelApi();
        $data = iterator_to_array($api->all(100, ['search' => $searchFilters]));

        $this->removeNullsAndEmptyValues($data);
        $this->removeByKeys(
            $data,
            ['family', 'created', 'updated', 'associations', '_links', 'sort_order']
        );

        return $data;
    }

    private function getInFamilyFilter(array $familyCodes): array
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('family', 'IN', $familyCodes);
        $searchFilters = $searchBuilder->getFilters();

        return $searchFilters;
    }

    private function removeNullsAndEmptyValues(array &$data)
    {
        foreach ($data as &$item) {
            $item = array_filter(
                $item,
                function ($value, $key) {

                    return $value !== null && $value !== [];

                },
                ARRAY_FILTER_USE_BOTH
            );
        }
    }

    // ['_links', 'sort_order']
    private function removeByKeys(array &$data, array $keysToRemove)
    {
        foreach ($data as &$item) {
            $item = array_filter(
                $item,
                function ($value, $key) use ($keysToRemove) {

                    return
                        !in_array($key, $keysToRemove);

                },
                ARRAY_FILTER_USE_BOTH
            );
        }
    }

    private function extractAssets(array $data): array
    {
        $imageAttributes = ['image', 'variation_image'];
        $assets = [];

        foreach ($data as &$item) {

            foreach ($imageAttributes as $imageAttribute) {

                if (false === isset($item['values'][$imageAttribute])) {
                    continue;
                }

                foreach ($item['values'][$imageAttribute] as &$assetData) {
                    $resultedAssetName = $this->getAssetFileName($item, $assetData);
                    $assets[$assetData['data']] = $resultedAssetName;
                }
            }
        }

        return $assets;
    }

    private function getAssetFileName(array $product, array $asset): string
    {
        $code = $product['code'] ?? $product['identifier'];

        $scope = null !== $asset['scope'] ?? '_'.$asset['scope'];
        $locale = null !== $asset['locale'] ?? '_'.$asset['locale'];

        $pathPieces = explode('.', $asset['data']);
        $ext = $pathPieces[count($pathPieces) - 1];

        return sprintf('asset/%s%s%s.%s', $code, $scope, $locale, $ext);

    }

    private function replaceAssets(array &$data, array $assets)
    {
        $imageAttributes = ['image', 'variation_image'];

        foreach ($data as &$item) {

            foreach ($imageAttributes as $imageAttribute) {

                if (false === isset($item['values'][$imageAttribute])) {
                    continue;
                }

                foreach ($item['values'][$imageAttribute] as &$assetData) {

                    $assetData['data'] = '@file:'.$assets[$assetData['data']];
                    unset($assetData['_links']);
                }
            }
        }
    }

    private function getProducts(array $familyCodes): array
    {
        $searchFilters = $this->getInFamilyFilter($familyCodes);

        $api = $this->pimClient->getProductApi();
        $data = iterator_to_array($api->all(100, ['search' => $searchFilters]));

        $this->removeNullsAndEmptyValues($data);
        $this->removeByKeys(
            $data,
            ['created', 'updated', 'associations', '_links', 'sort_order']
        );

        return $data;
    }

    private function getCategoryCodesFromProducts(array $products): array
    {
        $allCategories = array_column($products, 'categories');

        return array_unique(array_merge(...$allCategories));
    }

    private function getCategories(array $categoryCodes): array
    {
        $api = $this->pimClient->getCategoryApi();
        $data = iterator_to_array($api->all(100, []));

        $categories = array_values(
            array_filter(
                $data,
                function ($value) use ($categoryCodes) {

                    return in_array($value['code'], $categoryCodes);

                }
            )
        );

        // Simply remove hierarchy to avoid fetching parent categories
        foreach ($categories as &$category) {
            $category['parent'] = 'master';
        }

        return $categories;
    }

    private function getAssociationTypes(): array
    {
        $api = $this->pimClient->getAssociationTypeApi();
        return iterator_to_array($api->all(100, []));
    }
}
