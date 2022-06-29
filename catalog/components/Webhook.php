<?php

require_once '../../settings.php';
require_once '../../vendor/autoload.php';

$raw_data_input = json_decode(file_get_contents('php://input'));
$txn_id = $raw_data_input->resource->id;

//file_put_contents('data.txt', $raw_data_input->resource->purchase_units[0]->amount->value);



$stmt = $dbh->query("SELECT * FROM `purchases` WHERE `txn_id` = '$txn_id'");
$item = $stmt->fetchAll();

if ($raw_data_input->event_type == 'CHECKOUT.ORDER.APPROVED' && $item[0]['txn_id'] == $txn_id && floatval($item[0]['payment_amount']) == $raw_data_input->resource->purchase_units[0]->amount->value) {

    $item_name = $item[0]['item_name'];
    $stmt3 = $dbh->query("SELECT * FROM `products` WHERE `name` = '$item_name'");
    $product = $stmt3->fetchAll();

    $rcon = new Thedudeguy\Rcon($ip, $rcon_port, $rcon_password, 3);

    $command = str_replace("%user%", $item[0]['player'], $product[0]['command']);

    if ($rcon->connect()) {
        $rcon->sendCommand($command);
    }

    $query = "UPDATE `purchases` SET `payment_status` = :payment_status WHERE `txn_id` = :id";
    $params = [
        ':payment_status' => 'APPROVED',
        ':id' => $txn_id
    ];
    $stmt2 = $dbh->prepare($query);
    $stmt2->execute($params);
}

echo json_encode(array());
http_response_code(200);

?>