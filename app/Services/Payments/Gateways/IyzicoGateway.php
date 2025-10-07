<?php
namespace App\Services\Payments\Gateways;

use Illuminate\Support\Facades\App;
use Iyzipay\Model\Locale;
use Iyzipay\Model\ThreedsInitialize;
use Iyzipay\Options;
use Iyzipay\Request\CreatePaymentRequest;
use Iyzipay\Model\Currency;
use Iyzipay\Model\PaymentCard;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Request\CreateThreedsPaymentRequest;
use Iyzipay\Model\ThreedsPayment;
use RuntimeException;

class IyzicoGateway
{
    private Options $options;
    public function __construct()
    {
        $this->options = new Options();
        $this->options->setApiKey(config('services.iyzico.api_key'));
        $this->options->setSecretKey(config('services.iyzico.secret_key'));
        $this->options->setBaseUrl(config('services.iyzico.base_url'));
    }

    public function createPayment($transactionId, $price, $cardData, $user, $basket, $currency = Currency::TL)
    {
        $request = new CreatePaymentRequest();
        $request->setLocale(App::getLocale());
        $request->setConversationId($transactionId);
        $request->setPrice($price);
        $request->setPaidPrice($price);
        $request->setCurrency($currency);
        $request->setInstallment(1);

        $paymentCard = new PaymentCard();
        $paymentCard->setCardHolderName($cardData['holder_name'] ?? null);
        $paymentCard->setCardNumber($cardData['card_number'] ?? null);
        $paymentCard->setExpireMonth($cardData['month'] ?? null);
        $paymentCard->setExpireYear($cardData['year'] ?? null);
        $paymentCard->setCvc($cardData['cvc'] ?? null);
        $paymentCard->setRegisterCard(0);
        $request->setPaymentCard($paymentCard);

        $buyer = new Buyer();
        $buyer->setId($user?->id);
        $buyer->setName($user?->first_name);
        $buyer->setSurname($user?->first_name);
        $buyer->setGsmNumber($user?->phone_number);
        $buyer->setEmail($user?->email);
        $buyer->setIdentityNumber("11111111111");
        $buyer->setRegistrationAddress("-");
        $buyer->setIp(request()->ip());
        $buyer->setCity($user->city->name ?? 'İzmir');
        $buyer->setCountry($user->country->native ?? "Türkiye");
        $request->setBuyer($buyer);

        $shippingAddress = new Address();
        $shippingAddress->setContactName($user->full_name);
        $shippingAddress->setCity($user->city->name ?? 'İzmir');
        $shippingAddress->setCountry($user->country->native ?? "Türkiye");
        $shippingAddress->setAddress("-");
        $shippingAddress->setZipCode("34742");
        $request->setShippingAddress($shippingAddress);

        $billingAddress = new Address();
        $billingAddress->setContactName($user->full_name);
        $billingAddress->setCity($user->city->name ?? 'İzmir');
        $billingAddress->setCountry($user->country->native ?? "Türkiye");
        $billingAddress->setAddress("-");
        $request->setBillingAddress($billingAddress);

        $basketItems = [];
        foreach ($basket as $item){
            $firstBasketItem = new BasketItem();
            $firstBasketItem->setId($item['id'] ?? rand(100000, 999999));
            $firstBasketItem->setName($item['name'] ?? null);
            $firstBasketItem->setItemType(BasketItemType::VIRTUAL);
            $firstBasketItem->setCategory1($item['category'] ?? '-');
            $firstBasketItem->setPrice($item['price'] ?? null);
            $basketItems[0] = $firstBasketItem;
        }
        $request->setBasketItems($basketItems);

        $request->setCallbackUrl(route('payments.iyzico.threed-callback'));
        $initialize = ThreedsInitialize::create($request, $this->options);
        if ($initialize->getStatus() === 'success') {
            return $initialize->getHtmlContent();
        }

        throw new RuntimeException($initialize->getErrorMessage());
    }

    public function checkThreedsPaymentRequest($transactionId, $iyzicoPaymentId, $conversationData, $locale = Locale::TR): CreateThreedsPaymentRequest
    {
        $request = new CreateThreedsPaymentRequest();
        $request->setLocale($locale);
        $request->setConversationId($transactionId);
        $request->setPaymentId($iyzicoPaymentId);
        $request->setConversationData($conversationData);
        return $request;
    }

    public function threedsPayment($threedsPaymentRequest): ThreedsPayment
    {
        return ThreedsPayment::create($threedsPaymentRequest, $this->options);
    }
}