<?php

namespace App\Command;

use Aa\AkeneoDataLoader\Exception\LoaderFailureException;
use Aa\AkeneoDataLoader\LoaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Yaml\Yaml;

class LoadPimDataCommand extends Command
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
            ->setName('akeneo:data:load')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $stopwatch = new Stopwatch();
        $event = $stopwatch->start('load');

        try {
            foreach ($this->getAvailableFixtureDataTypes() as $dataType) {

                $style->note($dataType);
                $this->loader->load($dataType, $this->getFixtureData($dataType));
            }
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

    protected function getFixtureData(string $dataType): array
    {
        $filePath = sprintf('data/%s.yaml', $dataType);

        return Yaml::parse(file_get_contents($filePath));
    }

    private function getAvailableFixtureDataTypes(): array
    {
        return [
            'channel',
            'category',

            'attribute-group',
            'attribute',
            'attribute-option',
            'family',
            'family-variant',
            'product-model',
            'product',
        ];
    }
}
