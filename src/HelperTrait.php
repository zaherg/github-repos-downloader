<?php

namespace Downloader;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait HelperTrait
{
    protected function getIo(InputInterface $input, OutputInterface $output)
    {
        return new SymfonyStyle($input, $output);
    }

    protected function validateTools($tools)
    {
        return null !== (new ExecutableFinder())->find($tools);
    }

    protected function get($user, SymfonyStyle $consoleOutput)
    {
        $client = new Client([
            'base_uri' => 'https://api.github.com',
        ]);

        try {
            $uri = sprintf('/users/%s/repos', $user);

            $request = $client->request('GET', $uri);

            $data = json_decode((string) $request->getBody(), true);

            if (0 === JSON_ERROR_NONE) {
                $data = array_map(function ($item) {
                    return $item['html_url'];
                }, $data);

                return $data;
            }

            $this->$consoleOutput->error('Something wrong');
            exit;
        } catch (GuzzleException $e) {
            $this->$consoleOutput->error($e->getMessage());
            exit;
        }
    }

    protected function mkdir($directory)
    {
        $fileSystem = new Filesystem();

        if (!\mkdir($directory) && !\is_dir($directory)) {
            $fileSystem->mkdir($directory);
        }
    }
}
