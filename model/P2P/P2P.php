<?php

declare(strict_types=1);

namespace Model\P2P;

use Carbon\Carbon;
use Log;
use Model\Api;
use Model\P2P\DTO\CreateTransactionDTO;
use Model\P2P\DTO\P2PReceiverDTO;
use Model\P2P\DTO\ReceiverPaymentDataDTO;
use Model\P2P\DTO\RemotePaymentDTO;
use Model\P2P\DTO\TeacherDTO;
use Model\P2P\DTO\TransactionDTO;
use Model\P2P\Exception\BaseP2PException;
use Payment;

class P2P
{
    private static bool $isInitialized = false;
    private string $apiUrl;
    private string $authToken;
    /** @var P2PReceiverDTO[] */
    private static array $receivers;

    public function __construct()
    {
        $this->init();
    }

    private function init(): void
    {
        if (self::$isInitialized !== false) {
            return;
        }

        global $CFG;
        $this->apiUrl = $CFG->p2p->api_url;
        $this->authToken = $CFG->p2p->project_auth_token;
        $receiversList = array_map(
            function (array $receiverData): P2PReceiverDTO {
                return new P2PReceiverDTO(
                    (int)($receiverData['user_id'] ?? 0),
                    (int)($receiverData['receiver_id'] ?? 0),
                    (string)($receiverData['auth_token'] ?? ''),
                );
            },
            $CFG->p2p->auth_user_tokens ?? []
        );
        self::$receivers = array_combine(
            array_map(
                static fn (P2PReceiverDTO $receiver) => $receiver->getUserId(),
                $receiversList,
            ),
            $receiversList
        );

        self::$isInitialized = true;
    }

    private function getAvailableUsersIds(): array
    {
        return array_map(
            static fn (P2PReceiverDTO $receiver) => $receiver->getUserId(),
            self::$receivers
        );
    }

    private function getReceiverByUserId(int $userId): ?P2PReceiverDTO
    {
        return self::$receivers[$userId] ?? null;
    }

    private function getReceiverIdByUserId(int $userId): ?int
    {
        $receiverDTO = $this->getReceiverByUserId($userId);

        return null !== $receiverDTO
            ? $receiverDTO->getReceiverId()
            : null;
    }

    private function getUserIdByReceiverId(int $receiverId): ?int
    {
        foreach (self::$receivers as $receiver) {
            if ($receiver->getReceiverId() === $receiverId) {
                return $receiver->getUserId();
            }
        }

        return null;
    }

    private function getAuthHeader(string $authToken = null): array
    {
        return ['Authorization: Bearer ' . ($authToken ?: $this->authToken)];
    }

    /**
     * Поиск доступных преподавателей с накопившейся суммой для выплаты более чем $amount в период с $dateFrom по $dateTo
     *
     * @param int $amount
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return TeacherDTO[]
     */
    protected function getTeachersDTO(
        int $amount,
        Carbon $dateFrom,
        Carbon $dateTo
    ): array {
        $bonusesSqlBuilder = (new \Orm())
            ->select('SUM(p.value)')
            ->from('Payment', 'p')
            ->where('p.user', '=', 'slr.teacher_id')
            ->where('p.datetime', '>=', $dateFrom->format('Y-m-d'))
            ->where('p.datetime', '<=', $dateTo->format('Y-m-d'));
        $bonusesIncomeSql = (clone $bonusesSqlBuilder)
            ->where('p.type', '=', Payment::TYPE_BONUS_ADD)
            ->getQueryString();
        $bonusesPayedSql = (clone $bonusesSqlBuilder)
            ->where('p.type', '=', Payment::TYPE_BONUS_PAY)
            ->getQueryString();
        $teachers = (new \Orm())
            ->select([
                'slr.teacher_id AS teacher_id',
                'CONCAT(u.surname, " ", u.name) AS fio',
                'IFNULL(SUM(slr.teacher_rate), 0) AS salary',
                'IFNULL(('.$bonusesIncomeSql.'), 0) AS bonuses_income',
                'IFNULL(('.$bonusesPayedSql.'), 0) AS bonuses_payed',
            ])
            ->from('Schedule_Lesson_Report', 'slr')
            ->join('User AS u', 'u.id = slr.teacher_id')
            ->where('slr.date', '>=', $dateFrom->format('Y-m-d'))
            ->where('slr.date', '<=', $dateTo->format('Y-m-d'))
            ->whereIn('slr.teacher_id', $this->getAvailableUsersIds())
            ->groupBy('slr.teacher_id')
            ->having('salary + bonuses_income - bonuses_payed', '>=', $amount)
            ->get();

        return $teachers->map(
            static fn(\stdClass $teacherData): TeacherDTO => new TeacherDTO(
                (int)$teacherData->teacher_id,
                (string)$teacherData->fio,
            )
        )->toArray();
    }

