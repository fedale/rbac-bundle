<?php

namespace Fedale\RbacBundle\Command;

use Fedale\RbacBundle\Contract\RbacManagerInterface;
use Fedale\RbacBundle\Enum\AuthItemType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rbac:item:add',
    description: 'Create an auth item (role or permission)',
)]
final class AddItemCommand extends Command
{
    public function __construct(
        private readonly RbacManagerInterface $manager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Item name (e.g. EDIT_INVOICE or ROLE_EDITOR)')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'role | permission', 'permission')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Description')
            ->addOption('rule', null, InputOption::VALUE_REQUIRED, 'Name of the auth_rule to attach');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $type = AuthItemType::tryFrom((string) $input->getOption('type'));

        if (null === $type) {
            $io->error('Invalid --type: use "role" or "permission".');

            return Command::INVALID;
        }

        $this->manager->addItem(
            (string) $input->getArgument('name'),
            $type,
            $input->getOption('description'),
            $input->getOption('rule'),
        );

        $io->success(sprintf('Item "%s" (%s) created.', $input->getArgument('name'), $type->value));

        return Command::SUCCESS;
    }
}
