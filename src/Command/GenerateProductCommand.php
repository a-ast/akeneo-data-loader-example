<?php

namespace App\Command;

use Aa\AkeneoDataLoader\Exception\LoaderFailureException;
use Aa\AkeneoDataLoader\LoaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;
use Traversable;

/**
 * This command demonstrates how to generate a product with dummy data.
 */
class GenerateProductCommand extends Command
{
    /**
     * @var \Aa\AkeneoDataLoader\Loader
     */
    private $loader;

    public function __construct(LoaderInterface $loader)
    {
        parent::__construct();

        $this->loader = $loader;
    }

    protected function configure()
    {
        $this
            ->setName('akeneo:data:generate')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $stopwatch = new Stopwatch();
        $event = $stopwatch->start('load');

        try {
            $this->loader->load('asset-tag', $this->getAssetTagData());
//            $this->loader->load('product', $this->getProductData(5));
        } catch (LoaderFailureException $e) {
            $this->outputException($e, $style);
        }

        $stopwatch->stop('load');

        $style->table([], [

            ['Time', ($event->getDuration() / 1000). ' s'],
            ['Memory', ($event->getMemory() / (1024*1024)). ' MB'],

        ]);
    }

    private function outputException(LoaderFailureException $e, OutputInterface $output)
    {
        $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

        if (null !== $e->getFailure()) {
            $output->write(sprintf('<error>%s</error>', (string)$e->getFailure()));
        }

        $output->writeln([]);
    }

    private function getProductData(int $count): Traversable
    {
        for ($i = 1; $i <= $count; $i++) {

            yield [
                'identifier' => 'test-'.$i,
                'family' => 'toiletries',
                'values' => [
                    'name' => [
                        [
                            'data' => 'bla',
                            'locale' => 'en_GB',
                            'scope' => null,
                        ]
                    ],
                    'image' => [
                        [
                            'data' => '@file:health.jpeg',
                            'locale' => 'en_GB',
                            'scope' => 'ecommerce',
                        ]
                    ],
                    'short_description' => [
                        [
                            'data' => 'bla',
                            'locale' => 'en_GB',
                            'scope' => 'ecommerce',
                        ]
                    ],
                    'description' => [
                        [
                            'data' => 'bla',
                            'locale' => 'en_GB',
                            'scope' => null,
                        ]
                    ],
                ],
            ];
        }
    }

    private function getAssetTagData(): Traversable
    {
        yield ['code' => 'tag1'];
        yield ['code' => 'tag2'];
        yield ['code' => 'tag86'];
    }
}
