<?php

namespace Fedale\RbacBundle\Command;

use Fedale\RbacBundle\Contract\AssignmentStorageInterface;
use Fedale\RbacBundle\Contract\ItemStorageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Lists the items directly assigned to a user (auth_assignment), with their
 * type (role/permission). Read-only.
 */
#[AsCommand(
    name: 'rbac:list-assignments',
    description: 'List the auth items directly assigned to a user',
)]
final class ListAssignmentsCommand extends Command
{
    public function __construct(
        private readonly AssignmentStorageInterface $assignments,
        private readonly ItemStorageInterface $items,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('user', InputArgument::REQUIRED, 'User identifier');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $user = (string) $input->getArgument('user');

        $rows = [];

        foreach ($this->assignments->getAssignments($user) as $name) {
            $item = $this->items->getItem($name);

            $rows[] = [
                $name,
                null !== $item ? $item->type->value : '? (not in auth_item)',
            ];
        }

        if ([] === $rows) {
            $io->warning(sprintf('No assignments for user "%s".', $user));

            return Command::SUCCESS;
        }

        $io->table(['item', 'type'], $rows);

        return Command::SUCCESS;
    }
}
