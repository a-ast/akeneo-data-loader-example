<?php

namespace App\Command;

use Aa\AkeneoDataLoader\Exception\LoaderValidationException;
use Aa\AkeneoDataLoader\Loader;
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

    /**
     * @var string
     */
    private $importDir;

    public function __construct(LoaderInterface $loader, string $importDir)
    {
        parent::__construct();

        $this->loader = $loader;
        $this->importDir = $importDir;
    }

    protected function configure()
    {
        $this
            ->setName('akeneo:data:generate')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = $this->getProductData(10);

        $style = new SymfonyStyle($input, $output);
        $stopwatch = new Stopwatch();
        $event = $stopwatch->start('load');

        try {
            $this->loader->load('product', $data);
        } catch (LoaderValidationException $e) {
            $this->outputException($e, $style);
        }

        $stopwatch->stop('load');

        $style->table([], [

            ['Time', ($event->getDuration() / 1000). ' s'],
            ['Memory', ($event->getMemory() / (1024*1024)). ' MB'],

        ]);
    }

    private function outputException(LoaderValidationException $e, OutputInterface $output)
    {
        $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

        foreach ($e->getValidationErrors() as $error) {
            $output->writeln(
                sprintf(
                    '<error>%s: %s</error>',
                    $error['code'] ?? '',
                    $error['message'] ?? ''
                )
            );
        }
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
}
