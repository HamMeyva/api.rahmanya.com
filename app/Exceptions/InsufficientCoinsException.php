<?php

namespace App\Exceptions;

use Exception;

class InsufficientCoinsException extends Exception
{
    /**
     * @var int
     */
    protected $userId;

    /**
     * @var int
     */
    protected $requiredCoins;

    /**
     * @var int
     */
    protected $availableCoins;

    /**
     * InsufficientCoinsException constructor.
     *
     * @param int $userId
     * @param int $requiredCoins
     * @param int $availableCoins
     * @param string $message
     */
    public function __construct(int $userId, int $requiredCoins, int $availableCoins, $message = "")
    {
        if (empty($message)) {
            $message = "User {$userId} has insufficient coins. Required: {$requiredCoins}, Available: {$availableCoins}";
        }

        parent::__construct($message);

        $this->userId = $userId;
        $this->requiredCoins = $requiredCoins;
        $this->availableCoins = $availableCoins;
    }

    /**
     * Get the user ID.
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Get the required coins.
     *
     * @return int
     */
    public function getRequiredCoins(): int
    {
        return $this->requiredCoins;
    }

    /**
     * Get the available coins.
     *
     * @return int
     */
    public function getAvailableCoins(): int
    {
        return $this->availableCoins;
    }

    /**
     * Get the shortage amount.
     *
     * @return int
     */
    public function getShortage(): int
    {
        return $this->requiredCoins - $this->availableCoins;
    }
}
