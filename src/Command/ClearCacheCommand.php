<?php

namespace Fedale\RbacBundle\Command;

use Fedale\RbacBundle\Cache\CachedItemStorage;
use Fedale\RbacBundle\Cache\CachedRuleStorage;
use Fedale\RbacBundle\Contract\ItemStorageInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Clears only the bundle's RBAC cache keys (the item graph and the rule keys
 * attached to items) from the configured pool, without flushing the rest of
 * the pool. Useful after seeding the tables out-of-band (SQL/fixtures), which
 * bypasses the write API's automatic invalidation.
 *
 * Registered only when the cache is enabled.
 */
#[AsCommand(
    name: 'rbac:cache:clear',
    description: 'Clear the RBAC cache (item graph + rule keys)',
)]
final class ClearCacheCommand extends Command
{
    public function __construct(
        private readonly CacheItemPoolInterface $pool,
        private readonly ItemStorageInterface $items,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $keys = [CachedItemStorage::CACHE_KEY];

        foreach ($this->items->allItems() as $item) {
            if (null !== $item->ruleName) {
                $keys[] = CachedRuleStorage::CACHE_PREFIX . sha1($item->ruleName);
            }
        }

        $keys = array_values(array_unique($keys));
        $this->pool->deleteItems($keys);

        $io->success(sprintf('Cleared %d RBAC cache key(s).', \count($keys)));

        return Command::SUCCESS;
    }
}