    /**
     * Поиск данных для p2p перевода из списка доступных преподавателей
     *
     * @param list<int> $teachersIds
     * @return ReceiverPaymentDataDTO[]
     * @throws BaseP2PException
     */
    protected function getReceiversPaymentsDataDTO(array $teachersIds): array
    {
        $instance = $this;
        $receiversIds = array_filter(
            array_map(
                function (int $teacherId) use ($instance): ?int {
                    return $instance->getReceiverIdByUserId($teacherId);
                },
                $teachersIds,
            ),
        );
        if (empty($receiversIds)) {
            return [];
        }

        $response = Api::getJsonRequest(
            $this->apiUrl . '/receiver',
            ['receivers_ids' => $receiversIds],
            ['Authorization: Bearer ' . $this->authToken],
            Api::REQUEST_METHOD_GET,
        );
        $receiversData = json_decode($response);
        $receiversDataDTO = [];
        if (is_array($receiversData->data)) {
            foreach ($receiversData->data as $receiverData) {
                $receiversDataDTO[(int)$receiverData->receiver_id] = new ReceiverPaymentDataDTO(
                    (int)$receiverData->receiver_id,
                    $receiverData->card_number,
                    $receiverData->phone_number,
                    $receiverData->comment,
                );
            }
        } else {
            throw $this->makeApiException($response);
        }

        return $receiversDataDTO;
    }

    /**
     * Получение агрегатного списка возможных преподавателей и их платежных данных для p2p переводов
     *
     * @param int $amount
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     * @throws BaseP2PException
     */
    public function getReceiversDataAggregate(
        int $amount,
        Carbon $dateFrom,
        Carbon $dateTo
    ): array {
        $teachersDTO = $this->getTeachersDTO($amount, $dateFrom, $dateTo);
        if (empty($teachersDTO)) {
            return [];
        }
        $receiversPaymentDataDTO = $this->getReceiversPaymentsDataDTO(array_map(
            static fn (TeacherDTO $teacherDTO) => $teacherDTO->getUserId(),
            $teachersDTO
        ));

        $instance = $this;
        return array_map(
            static fn ($teacherDTO): array => [
                'teacher' => $teacherDTO,
                'payment_data' => $receiversPaymentDataDTO[$instance->getReceiverIdByUserId($teacherDTO->getUserId())] ?? null,
            ],
            $teachersDTO
        );
    }

