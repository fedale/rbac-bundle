<?php

namespace Fedale\AccessControlVoterBundle\Command;

use Fedale\AccessControlVoterBundle\Contract\PermissionRuleProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Stampa le regole di permesso "effettive" cosi' come le vede il bundle,
 * leggendole dal PermissionRuleProviderInterface configurato. Sorgente-agnostico:
 * funziona con Doctrine, YAML, API o qualunque provider custom. E' di sola lettura.
 *
 * Nota: col provider Doctrine vengono elencate solo le regole attive (findActive),
 * e se la cache e' attiva si vede l'elenco materializzato in cache.
 */
#[AsCommand(
    name: 'fedale:access-control-voter:list',
    description: 'List the effective permission rules from the configured provider',
)]
final class ListPermissionRulesCommand extends Command
{
    public function __construct(
        private readonly PermissionRuleProviderInterface $provider,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rows = [];

        foreach ($this->provider->findActive() as $rule) {
            $rows[] = [
                $rule->sort,
                $rule->id,
                $rule->name,
                $rule->attribute,
                $rule->subjectType ?? '*',
                $rule->condition ?? '-',
                [] === $rule->roles ? '-' : implode(',', $rule->roles),
                $rule->allow ? 'allow' : 'deny',
                $rule->active ? 'yes' : 'no',
            ];
        }

        if ([] === $rows) {
            $io->warning('No permission rules found.');

            return Command::SUCCESS;
        }

        $io->table(
            ['sort', 'id', 'name', 'attribute', 'subjectType', 'condition', 'roles', 'policy', 'active'],
            $rows,
        );

        return Command::SUCCESS;
    }
}
