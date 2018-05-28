<?php

namespace Downloader;

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

    protected function get($user, $page = 1)
    {
        $request = $this->request($user, $page);

        $lastPage = $this->getLastPage($request->getHeader('link')[0]);

        if ($lastPage !== $page && $page > $lastPage) {
            $this->consoleOutput->error('The page you are requesting is bigger than the last page, last page '.
            'should not be greater than '.$lastPage);

            exit;
        }

        $this->consoleOutput->text('This user has <info>'.$lastPage.'</info> page.');

        $data = json_decode((string) $request->getBody(), true);

        if (0 === JSON_ERROR_NONE) {
            $data = array_map(function ($item) {
                return $item['html_url'];
            }, $data);

            return $data;
        }

        $this->consoleOutput->error('Something wrong');

        exit;
    }

    protected function mkdir($directory): void
    {
        $fileSystem = new Filesystem();

        if (!\is_dir($directory)) {
            $fileSystem->mkdir($directory);
        }
    }

    protected function getLastPage($string): int
    {
        preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w]+\)|([^,[:punct:]\s]|/))#', $string, $links);

        $last = array_key_exists(1, $links[0])
            ? explode('=', explode('?', $links[0][1])[1])[1]
            : null;

        return (int) $last;
    }

    protected function request($user, $page = 1)
    {
        $uri = sprintf('/users/%s/repos?page=%d', $user, $page);

        try {
            return $this->client->request('GET', $uri);
        } catch (GuzzleException $e) {
            $this->consoleOutput->error($e->getMessage());
            exit;
        }
    }

    protected function getUserInfo($user)
    {
        $uri = sprintf('/users/%s', $user);

        try {
            return $this->client->request('GET', $uri);
        } catch (GuzzleException $e) {
            $this->consoleOutput->error($e->getMessage());
            exit;
        }
    }
}
