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
    name: 'rbac:item:remove',
    description: 'Remove an auth item (cascades to its edges and assignments)',
)]
final class RemoveItemCommand extends Command
{
    public function __construct(
        private readonly RbacManagerInterface $manager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Item name to remove');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = (string) $input->getArgument('name');

        $this->manager->removeItem($name);

        $io->success(sprintf('Item "%s" removed.', $name));

        return Command::SUCCESS;
    }
}
