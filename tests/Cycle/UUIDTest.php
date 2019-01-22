<?php
declare(strict_types=1);/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */namespace Spiral\Cycle\Tests;

use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\Uuid;
use Spiral\Cycle\Heap\Heap;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select;
use Spiral\Cycle\Tests\Fixtures\Comment;
use Spiral\Cycle\Tests\Fixtures\SortByMsgConstrain;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Fixtures\UUIDMapper;
use Spiral\Cycle\Tests\Traits\TableTrait;
use Spiral\Cycle\Transaction;

abstract class UUIDTest extends BaseTest
{
    private $u1;
    private $u2;
    private $c1;
    private $c2;
    private $c3;

    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'      => 'string(36),primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        $this->makeTable('comment', [
            'id'      => 'string(36),primary',
            'user_id' => 'string(36)',
            'message' => 'string'
        ]);

        $this->makeFK('comment', 'user_id', 'user', 'id');

        // seed
        $this->u1 = Uuid::uuid4()->toString();
        $this->u2 = Uuid::uuid4()->toString();
        $this->c1 = Uuid::uuid4()->toString();
        $this->c2 = Uuid::uuid4()->toString();
        $this->c3 = Uuid::uuid4()->toString();

        $this->getDatabase()->table('user')->insertMultiple(
            ['id', 'email', 'balance'],
            [
                [$this->u1, 'hello@world.com', 100],
                [$this->u2, 'another@world.com', 200],
            ]
        );

