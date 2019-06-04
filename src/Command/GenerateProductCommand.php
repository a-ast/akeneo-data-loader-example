<?php

namespace App\Command;

use Aa\AkeneoDataLoader\Exception\LoaderValidationException;
use Aa\AkeneoDataLoader\Loader;
use Aa\AkeneoDataLoader\LoaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        try {

            $data = [];

            for ($i = 1; $i <= 1000; $i++) {

                $data[] = [
                    'identifier' => 'test-'.$i,
                    'family' => 'toiletries',
                    'values' => [
                        'name' => [[
                            'data' => 'bla',
                            'locale' => 'en_GB',
                            'scope' => null,
                        ]],
                        'short_description' => [[
                            'data' => 'bla',
                            'locale' => 'en_GB',
                            'scope' => 'ecommerce',
                        ]],
                        'description' => [[
                            'data' => 'bla',
                            'locale' => 'en_GB',
                            'scope' => null,
                        ]],
                    ],
                ];
            }

            $this->loader->load('product', $data);

            $output->writeln([PHP_EOL, 'Generating finished.']);

        } catch (LoaderValidationException $e) {

            $this->outputException($e, $output);

            exit(1);
        }
    }

    protected function outputException(LoaderValidationException $e, OutputInterface $output)
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
}
