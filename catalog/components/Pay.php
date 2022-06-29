<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/catalog/classes/Paypal.php';

$item = trim(htmlspecialchars($_POST['item']));
$nickname = trim(htmlspecialchars($_POST['nickname']));

if (!empty($item) && !empty($nickname)) {
    $stmt = $dbh->prepare("SELECT * FROM `products` WHERE `name` = ?");
    $stmt->execute([$item]);
    $item = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $paypal = new Paypal($currency, $dbh);
    $paypal->newShop($client_id, $secret);
    $paypal->setReturnUrl('https://lammer-store.fun/');
    $result = $paypal->createOrder(floatval($item[0]['price']));

    if ($result->status == 'CREATED') {
        $params = [
            ':item_name' => $item[0]['name'],
            ':payment_status' => 'WAITING',
            ':payment_amount' => floatval($item[0]['price']),
            ':payment_currency' => $currency,
            ':txn_id' => $result->id,
            ':receiver_email' => null,
            ':payer_email' => null,
            ':player' => $nickname,
            ':type' => $payment_sytem
        ];
        $stmt2 = $dbh->prepare("INSERT INTO `purchases` (`item_name`, `payment_status`, `payment_amount`, `payment_currency`, `txn_id`, `receiver_email`, `payer_email`, `player`, `type`) VALUES (:item_name, :payment_status, :payment_amount, :payment_currency, :txn_id, :receiver_email, :payer_email, :player, :type)");
        $stmt2->execute($params);

        echo $result->links[1]->href;
    }



} else {
    echo 'Error: Enter your server\'s login';
}

?>