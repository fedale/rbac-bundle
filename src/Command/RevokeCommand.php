<?php

namespace Fedale\RbacBundle\Command;

use Fedale\RbacBundle\Contract\RbacManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rbac:revoke',
    description: 'Revoke an item (role or permission) from a user',
)]
final class RevokeCommand extends Command
{
    public function __construct(
        private readonly RbacManagerInterface $manager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user', InputArgument::REQUIRED, 'User identifier (as returned by getUserIdentifier())')
            ->addArgument('item', InputArgument::REQUIRED, 'Item to revoke');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $user = (string) $input->getArgument('user');
        $item = (string) $input->getArgument('item');

        $this->manager->revoke($user, $item);

        $io->success(sprintf('"%s" revoked from "%s".', $item, $user));

        return Command::SUCCESS;
    }
}
