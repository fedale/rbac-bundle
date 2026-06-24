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
 * STATIC diagnostics: check whether an item is reachable for a user by
 * walking the hierarchy up from their auth_assignment entries.
 *
 * WARNING: this is a reachability-only check. It does NOT evaluate auth_rule
 * (which need a token/subject at runtime) nor the role_hierarchy applied to
 * the token: for the real decision use can()/isGranted() in an HTTP context.
 */
#[AsCommand(
    name: 'rbac:check',
    description: 'Statically check if an item is reachable for a user (rules NOT evaluated)',
)]
final class CheckAccessCommand extends Command
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
        $this->addArgument('item', InputArgument::REQUIRED, 'Item name (role or permission)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $user = (string) $input->getArgument('user');
        $target = (string) $input->getArgument('item');

        $seeds = array_fill_keys($this->assignments->getAssignments($user), true);

        $reachable = $this->reachable($target, $seeds);

        $io->writeln(sprintf(
            '%s -> %s: <%s>%s</>',
            $user,
            $target,
            $reachable ? 'info' : 'comment',
            $reachable ? 'REACHABLE' : 'not reachable',
        ));

        $io->note('Static reachability only: auth_rule conditions and token role_hierarchy are NOT applied.');

        return Command::SUCCESS;
    }

    /**
     * @param array<string, true> $seeds
     */
    private function reachable(string $target, array $seeds): bool
    {
        $stack = [$target];
        $visited = [];

        while ([] !== $stack) {
            $name = array_pop($stack);

            if (isset($visited[$name])) {
                continue;
            }
            $visited[$name] = true;

            if (isset($seeds[$name])) {
                return true;
            }

            foreach ($this->items->getParents($name) as $parent) {
                $stack[] = $parent;
            }
        }

        return false;
    }
}
