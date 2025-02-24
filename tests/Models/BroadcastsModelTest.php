<?php

namespace HotwiredLaravel\TurboLaravel\Tests\Models;

use HotwiredLaravel\TurboLaravel\Broadcasting\PendingBroadcast;
use HotwiredLaravel\TurboLaravel\Facades\TurboStream;
use HotwiredLaravel\TurboLaravel\Tests\TestCase;
use Illuminate\Broadcasting\Channel;
use Workbench\App\Models\Comment;
use Workbench\App\Models\Company;
use Workbench\App\Models\ReviewStatus;
use Workbench\Database\Factories\ArticleFactory;
use Workbench\Database\Factories\CommentFactory;
use Workbench\Database\Factories\CompanyFactory;
use Workbench\Database\Factories\ContactFactory;

class BroadcastsModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['turbo-laravel.queue' => false]);

        TurboStream::fake();
    }

    /** @test */
    public function manually_broadcast_append()
    {
        $article = ArticleFactory::new()->create();

        TurboStream::assertNothingWasBroadcasted();

        $article->broadcastAppend();

        TurboStream::assertBroadcasted(function (PendingBroadcast $broadcast) use ($article) {
            $this->assertCount(1, $broadcast->channels);
            $this->assertEquals('private-articles', $broadcast->channels[0]->name);
            $this->assertEquals('articles', $broadcast->target);
            $this->assertEquals('append', $broadcast->action);
            $this->assertEquals('articles._article', $broadcast->partialView);
            $this->assertEquals(['article' => $article], $broadcast->partialData);
            $this->assertNull($broadcast->targets);

            return true;
        });
    }

    /** @test */
    public function manually_append_with_overrides()
    {
        $article = ArticleFactory::new()->create();

        TurboStream::assertNothingWasBroadcasted();

        $article->broadcastAppend()
            ->to($channel = new Channel('hello'))
            ->target('some_other_target')
            ->partial('another_partial', ['lorem' => 'ipsum']);

        TurboStream::assertBroadcasted(function (PendingBroadcast $broadcast) use ($channel) {
            $this->assertCount(1, $broadcast->channels);
            $this->assertSame($channel, $broadcast->channels[0]);
            $this->assertEquals('some_other_target', $broadcast->target);
            $this->assertEquals('append', $broadcast->action);
            $this->assertEquals('another_partial', $broadcast->partialView);
            $this->assertEquals(['lorem' => 'ipsum'], $broadcast->partialData);
            $this->assertNull($broadcast->targets);

            return true;
        });
    }

    /** @test */
    public function manually_before_with_overrides()
    {
        $article = ArticleFactory::new()->create();

        TurboStream::assertNothingWasBroadcasted();

        $article->broadcastBefore('articles_card')
            ->to($channel = new Channel('hello'))
            ->partial('articles._article_card', [
                'article' => $article,
                'lorem' => 'ipsum',
            ]);

        TurboStream::assertBroadcasted(function (PendingBroadcast $broadcast) use ($article, $channel) {
            $this->assertCount(1, $broadcast->channels);
            $this->assertSame($channel, $broadcast->channels[0]);
            $this->assertEquals('articles_card', $broadcast->target);
            $this->assertEquals('before', $broadcast->action);
            $this->assertEquals('articles._article_card', $broadcast->partialView);
            $this->assertEquals(['article' => $article, 'lorem' => 'ipsum'], $broadcast->partialData);
            $this->assertNull($broadcast->targets);

            return true;
        });
    }

    /** @test */
    public function manually_after_with_overrides()
    {
        $article = ArticleFactory::new()->create();

        TurboStream::assertNothingWasBroadcasted();

        $article->broadcastAfter('article_cards')
            ->to($channel = new Channel('hello'))
            ->partial('articles._article_card', [
                'article' => $article,
                'lorem' => 'ipsum',
            ]);

        TurboStream::assertBroadcasted(function (PendingBroadcast $broadcast) use ($article, $channel) {
            $this->assertCount(1, $broadcast->channels);
            $this->assertSame($channel, $broadcast->channels[0]);
            $this->assertEquals('article_cards', $broadcast->target);
            $this->assertEquals('after', $broadcast->action);
            $this->assertEquals('articles._article_card', $broadcast->partialView);
            $this->assertEquals(['article' => $article, 'lorem' => 'ipsum'], $broadcast->partialData);
            $this->assertNull($broadcast->targets);

            return true;
        });
    }

    /** @test */
    public function manually_before_to_with_overrides()
    {
        $article = ArticleFactory::new()->create();

        TurboStream::assertNothingWasBroadcasted();

        $article->broadcastBeforeTo($channel = new Channel('hello'), 'example_dom_id_target')
            ->partial('articles._article_card', [
                'article' => $article,
                'lorem' => 'ipsum',
            ]);

        TurboStream::assertBroadcasted(function (PendingBroadcast $broadcast) use ($article, $channel) {
            $this->assertCount(1, $broadcast->channels);
            $this->assertSame($channel, $broadcast->channels[0]);
            $this->assertEquals('example_dom_id_target', $broadcast->target);
            $this->assertEquals('before', $broadcast->action);
            $this->assertEquals('articles._article_card', $broadcast->partialView);
            $this->assertEquals(['article' => $article, 'lorem' => 'ipsum'], $broadcast->partialData);
            $this->assertNull($broadcast->targets);

            return true;
        });
    }

    /** @test */
    public function manually_after_to_with_overrides()
    {
        $article = ArticleFactory::new()->create();

        TurboStream::assertNothingWasBroadcasted();

        $article->broadcastAfterTo($channel = new Channel('hello'), 'example_dom_id_target')
            ->partial('articles._article_card', [
                'article' => $article,
                'lorem' => 'ipsum',
            ]);

        TurboStream::assertBroadcasted(function (PendingBroadcast $broadcast) use ($article, $channel) {
            $this->assertCount(1, $broadcast->channels);
            $this->assertSame($channel, $broadcast->channels[0]);
            $this->assertEquals('example_dom_id_target', $broadcast->target);
            $this->assertEquals('after', $broadcast->action);
            $this->assertEquals('articles._article_card', $broadcast->partialView);
            $this->assertEquals(['article' => $article, 'lorem' => 'ipsum'], $broadcast->partialData);
            $this->assertNull($broadcast->targets);

            return true;
        });
    }

    /** @test */
    public function manually_broadcast_replace()
    {
        $article = ArticleFactory::new()->create();

        TurboStream::assertNothingWasBroadcasted();

        $article->broadcastReplace();

        TurboStream::assertBroadcasted(function (PendingBroadcast $broadcast) use ($article) {
            $this->assertCount(1, $broadcast->channels);
            $this->assertEquals(sprintf('private-%s', $article->broadcastChannel()), $broadcast->channels[0]->name);
            $this->assertEquals("article_{$article->id}", $broadcast->target);
            $this->assertEquals('replace', $broadcast->action);
            $this->assertEquals('articles._article', $broadcast->partialView);
            $this->assertEquals(['article' => $article], $broadcast->partialData);
            $this->assertNull($broadcast->targets);

            return true;
        });
    }

    /** @test */
    public function manually_broadcast_remove()
    {
        $article = ArticleFactory::new()->create();

        TurboStream::assertNothingWasBroadcasted();

        $article->broadcastRemove();

        TurboStream::assertBroadcasted(function (PendingBroadcast $broadcast) use ($article) {
            $this->assertCount(1, $broadcast->channels);
            $this->assertEquals(sprintf('private-%s', $article->broadcastChannel()), $broadcast->channels[0]->name);
            $this->assertEquals("article_{$article->id}", $broadcast->target);
            $this->assertEquals('remove', $broadcast->action);
            $this->assertNull($broadcast->partialView);
            $this->assertEquals([], $broadcast->partialData);
            $this->assertNull($broadcast->targets);

            return true;
        });
    }

    /** @test */
    public function can_auto_broadcast()
    {
        $comment = CommentFactory::new()->create();

        TurboStream::assertBroadcasted(function (PendingBroadcast $broadcast) use ($comment) {
            $this->assertCount(1, $broadcast->channels);
            $this->assertEquals('private-'.$comment->article->broadcastChannel(), $broadcast->channels[0]->name);
            $this->assertEquals('comments', $broadcast->target);
            $this->assertEquals('append', $broadcast->action);
            $this->assertEquals('comments._comment', $broadcast->partialView);
            $this->assertEquals(['comment' => $comment], $broadcast->partialData);
            $this->assertNull($broadcast->targets);

            return true;
        });
    }

    /** @test */
    public function can_auto_broadcast_with_custom_overrides()
    {
        $company = CompanyFactory::new()->create();

        TurboStream::assertBroadcasted(function (PendingBroadcast $broadcast) use ($company) {
            $this->assertCount(1, $broadcast->channels);
            $this->assertEquals('private-custom-channel', $broadcast->channels[0]->name);
            $this->assertEquals('companies', $broadcast->target);
            $this->assertEquals('prepend', $broadcast->action);
            $this->assertEquals('companies._company', $broadcast->partialView);
            $this->assertEquals(['company' => $company], $broadcast->partialData);
            $this->assertNull($broadcast->targets);

            return true;
        });
    }

    /** @test */
    public function can_configure_auto_broadcast_to_parent_model_using_a_method()
    {
        $company = Company::withoutEvents(fn () => CompanyFactory::new()->create());

        $contact = ContactFactory::new()->create([
            'company_id' => $company,
        ]);

        TurboStream::assertBroadcasted(function (PendingBroadcast $broadcast) use ($company, $contact) {
            $this->assertCount(1, $broadcast->channels);
            $this->assertEquals(sprintf('private-%s', $company->broadcastChannel()), $broadcast->channels[0]->name);
            $this->assertEquals('contacts', $broadcast->target);
            $this->assertEquals('append', $broadcast->action);
            $this->assertEquals('contacts._contact', $broadcast->partialView);
            $this->assertEquals(['contact' => $contact], $broadcast->partialData);
            $this->assertNull($broadcast->targets);

            return true;
        });
    }

    /** @test */
    public function manually_broadcast_append_targets()
    {
        $article = ArticleFactory::new()->create();

        TurboStream::assertNothingWasBroadcasted();

        $article->broadcastAppend()
            ->targets('.test_targets');

        TurboStream::assertBroadcasted(function (PendingBroadcast $broadcast) use ($article) {
            $this->assertCount(1, $broadcast->channels);
            $this->assertEquals('private-articles', $broadcast->channels[0]->name);
            $this->assertNull($broadcast->target);
            $this->assertEquals('append', $broadcast->action);
            $this->assertEquals('articles._article', $broadcast->partialView);
            $this->assertEquals(['article' => $article], $broadcast->partialData);
            $this->assertEquals('.test_targets', $broadcast->targets);

            return true;
        });
    }

    /** @test */
    public function broadcasts_on_model_touching()
    {
        $oldUpdatedAt = now()->subDays(10);

        $comment = Comment::withoutEvents(fn () => CommentFactory::new()->create([
            'updated_at' => $oldUpdatedAt,
        ]));

        $this->assertTrue($comment->fresh()->updated_at->isSameDay($oldUpdatedAt));

        TurboStream::assertNothingWasBroadcasted();

        $comment->fresh()->review()->create([
            'status' => ReviewStatus::Approved,
        ]);

        // Must have updated the parent timestamps...
        $this->assertFalse($comment->fresh()->updated_at->isSameDay($oldUpdatedAt));

        TurboStream::assertBroadcasted(function (PendingBroadcast $broadcast) use ($comment) {
            $this->assertCount(1, $broadcast->channels);
            // The comment model is configured to broadacst to the article's channel...
            $this->assertEquals('private-'.$comment->article->broadcastChannel(), $broadcast->channels[0]->name);
            $this->assertEquals(dom_id($comment), $broadcast->target);
            $this->assertNull($broadcast->targets);
            $this->assertEquals('replace', $broadcast->action);
            $this->assertEquals('comments._comment', $broadcast->partialView);
            $this->assertCount(1, $broadcast->partialData);
            $this->assertArrayHasKey('comment', $broadcast->partialData);
            $this->assertTrue($comment->is($broadcast->partialData['comment']));

            return true;
        });
    }
}
