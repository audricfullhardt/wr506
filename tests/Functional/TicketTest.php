<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TicketTest extends ApiTestCase
{
    private ?EntityManagerInterface $em;
    private ?string $clientToken = null;
    private ?string $adminToken = null;
    private ?string $categoryIri = null;
    private ?string $clientUserUuid = null;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $kernel->getContainer()->get('test.service_container')->get(UserPasswordHasherInterface::class);

        $this->em->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $this->em->getConnection()->executeStatement('DELETE FROM comment');
        $this->em->getConnection()->executeStatement('DELETE FROM ticket');
        $this->em->getConnection()->executeStatement('DELETE FROM category');
        $this->em->getConnection()->executeStatement('DELETE FROM `user`');
        $this->em->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        $clientUser = new User();
        $clientUser->setEmail('client@test.com');
        $clientUser->setRoles(['ROLE_USER']);
        $clientUser->setPassword($hasher->hashPassword($clientUser, 'password'));
        $this->em->persist($clientUser);

        $adminUser = new User();
        $adminUser->setEmail('admin@test.com');
        $adminUser->setRoles(['ROLE_ADMIN']);
        $adminUser->setPassword($hasher->hashPassword($adminUser, 'password'));
        $this->em->persist($adminUser);

        $category = new Category();
        $category->setName('Technique');
        $category->setDescription('Problèmes techniques');
        $this->em->persist($category);

        $this->em->flush();

        $this->clientUserUuid = $clientUser->getUuid()->toRfc4122();
        $this->categoryIri = '/api/categories/' . $category->getUuid()->toRfc4122();

        $this->clientToken = $this->getJwtToken('client@test.com', 'password');
        $this->adminToken = $this->getJwtToken('admin@test.com', 'password');
    }

    private function getJwtToken(string $email, string $password): string
    {
        $response = static::createClient()->request('POST', '/api/login', [
            'json' => [
                'email' => $email,
                'password' => $password,
            ],
        ]);

        $data = $response->toArray();
        return $data['token'];
    }

    public function testClientCanCreateTicket(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/tickets', [
            'headers' => ['Authorization' => 'Bearer ' . $this->clientToken],
            'json' => [
                'title' => 'Mon problème technique',
                'description' => 'Je n\'arrive pas à me connecter au VPN.',
                'priority' => 'haute',
                'category' => $this->categoryIri,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'title' => 'Mon problème technique',
            'priority' => 'haute',
            'status' => 'ouvert',
        ]);
    }

    public function testClientCanModifyOwnTicket(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/tickets', [
            'headers' => ['Authorization' => 'Bearer ' . $this->clientToken],
            'json' => [
                'title' => 'Ticket à modifier',
                'description' => 'Description originale.',
                'priority' => 'normale',
                'category' => $this->categoryIri,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $ticketIri = $response->toArray()['@id'];

        $client->request('PATCH', $ticketIri, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->clientToken,
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'title' => 'Ticket modifié',
                'description' => 'Description mise à jour.',
                'priority' => 'haute',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'title' => 'Ticket modifié',
            'description' => 'Description mise à jour.',
            'priority' => 'haute',
        ]);
    }

    public function testClientCannotDeleteTicket(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/tickets', [
            'headers' => ['Authorization' => 'Bearer ' . $this->clientToken],
            'json' => [
                'title' => 'Ticket non supprimable',
                'description' => 'Ce ticket ne peut pas être supprimé par un client.',
                'priority' => 'faible',
                'category' => $this->categoryIri,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $ticketIri = $response->toArray()['@id'];

        $client->request('DELETE', $ticketIri, [
            'headers' => ['Authorization' => 'Bearer ' . $this->clientToken],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanDeleteTicket(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/tickets', [
            'headers' => ['Authorization' => 'Bearer ' . $this->clientToken],
            'json' => [
                'title' => 'Ticket supprimable par admin',
                'description' => 'Seul un admin peut supprimer ce ticket.',
                'priority' => 'normale',
                'category' => $this->categoryIri,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $ticketIri = $response->toArray()['@id'];

        $client->request('DELETE', $ticketIri, [
            'headers' => ['Authorization' => 'Bearer ' . $this->adminToken],
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testClientCanOnlySeeOwnTickets(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/tickets', [
            'headers' => ['Authorization' => 'Bearer ' . $this->clientToken],
            'json' => [
                'title' => 'Mon ticket',
                'description' => 'Visible uniquement par moi.',
                'priority' => 'normale',
                'category' => $this->categoryIri,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $response = $client->request('GET', '/api/tickets', [
            'headers' => ['Authorization' => 'Bearer ' . $this->clientToken],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        foreach ($data['hydra:member'] as $ticket) {
            $this->assertArrayHasKey('client', $ticket);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em?->close();
        $this->em = null;
    }
}
