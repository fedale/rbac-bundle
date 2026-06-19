<?php

namespace Fedale\AccessControlVoterBundle\Dto;

/**
 * Value object immutabile che rappresenta una regola di permesso, sorgente
 * agnostica (Doctrine, YAML, API, ...). Specchio dell'entita.
 *
 * Mapping concettuale con Yii2 RBAC:
 *   - $attribute   <-> nome del permesso (es. updatePost)
 *   - $subjectType <-> tipo dell'oggetto su cui valutare (predisposto)
 *   - $condition   <-> "Rule" Yii2 (execute($user, $item, $params)) (predisposto)
 */
final readonly class PermissionRule
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $reason,
        public string $attribute,
        public ?string $subjectType,
        public ?string $condition,
        public array $roles,
        public bool $allow,
        public int $sort,
        public bool $active,
    ) {
        // L'attributo e' la chiave su cui il voter decide se "supportare" il
        // voto: una regola senza attributo non sarebbe mai raggiungibile e
        // indica quasi sempre un errore di configurazione. Falliamo subito,
        // alla nascita della regola (qualunque sorgente), con un messaggio
        // chiaro invece di un voto silenziosamente inerte.
        if ('' === trim($attribute)) {
            throw new \InvalidArgumentException(sprintf(
                'Permission rule "%s" must define a non-empty attribute.',
                '' === $name ? '(unnamed)' : $name,
            ));
        }
    }
}
