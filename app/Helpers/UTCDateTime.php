<?php

namespace App\Helpers;

/**
 * UTCDateTime sınıfı, MongoDB tipi bulunmadığında kullanılabilecek fallback sınıfıdır
 * Bu sınıf sadece geliştirme ortamında IDE'nin tip tanıması için kullanılır
 */
class UTCDateTime
{
    /**
     * Unix timestamp milisaniye cinsinden
     * @var int
     */
    private $milliseconds;
    
    /**
     * Yeni bir UTCDateTime oluştur
     *
     * @param int $milliseconds Unix zaman damgası (milisaniye)
     */
    public function __construct(int $milliseconds = null)
    {
        $this->milliseconds = $milliseconds ?? round(microtime(true) * 1000);
    }
    
    /**
     * Zaman damgasını DateTime nesnesine dönüştürür
     *
     * @return \DateTime
     */
    public function toDateTime()
    {
        $dateTime = new \DateTime();
        $dateTime->setTimestamp((int)($this->milliseconds / 1000));
        $microseconds = ($this->milliseconds % 1000) * 1000;
        $dateTime->setTime(
            (int)$dateTime->format('H'),
            (int)$dateTime->format('i'),
            (int)$dateTime->format('s'),
            (int)$microseconds
        );
        
        return $dateTime;
    }
}
