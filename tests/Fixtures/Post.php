<?php

namespace Fedale\RbacBundle\Tests\Fixtures;

/**
 * Example subject for the expression rule tests.
 *
 * @internal
 */
final class Post
{
    public function __construct(
        private readonly mixed $author,
        private readonly bool $published,
    ) {
    }

    public function getAuthor(): mixed
    {
        return $this->author;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }
}
