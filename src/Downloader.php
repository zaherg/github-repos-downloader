<?php

namespace Downloader;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Downloader extends Command
{
    use HelperTrait;

    protected $tools = 'git';
    protected $stopWatch;
    protected $consoleOutput;

    protected function configure()
    {
        $this->setName('run')
            ->addOption(
                'directory',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Set the path for the target directory where should all repos clone to.',
                'repos'
            )
            ->addOption(
                'page',
                'p',
                InputOption::VALUE_OPTIONAL,
                'The page number',
                1
            )
            ->addArgument('user', InputOption::VALUE_REQUIRED, 'The username for the github user.')
            ->setDescription('You can use this command to start download all public repos');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->consoleOutput = $this->getIo($input, $output);

        $this->client = new Client([
            'base_uri' => 'https://api.github.com',
        ]);

        if (!$this->validateTools($this->tools)) {
            $this->consoleOutput->error("Please make sure that you have installed '{$this->tools}' locally.");
            exit;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->consoleOutput->section('<info>[INFO]</info> The process will start now.');

        $user = $input->getArgument('user');

        $userInfo = $this->getUserInfo($user);

        $userData = json_decode((string) $userInfo->getBody(), true);

        $this->consoleOutput->text('This user has <info>'.$userData['public_repos'].'</info> repository');

        $repos = $this->get($user, $input->getOption('page'));

        $this->mkdir($input->getOption('directory'));

        array_map(function ($repo) use ($input) {
            $command = sprintf('cd %s && git clone %s', $input->getOption('directory'), $repo);

            (new Process())->execute($command, $this->consoleOutput);
        }, $repos);

        $finishMsg = sprintf(
            '<info>[INFO]</info> We have cloned <comment>%s repository.</comment>.',
            count($repos)
        );

        $this->consoleOutput->text($finishMsg);

        $this->consoleOutput->success('＼（＾ ＾）／ everything was successfully executed.');
    }
}
