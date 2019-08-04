<?php

namespace App\Command;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use App\Dumper\YamlDumper;
use App\PimData\PimData;
use App\PimData\PimDataRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dumps product data by family codes
 */
class DumpPimDataCommand extends Command
{
    /**
     * @var \App\Dumper\YamlDumper
     */
    private $dumper;

    /**
     * @var \App\PimData\PimDataRepository
     */
    private $repository;

    /**
     * @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface
     */
    private $pimClient;

    public function __construct(PimDataRepository $repository, YamlDumper $dumper, AkeneoPimClientInterface $pimClient)
    {
        parent::__construct();

        $this->repository = $repository;
        $this->dumper = $dumper;
        $this->pimClient = $pimClient;
    }

    protected function configure()
    {
        $this
            ->setName('akeneo:data:download')
            ->addArgument('dataTypes', InputArgument::IS_ARRAY, '', [])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $familyCodes = ['shoes', 'accessories'];

        $pimData = $this->repository->getByFamiliyCodes($familyCodes);

        $this->dumpFiles($pimData);

        $this->dumpAssets($pimData->getProductModelAssets());
        $this->dumpAssets($pimData->getProductAssets());
    }

    private function dumpFiles(PimData $pimData): void
    {
        $this->dumper->dump('data/channel.yaml', $pimData->getChannels());
        $this->dumper->dump('data/family.yaml', $pimData->getFamilies());
        $this->dumper->dump('data/family-variant.yaml', $pimData->getFamilyVariants());
        $this->dumper->dump('data/attribute.yaml', $pimData->getAttributes());
        $this->dumper->dump('data/attribute-option.yaml', $pimData->getAttributeOptions());
        $this->dumper->dump('data/attribute-group.yaml', $pimData->getAttributeGroups());
        $this->dumper->dump('data/product-model.yaml', $pimData->getProductModels());
        $this->dumper->dump('data/product.yaml', $pimData->getProducts());
        $this->dumper->dump('data/category.yaml', $pimData->getCategories());
        $this->dumper->dump('data/association-type.yaml', $pimData->getAssociationTypes());
    }

    private function dumpAssets(array $assets)
    {
        $mediaApi = $this->pimClient->getProductMediaFileApi();

        foreach ($assets as $originalPath => $dumpPath) {

            $mediaResponse = $mediaApi->download($originalPath);
            $body = $mediaResponse->getBody();

            file_put_contents('data/'.$dumpPath, $body);
        }
    }
}
