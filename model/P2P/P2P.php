<?php

declare(strict_types=1);

namespace Model\P2P;

use Carbon\Carbon;
use Model\Api;
use Model\P2P\DTO\P2PReceiverDTO;
use Model\P2P\DTO\ReceiverPaymentDataDTO;
use Model\P2P\DTO\TeacherDTO;
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

    private function getReceiverIdByUserId(int $userId): ?int
    {
        return isset(self::$receivers[$userId])
            ? self::$receivers[$userId]->getReceiverId()
            : null;
    }

    /**
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
     * @param array $teachersIds
     * @return ReceiverPaymentDataDTO[]
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
        }

        return $receiversDataDTO;
    }

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

}
