<?php

namespace Fedale\RbacBundle\Tests\Security;

use Fedale\RbacBundle\Dto\AuthItem;
use Fedale\RbacBundle\Enum\AuthItemType;
use Fedale\RbacBundle\Security\ExpressionRule;
use Fedale\RbacBundle\Tests\Fixtures\Post;
use Fedale\RbacBundle\Tests\Fixtures\StubAuthorizationChecker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage;
use Symfony\Component\Security\Core\User\InMemoryUser;

#[CoversClass(ExpressionRule::class)]
final class ExpressionRuleTest extends TestCase
{
    public function testSimpleSubjectComparison(): void
    {
        $rule = $this->rule('subject == "ok"');

        self::assertTrue($rule->execute($this->token(), $this->item(), 'ok'));
        self::assertFalse($rule->execute($this->token(), $this->item(), 'nope'));
    }

    public function testCompositeExpressionWithMapSubjectAndMethodCall(): void
    {
        $user = new InMemoryUser('u', null);
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);

        $rule = $this->rule('user === subject["author"] and subject["post"].isPublished()');

        $publishedByUser = ['author' => $user, 'post' => new Post($user, true)];
        $draftByUser = ['author' => $user, 'post' => new Post($user, false)];
        $publishedByOther = ['author' => new InMemoryUser('other', null), 'post' => new Post($user, true)];

        self::assertTrue($rule->execute($token, $this->item(), $publishedByUser));
        self::assertFalse($rule->execute($token, $this->item(), $draftByUser), 'unpublished post');
        self::assertFalse($rule->execute($token, $this->item(), $publishedByOther), 'different author');
    }

    private function rule(string $expression): ExpressionRule
    {
        return new ExpressionRule(
            new ExpressionLanguage(),
            $expression,
            new StubAuthorizationChecker(),
        );
    }

    private function token(): UsernamePasswordToken
    {
        return new UsernamePasswordToken(new InMemoryUser('u', null), 'main', ['ROLE_USER']);
    }

    private function item(): AuthItem
    {
        return new AuthItem('EDIT_POST', AuthItemType::PERMISSION);
    }
}
