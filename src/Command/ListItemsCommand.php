<?php

namespace Fedale\RbacBundle\Command;

use Fedale\RbacBundle\Contract\ItemStorageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Lists the auth items (roles and permissions) with their type, attached rule
 * and direct children. Read-only, source-agnostic.
 */
#[AsCommand(
    name: 'rbac:list-items',
    description: 'List RBAC auth items (roles and permissions) and their children',
)]
final class ListItemsCommand extends Command
{
    public function __construct(
        private readonly ItemStorageInterface $items,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rows = [];

        foreach ($this->items->allItems() as $item) {
            $children = $this->items->getChildren($item->name);

            $rows[] = [
                $item->name,
                $item->type->value,
                $item->ruleName ?? '-',
                [] === $children ? '-' : implode(', ', $children),
            ];
        }

        if ([] === $rows) {
            $io->warning('No auth items found.');

            return Command::SUCCESS;
        }

        $io->table(['name', 'type', 'rule', 'children'], $rows);

        return Command::SUCCESS;
    }
}