    public function createPayment(
        int $userId,
        int $receiverId,
        float $amount
    ): ?RemotePaymentDTO {
        $payment = new Payment();
        $payment->user($userId);
        $payment->value((int)($amount));
        $payment->description('P2P перевод');
        $payment->type(Payment::TYPE_INCOME);
        $payment->status(Payment::STATUS_PENDING);
        $payment->save();
        if (!$payment->getId()) {
            return null;
        }

        $transactionDTO = null;
        try {
            $transactionDTO = $this->createP2PTransaction(new CreateTransactionDTO(
                $receiverId,
                $amount,
                $userId,
                $this->getUserIdByReceiverId($receiverId),
                $payment->getId(),
            ));
            $payment->merchantOrderId((string)$transactionDTO->getId());
        } catch (BaseP2PException $p2pException) {
            $payment->setStatusError();
            $payment->description('Не удалось создать транзакцию на стороне p2p сервиса. Обратитесь к менеджеру.');
            $payment->appendComment('Код ошибки создания транзакции на стороне p2p: ' . $p2pException->getErrorLogHash());
        } catch (\Throwable $exception) {
            $payment->setStatusError();
            $payment->description('Не удалось создать транзакцию на стороне p2p сервиса. Обратитесь к менеджеру.');
            $payment->appendComment('Неизвестная ошибка создания транзакции: ' . $exception->getMessage());
        }

        $payment->save();

        return null !== $transactionDTO
            ? new RemotePaymentDTO($payment, $transactionDTO)
            : null;
    }

    /**
     * @param CreateTransactionDTO $createTransactionDTO
     * @return TransactionDTO
     * @throws BaseP2PException
     */
    private function createP2PTransaction(CreateTransactionDTO $createTransactionDTO): TransactionDTO
    {
        $response = Api::getJsonRequest(
            $this->apiUrl . '/transaction/create',
            $createTransactionDTO->toArray(),
            $this->getAuthHeader(),
            Api::REQUEST_METHOD_POST,
        );

        $transactionData = json_decode($response);
        if (null === $transactionData->data) {
            throw $this->makeApiException($response);
        }

        return $this->buildTransactionDTO($transactionData->data);
    }

    /**
     * @param \stdClass $transactionData
     * @return TransactionDTO
     */
    private function buildTransactionDTO(\stdClass $transactionData): TransactionDTO
    {
        return new TransactionDTO(
            (int)$transactionData->id,
            (int)$transactionData->status,
            (float)$transactionData->amount,
            (int)$transactionData->receiver_id,
            (string)($transactionData->createdAt ?? date('Y-m-d H:i:s')),
            (string)($transactionData->updatedAt ?? date('Y-m-d H:i:s')),
            (array)($transactionData->extra_data ?? [])
        );
    }

    /**
     * @param string $jsonResponse
     * @return BaseP2PException
     */
    private function makeApiException(string $jsonResponse): BaseP2PException
    {
        $decodedResponse = json_decode($jsonResponse, true);
        $errorMessage = $decodedResponse['message'] ?? null;
        $hash = !empty($errorMessage) ? md5($errorMessage) : md5($jsonResponse);
        // TODO: обязательно добавить нормальный вывод ошибок
        Log::instance()->error(Log::TYPE_P2P, 'Hash: ' . $hash . PHP_EOL . $jsonResponse);

        return new BaseP2PException($hash);
    }

    /**
     * Получение списка транзакций
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param array $statuses
     * @return TransactionDTO[]
     * @throws BaseP2PException
     */
    public function getReceiverTransactionsByUserId(
        int $userId,
        int $limit,
        int $offset,
        array $statuses = []
    ): array {
        $receiver = $this->getReceiverByUserId($userId);
        if (null === $receiver) {
            return [];
        }
        $requestParams = [
            'limit' => $limit,
            'offset' => $offset,
        ];
        if (!empty($statuses)) {
            $requestParams['statuses'] = $statuses;
        }

        $response = Api::getJsonRequest(
            $this->apiUrl . '/transaction/receiver',
            $requestParams,
            $this->getAuthHeader($receiver->getAuthToken()),
            Api::REQUEST_METHOD_GET,
        );
        $transactionsData = json_decode($response);
        if (null === $transactionsData->data) {
            throw $this->makeApiException($response);
        }

        $transactionsDTO = [];
        foreach ($transactionsData->data as $transactionData) {
            $transactionsDTO[] = $this->buildTransactionDTO($transactionData);
        }

        return $transactionsDTO;
    }

}
