<?php

namespace Fedale\AccessControlVoterBundle\Contract;

use Fedale\AccessControlVoterBundle\Dto\PermissionRule;

/**
 * Sorgente delle regole di permesso. Implementazioni possibili: Doctrine,
 * YAML, API, in-memory. Tutte ritornano DTO immutabili, mai entita.
 */
interface PermissionRuleProviderInterface
{
    /**
     * Tutte le regole attive, ordinate per sort ASC.
     *
     * @return iterable<PermissionRule>
     */
    public function findActive(): iterable;

    /**
     * Le regole attive per un singolo attributo, ordinate per sort ASC.
     *
     * @return iterable<PermissionRule>
     */
    public function findByAttribute(string $attribute): iterable;
}