        $this->getDatabase()->table('comment')->insertMultiple(
            ['id', 'user_id', 'message'],
            [
                [$this->c1, $this->u1, 'msg 1'],
                [$this->c2, $this->u1, 'msg 2'],
                [$this->c3, $this->u1, 'msg 3'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class    => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => UUIDMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'comments' => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ]
                ]
            ],
            Comment::class => [
                Schema::ALIAS       => 'comment',
                Schema::MAPPER      => UUIDMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'message'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
                Schema::CONSTRAINS  => [Select\Source::DEFAULT_CONSTRAIN => SortByMsgConstrain::class]
            ]
        ]));
    }

    public function testFetchRelation()
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.email');

        $this->assertEquals([
            [
                'id'       => $this->u2,
                'email'    => 'another@world.com',
                'balance'  => 200.0,
                'comments' => [],
            ],
            [
                'id'       => $this->u1,
                'email'    => 'hello@world.com',
                'balance'  => 100.0,
                'comments' => [
                    [
                        'id'      => $this->c1,
                        'user_id' => $this->u1,
                        'message' => 'msg 1',
                    ],
                    [
                        'id'      => $this->c2,
                        'user_id' => $this->u1,
                        'message' => 'msg 2',
                    ],
                    [
                        'id'      => $this->c3,
                        'user_id' => $this->u1,
                        'message' => 'msg 3',
                    ],
                ],
            ],
        ], $selector->fetchData());
    }

    public function testFetchRelationInload()
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments', ['method' => Select\JoinableLoader::INLOAD])->orderBy('user.email');

        $this->assertEquals([
            [
                'id'       => $this->u2,
                'email'    => 'another@world.com',
                'balance'  => 200.0,
                'comments' => [],
            ],
            [
                'id'       => $this->u1,
                'email'    => 'hello@world.com',
                'balance'  => 100.0,
                'comments' => [
                    [
                        'id'      => $this->c1,
                        'user_id' => $this->u1,
                        'message' => 'msg 1',
                    ],
                    [
                        'id'      => $this->c2,
                        'user_id' => $this->u1,
                        'message' => 'msg 2',
                    ],
                    [
                        'id'      => $this->c3,
                        'user_id' => $this->u1,
                        'message' => 'msg 3',
                    ],
                ],
            ],
        ], $selector->fetchData());
    }

    public function testAccessRelated()
    {
        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        list($b, $a) = $selector->load('comments')->orderBy('user.email')->fetchAll();

        $this->assertInstanceOf(Collection::class, $a->comments);
        $this->assertInstanceOf(Collection::class, $b->comments);

        $this->assertCount(3, $a->comments);
        $this->assertCount(0, $b->comments);

        $this->assertEquals('msg 1', $a->comments[0]->message);
        $this->assertEquals('msg 2', $a->comments[1]->message);
        $this->assertEquals('msg 3', $a->comments[2]->message);
    }

    public function testNoWrite()
    {
        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('comments')->orderBy('user.email')->fetchAll();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testCreateWithRelations()
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->comments->add(new Comment());
        $e->comments->add(new Comment());

        $e->comments[0]->message = 'msg A';
        $e->comments[1]->message = 'msg B';

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(3);

        // consecutive test
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(0);

        $this->assertInternalType('string', $e->id);
        $this->assertEquals(36, strlen($e->id));

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());

        $this->assertTrue($this->orm->getHeap()->has($e->comments[0]));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e->comments[0])->getStatus());
        $this->assertSame($e->id, $this->orm->getHeap()->get($e->comments[0])->getData()['user_id']);

        $this->assertTrue($this->orm->getHeap()->has($e->comments[1]));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e->comments[1])->getStatus());
        $this->assertSame($e->id, $this->orm->getHeap()->get($e->comments[1])->getData()['user_id']);

        $selector = new Select($this->orm, User::class);
        $selector->load('comments');

        $this->assertEquals([
            [
                'id'       => $e->id,
                'email'    => 'test@email.com',
                'balance'  => 300.0,
                'comments' => [
                    [
                        'id'      => $e->comments[0]->id,
                        'user_id' => $e->id,
                        'message' => 'msg A',
                    ],
                    [
                        'id'      => $e->comments[1]->id,
                        'user_id' => $e->id,
                        'message' => 'msg B',
                    ],
                ],
            ],
        ], $selector->wherePK($e->id)->fetchData());
    }

    public function testRemoveChildren()
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.email')->load('comments');

        /** @var User $e */
        $e = $selector->wherePK($this->u1)->fetchOne();

        $e->comments->remove(1);

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $selector->orderBy('user.id')->load('comments');

        /** @var User $e */
        $e = $selector->wherePK($this->u1)->fetchOne();

        $this->assertCount(2, $e->comments);

        $this->assertSame('msg 1', $e->comments[0]->message);
        $this->assertSame('msg 3', $e->comments[1]->message);
    }

    public function testAddAndRemoveChildren()
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments');

        /** @var User $e */
        $e = $selector->wherePK($this->u1)->fetchOne();

        $e->comments->remove(1);

        $c = new Comment();
        $c->message = "msg 4";
        $e->comments->add($c);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(2);

        // consecutive test
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(0);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $selector->load('comments');

        /** @var User $e */
        $e = $selector->wherePK($this->u1)->fetchOne();

        $this->assertCount(3, $e->comments);

        $this->assertSame('msg 1', $e->comments[0]->message);
        $this->assertSame('msg 3', $e->comments[1]->message);
        $this->assertSame('msg 4', $e->comments[2]->message);
    }

    public function testSliceAndSaveToAnotherParent()
    {
        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        list($b, $a) = $selector->load('comments')->orderBy('user.email')->fetchAll();

        $this->assertCount(3, $a->comments);
        $this->assertCount(0, $b->comments);

        $b->comments = $a->comments->slice(0, 2);
        foreach ($b->comments as $c) {
            $a->comments->removeElement($c);
        }

        $b->comments[0]->message = "new b";

        $this->assertCount(1, $a->comments);
        $this->assertCount(2, $b->comments);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(2);

        // consecutive
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(0);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($b, $a) = $selector->load('comments', [
            'method' => Select\JoinableLoader::INLOAD,
            'alias'  => 'comment'
        ])->orderBy('user.email')->fetchAll();

        $this->assertCount(1, $a->comments);
        $this->assertCount(2, $b->comments);

        $this->assertEquals($this->c3, $a->comments[0]->id);

        $this->assertEquals($this->c2, $b->comments[0]->id);
        $this->assertEquals($this->c1, $b->comments[1]->id);

        $this->assertEquals('new b', $b->comments[1]->message);
    }
}