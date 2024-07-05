<?php

namespace MauticPlugin\MZagmajsterHandyToolsBundle\Command;

use MauticPlugin\MZagmajsterHandyToolsBundle\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI Command to send Slack notification about new mautic version of Mautic.
 */
class ChangePasswordCommand extends Command
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        parent::__construct();
        $this->userService = $userService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('mz:change:password')
            ->setDescription('Change user password')
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'User ID')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'New user password.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command to change Mautic user password.

<info>php %command.full_name% --user-id=123</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId   = $input->getOption('user-id');
        $password = $input->getOption('password');
        if (!$userId) {
            $io->error('User ID is required');

            return Command::FAILURE;
        }

        $helper = $this->getHelper('question');
        if (null === $password) {
            $question = new Question('Please enter the password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $helper->ask($input, $output, $question);
        }

        try {
            $this->userService->changePassword(
                intval($userId),
                $password
            );
        } catch (\InvalidArgumentException $e) {
            $io->error(sprintf('User with ID: %s not found.', $userId));

            return Command::FAILURE;
        }

        $io->success(sprintf('Password updated.'));

        return Command::SUCCESS;
    }
}
