<?php

namespace Fedale\AccessControlVoterBundle\Contract;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Punto di estensione per la valutazione object-level di una regola, ispirato
 * alle "Rule" di Yii2 RBAC (una classe con execute($user, $item, $params): bool
 * agganciata a un permesso e valutata da user->can($permission, $params)).
 *
 * Una PermissionRule puo' referenziare l'id di un servizio che implementa
 * questa interfaccia tramite il campo `condition`. Il $subject passato a
 * isGranted()/#[IsGranted] e' l'equivalente del $params di can().
 *
 * NOTA: predisposizione. Il DynamicVoter legge il campo `condition` ma NON
 * invoca ancora evaluate(): la valutazione object-level e' fuori scope per ora
 * (vedi README, sezione "Ispirazione Yii2 RBAC").
 */
interface PermissionConditionInterface
{
    /**
     * @param mixed                $subject il soggetto passato a isGranted() (equivalente a $params in Yii2 can())
     * @param array<string, mixed> $context dati aggiuntivi (riservato a usi futuri)
     */
    public function evaluate(mixed $subject, TokenInterface $token, array $context = []): bool;
}
