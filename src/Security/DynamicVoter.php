<?php

namespace Fedale\AccessControlVoterBundle\Security;

use Fedale\AccessControlVoterBundle\Config\VoterConfig;
use Fedale\AccessControlVoterBundle\Contract\PermissionRuleProviderInterface;
use Fedale\AccessControlVoterBundle\Dto\PermissionRule;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter nativo Symfony che porta le regole DB-driven nel flusso
 * #[IsGranted] / isGranted() / AccessDecisionManager.
 *
 * Semantica (coerente col firewall del bundle padre):
 *   - super_admin_role -> ACCESS_GRANTED (short-circuit);
 *   - first-match-wins sulle regole dell'attributo, ordinate per sort ASC: la
 *     prima regola "applicabile" (ruoli soddisfatti) decide via il suo allow;
 *   - attributo senza alcuna regola attiva -> supports() = false -> ABSTAIN
 *     (lasciamo decidere gli altri voter).
 *
 * Predisposizione object-level: subjectType e condition vengono letti ma il
 * match su $subject e l'esecuzione della condizione sono NO-OP per ora
 * (vedi PermissionConditionInterface e README).
 */
final class DynamicVoter extends Voter
{
    public function __construct(
        private readonly PermissionRuleProviderInterface $provider,
        private readonly Security $security,
        private readonly VoterConfig $config,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$this->config->enabled) {
            return false;
        }

        // Attributo-scoped: supportiamo il voto solo per attributi che hanno
        // almeno una regola attiva. Cosi' i ruoli standard (ROLE_*) non
        // rientrano qui e isGranted($role) — usato sotto per la gerarchia —
        // non innesca ricorsione su questo voter.
        foreach ($this->provider->findActive() as $rule) {
            if ($rule->attribute === $attribute) {
                return true;
            }
        }

        return false;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // Il super admin bypassa qualunque regola.
        if (
            '' !== $this->config->superAdminRole
            && $this->security->isGranted($this->config->superAdminRole)
        ) {
            return true;
        }

        foreach ($this->provider->findByAttribute($attribute) as $rule) {
            // TODO object-level: qui andrebbero valutati $rule->subjectType
            // contro $subject e $rule->condition via PermissionConditionInterface.
            // Per ora sono no-op: la regola si applica in base ai soli ruoli.
            if ($this->matchesRoles($rule, $token)) {
                return $rule->allow;
            }
        }

        // Esistono regole per l'attributo (supports() = true) ma nessuna e'
        // applicabile all'utente corrente: nega.
        return false;
    }

    private function matchesRoles(PermissionRule $rule, TokenInterface $token): bool
    {
        if ([] === $rule->roles) {
            return true;
        }

        foreach ($rule->roles as $role) {
            // isGranted riusa la role_hierarchy configurata nell'app.
            if ($this->security->isGranted($role)) {
                return true;
            }
        }

        return false;
    }
}
