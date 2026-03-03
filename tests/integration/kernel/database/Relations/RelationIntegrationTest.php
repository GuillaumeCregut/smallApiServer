<?php

/**
 * Requires a real DB connection (SQLite in-memory recommended).
 * Set up your ConnectorDispatcher to point to a test SQLite instance.
 *
 * Tables expected:
 *   authors (id INT AUTO_INCREMENT PK, name VARCHAR(255) NOT NULL)
 *   posts   (id INT AUTO_INCREMENT PK, title VARCHAR(255) NOT NULL, author_id INT NOT NULL)
 */


use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Datas\LazyBag;
use App\Kernel\Connector\Management\IdentityMap;
use App\Kernel\Connector\Management\EntityManager;
use App\Kernel\Connector\Interfaces\EntityInterface;

class RelationIntegrationTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        $this->em = new EntityManager(new IdentityMap());
        $this->setUpSchema();
    }

    protected function tearDown(): void
    {
        $this->tearDownSchema();
        $this->em->clear();
    }

    private function setUpSchema(): void
    {
        // Example for SQLite:
        // $pdo = \App\Kernel\Connector\ConnectorDispatcher::getConnector()->getConnection();
        // $pdo->exec('CREATE TABLE IF NOT EXISTS authors (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255) NOT NULL)');
        // $pdo->exec('CREATE TABLE IF NOT EXISTS posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title VARCHAR(255) NOT NULL, author_id INTEGER NOT NULL)');
    }

    private function tearDownSchema(): void
    {
        // Example for SQLite:
        // $pdo = \App\Kernel\Connector\ConnectorDispatcher::getConnector()->getConnection();
        // $pdo->exec('DELETE FROM posts');
        // $pdo->exec('DELETE FROM authors');
    }

    private function insertAuthor(string $name): int
    {
        // $connector = \App\Kernel\Connector\ConnectorDispatcher::getConnector();
        // return (int) $connector->executeQuery('INSERT INTO authors (name) VALUES (?)', [$name]);
        throw new \LogicException('Implement insertAuthor() for your test DB.');
    }

    private function insertPost(string $title, int $authorId): int
    {
        // $connector = \App\Kernel\Connector\ConnectorDispatcher::getConnector();
        // return (int) $connector->executeQuery('INSERT INTO posts (title, author_id) VALUES (?, ?)', [$title, $authorId]);
        throw new LogicException('Implement insertPost() for your test DB.');
    }

    public function testFindReturnsSameInstanceForSameId(): void
    {
        $this->markTestSkipped('Needs DB connection');
        $authorId = $this->insertAuthor('Alice');

        $first  = $this->em->find(AuthorEntity::class, $authorId);
        $second = $this->em->find(AuthorEntity::class, $authorId);

        $this->assertSame($first, $second);
    }

    public function testManyToOneRelationReturnsSameAuthorInstanceAcrossPosts(): void
    {
        $this->markTestSkipped('Needs DB connection');
        $authorId = $this->insertAuthor('Bob');
        $this->insertPost('Post One', $authorId);
        $this->insertPost('Post Two', $authorId);

        $repo  = new PostRepository($this->em);
        /**@var Post[]  $posts*/
        $posts = $repo->findBy(['author_id' => $authorId]);

        $this->assertCount(2, $posts);
        $this->assertSame($posts[0]->getAuthor(), $posts[1]->getAuthor());
    }

    public function testLazyBagLoaderRoutesChildEntitiesThroughIdentityMap(): void
    {
        $this->markTestSkipped('Needs DB connection');
        $authorId = $this->insertAuthor('Carol');
        $this->insertPost('Post A', $authorId);
        $this->insertPost('Post B', $authorId);

        /**@var Author  $author*/
        $author = $this->em->find(AuthorEntity::class, $authorId);
        $posts  = $author->getPosts()->toArray();

        $this->assertCount(2, $posts);
        foreach ($posts as $post) {
            $this->assertSame($author, $post->getAuthor());
        }
    }

    public function testPersistAndFlushNewPostWithExistingAuthor(): void
    {
        $this->markTestSkipped('Needs DB connection');
        $authorId = $this->insertAuthor('Dave');
        $author   = $this->em->find(AuthorEntity::class, $authorId);

        $post = new PostEntity();
        $post->setTitle('New Post');
        $post->setAuthor($author);

        $this->em->persist($post);
        $this->em->flush();

        $this->assertNotNull($post->getId());
        /**@var Post $found */
        $found = $this->em->find(PostEntity::class, $post->getId());
        $this->assertSame('New Post', $found->getTitle());
        $this->assertSame($authorId, $found->getAuthor()->getId());
    }

     public function testPersistAndFlushTwoNewEntitiesInDependencyOrder(): void
    {
        $this->markTestSkipped('Needs DB connection');
        $author = new AuthorEntity();
        $author->setName('Eve');

        $post = new PostEntity();
        $post->setTitle('Eve Post');
        $post->setAuthor($author);

        // Persisted in wrong order — EM must reorder author before post
        $this->em->persist($post);
        $this->em->persist($author);
        $this->em->flush();

        $this->assertNotNull($author->getId());
        $this->assertNotNull($post->getId());
        /**@var PostEntity $found */
        $found = $this->em->find(PostEntity::class, $post->getId());
        $this->assertSame($author->getId(), $found->getAuthor()->getId());
    }

    public function testPersistAndFlushUpdatesExistingEntity(): void
    {
        $this->markTestSkipped('Needs DB connection');
        $authorId = $this->insertAuthor('Frank');
        $author   = $this->em->find(AuthorEntity::class, $authorId);
        /**@var AuthorEntity $author */
        $author->setName('Frank Updated');
        $this->em->persist($author);
        $this->em->flush();

        $this->em->clear();
        /**@var AuthorEntity $fresh */
        $fresh = $this->em->find(AuthorEntity::class, $authorId);
        $this->assertSame('Frank Updated', $fresh->getName());
    }

    public function testRemoveAndFlushDeletesEntity(): void
    {
        $this->markTestSkipped('Needs DB connection');
        $authorId = $this->insertAuthor('Grace');
        $author   = $this->em->find(AuthorEntity::class, $authorId);

        $this->em->remove($author);
        $this->em->flush();

        $this->em->clear();
        $this->assertNull($this->em->find(AuthorEntity::class, $authorId));
    }

}

class AuthorEntity implements EntityInterface
{
    private ?int $id = null;
    private string $name = '';
    private ?LazyBag $posts = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getPosts(): LazyBag { return $this->posts; }
    public function setPosts(LazyBag $bag): static { $this->posts = $bag; return $this; }
    public static function getRepository(): ?string { return AuthorRepository::class; }
}

class PostEntity implements EntityInterface
{
    private ?int $id = null;
    private string $title = '';
    private ?AuthorEntity $author = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getAuthor(): ?AuthorEntity { return $this->author; }
    public function setAuthor(?AuthorEntity $author): static { $this->author = $author; return $this; }
    public static function getRepository(): ?string { return PostRepository::class; }
}

class AuthorRepository extends \App\Kernel\Connector\AbstractRepository
{
    protected ?string $entity = AuthorEntity::class;
}

class PostRepository extends \App\Kernel\Connector\AbstractRepository
{
    protected ?string $entity = PostEntity::class;
}