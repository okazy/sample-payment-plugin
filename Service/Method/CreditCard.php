<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\SamplePayment\Service\Method;

use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\SamplePayment\Entity\PaymentStatus;
use Plugin\SamplePayment\Repository\PaymentStatusRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * クレジットカード(トークン決済)の決済処理を行う.
 */
class CreditCard implements PaymentMethodInterface
{

    /**
     * @var Order
     */
    protected $Order;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var PaymentStatusRepository
     */
    private $paymentStatusRepository;

    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * CreditCard constructor.
     *
     * @param OrderStatusRepository $orderStatusRepository
     * @param PaymentStatusRepository $paymentStatusRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param RouterInterface $router
     */
    public function __construct(
        OrderStatusRepository $orderStatusRepository,
        PaymentStatusRepository $paymentStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        RouterInterface $router
    ) {
        $this->orderStatusRepository = $orderStatusRepository;
        $this->paymentStatusRepository = $paymentStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->router = $router;
    }

    /**
     * 注文確認画面遷移時に呼び出される.
     *
     * クレジットカードの有効性チェックを行う.
     *
     * @return PaymentResult
     *
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function verify()
    {
        // 決済サーバとの通信処理(有効性チェックやカード番号の下4桁取得)
        // ...
        //

        if (true) {
            $result = new PaymentResult();
            $result->setSuccess(true);
            $this->Order->setSamplePaymentCardNoLast4('****-*****-****-1234');
        } else {
            $result = new PaymentResult();
            $result->setSuccess(false);
            $result->setErrors([trans('sample_payment.shopping.verify.error')]);
        }

        return $result;
    }

    /**
     * 注文時に呼び出される.
     *
     * 受注ステータス, 決済ステータスを更新する.
     * ここでは決済サーバとの通信は行わない.
     *
     * @return PaymentDispatcher|null
     */
    public function apply()
    {
        // 受注ステータスを決済処理中へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $this->Order->setOrderStatus($OrderStatus);

        // 決済ステータスを未決済へ変更
        $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::OUTSTANDING);
        $this->Order->setSamplePaymentPaymentStatus($PaymentStatus);

        // purchaseFlow::prepareを呼び出し, 購入処理を進める.
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());
    }

    /**
     * 注文時に呼び出される.
     *
     * クレジットカードの決済処理を行う.
     *
     * @return PaymentResult
     *
     * @throws \Exception
     */
    public function checkout()
    {
        $this->postLogRequest(['key' => 'key0123', 'no' => 1, 'status' => PaymentStatus::OUTSTANDING]);

        // 決済サーバに仮売上のリクエスト送る(設定等によって送るリクエストは異なる)
        // ...
        //
        $token = $this->Order->getSamplePaymentToken();

        if (true) {
            $this->postLogRequest(['key' => 'key0123', 'no' => 1, 'status' => PaymentStatus::ENABLED]);

            // 受注ステータスを新規受付へ変更
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
            $this->Order->setOrderStatus($OrderStatus);

            // 決済ステータスを仮売上へ変更
            $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::PROVISIONAL_SALES);
            $this->Order->setSamplePaymentPaymentStatus($PaymentStatus);

            // 注文完了画面/注文完了メールにメッセージを追加
            $this->Order->appendCompleteMessage('トークン -> '.$token);
            $this->Order->appendCompleteMailMessage('トークン -> '.$token);

            // purchaseFlow::commitを呼び出し, 購入処理を完了させる.
            $this->purchaseFlow->commit($this->Order, new PurchaseContext());

            $result = new PaymentResult();
            $result->setSuccess(true);
        } else {
            $this->postLogRequest(['key' => 'key0123', 'no' => 1, 'status' => PaymentStatus::PROVISIONAL_SALES]);

            // 受注ステータスを購入処理中へ変更
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PROCESSING);
            $this->Order->setOrderStatus($OrderStatus);

            // 決済ステータスを未決済へ変更
            $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::OUTSTANDING);
            $this->Order->setSamplePaymentPaymentStatus($PaymentStatus);

            // 失敗時はpurchaseFlow::commitを呼び出す.
            $this->purchaseFlow->rollback($this->Order, new PurchaseContext());

            $result = new PaymentResult();
            $result->setSuccess(false);
            $result->setErrors([trans('sample_payment.shopping.checkout.error')]);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormType(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }

    /**
     * API post request processing
     *
     * @param array $data
     *
     * @return array
     *
     * @throws \Exception
     */
    private function postLogRequest($data)
    {
        $url = $this->router->generate('sample_payment_log', array(), UrlGeneratorInterface::ABSOLUTE_URL);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($curl);
        $info = curl_getinfo($curl);
        $message = curl_error($curl);
        $info['message'] = $message;
        curl_close($curl);

        return [$result, $info];
    }
}
