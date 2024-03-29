<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/catalog/classes/Paypal.php';

$item = trim(htmlspecialchars($_POST['item']));
$nickname = trim(htmlspecialchars($_POST['nickname']));
$promo = trim(htmlspecialchars($_POST['promo']));

if (!empty($promo)) {
    $stmt3 = $dbh->prepare("SELECT * FROM `promos` WHERE `name` = ?");
    $stmt3->execute([$promo]);
    $promo = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    if ($promo[0]['percent'] == '') {
        echo 'Error: Incorrect promo code';
        return;
    }
}
-
if (!empty($item) && !empty($nickname) && preg_match('/^\w{3,16}$/i', $nickname)) {
    $stmt = $dbh->prepare("SELECT * FROM `products` WHERE `name` = ?");
    $stmt->execute([$item]);
    $item = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $price = $item[0]['price'] - ($promo[0]['percent'] * $item[0]['price']) / 100;

    $paypal = new Paypal($currency, $dbh);
    $paypal->newShop($client_id, $secret);
    $paypal->setReturnUrl($return_url);
    $result = $paypal->createOrder(floatval($price));

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
    echo 'Error: Incorrect server\'s login';
}

?>