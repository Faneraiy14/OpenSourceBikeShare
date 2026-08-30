<?php

declare(strict_types=1);

namespace BikeShare\Test\Application\Controller\Api\User;

use BikeShare\App\Security\UserProvider;
use BikeShare\Credit\CreditSystemInterface;
use BikeShare\Enum\Action;
use BikeShare\Enum\CreditChangeType;
use BikeShare\Repository\HistoryRepository;
use BikeShare\Repository\UserRepository;
use BikeShare\Test\Application\BikeSharingWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

class UserCreditHistoryTest extends BikeSharingWebTestCase
{
    private const USER_PHONE_NUMBER = '421951111111';

    protected function setUp(): void
    {
        $this->setEnvVar('CREDIT_SYSTEM_ENABLED', '1');
        parent::setUp();
    }

    public function testCreditHistoryReturnsArray(): void
    {
        $user = $this->client->getContainer()->get(UserProvider::class)->loadUserByIdentifier(self::USER_PHONE_NUMBER);
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_GET, '/api/v1/me/credit-history');
        $this->assertResponseIsSuccessful();
        $data = $this->decodeApiResponseData();
        $this->assertIsArray($data, 'Response data must be an array');
    }

    public function testCreditHistoryReturnsItemsWithExpectedKeys(): void
    {
        $userData = $this->client->getContainer()->get(UserRepository::class)
            ->findItemByPhoneNumber(self::USER_PHONE_NUMBER);
        $creditSystem = $this->client->getContainer()->get(CreditSystemInterface::class);
        $creditSystem->increaseCredit($userData['userId'], 25.0, CreditChangeType::CREDIT_ADD);

        $user = $this->client->getContainer()->get(UserProvider::class)->loadUserByIdentifier(self::USER_PHONE_NUMBER);
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_GET, '/api/v1/me/credit-history');
        $this->assertResponseIsSuccessful();
        $data = $this->decodeApiResponseData();
        $this->assertIsArray($data);
        $this->assertNotEmpty($data, 'Credit history should contain at least one entry after adding credit');
        $first = $data[0];
        $this->assertArrayHasKey('date', $first);
        $this->assertArrayHasKey('amount', $first);
        $this->assertArrayHasKey('type', $first);
        $this->assertArrayHasKey('balance', $first);
    }

    public function testCreditHistoryRequiresAuthentication(): void
    {
        $this->client->request(Request::METHOD_GET, '/api/v1/me/credit-history');
        $this->assertResponseStatusCodeSame(401);
    }

    public static function reasonProvider(): iterable
    {
        yield 'known reason' => [CreditChangeType::CREDIT_ADD->value, CreditChangeType::CREDIT_ADD->value];
        yield 'legacy/unknown' => ['legacy_reason_not_in_enum', 'unknown'];
    }

    #[DataProvider('reasonProvider')]
    public function testCreditHistoryMapsReasonType(string $storedReason, string $expectedType): void
    {
        $userData = $this->client->getContainer()->get(UserRepository::class)
            ->findItemByPhoneNumber(self::USER_PHONE_NUMBER);
        $this->client->getContainer()->get(HistoryRepository::class)->addItem(
            $userData['userId'],
            0,
            Action::CREDIT_CHANGE,
            json_encode(['amount' => 1.0, 'balance' => 1.0, 'reason' => $storedReason], JSON_THROW_ON_ERROR)
        );
        $user = $this->client->getContainer()->get(UserProvider::class)->loadUserByIdentifier(self::USER_PHONE_NUMBER);
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_GET, '/api/v1/me/credit-history');

        $this->assertResponseIsSuccessful(); // 200, not 500 - the actual regression
        $this->assertSame($expectedType, $this->decodeApiResponseData()[0]['type']);
    }
}
