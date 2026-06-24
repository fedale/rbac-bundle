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
    name: 'rbac:child:remove',
    description: 'Remove a hierarchy edge parent -> child',
)]
final class RemoveChildCommand extends Command
{
    public function __construct(
        private readonly RbacManagerInterface $manager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('parent', InputArgument::REQUIRED, 'Parent item')
            ->addArgument('child', InputArgument::REQUIRED, 'Child item');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $parent = (string) $input->getArgument('parent');
        $child = (string) $input->getArgument('child');

        $this->manager->removeChild($parent, $child);

        $io->success(sprintf('Edge "%s" -> "%s" removed.', $parent, $child));

        return Command::SUCCESS;
    }
}
